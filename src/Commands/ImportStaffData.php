<?php

namespace Schooldesk\Cases21Importer\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Str;
use function Laravel\Prompts\alert;
use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\progress;
use function Laravel\Prompts\select;

class ImportStaffData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'importers:cases21-staff';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Imports Staff Account Data via a CASES21 Export.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return true|int
     */
    public function handle(): true|int
    {

        if (config('auth.guards.web.provider') != 'internaldb') {
            alert('The internal database is not used for authentication. To use the internal database, change your authentication provider to "internaldb".');
            return false;
        }

        $directory = array_filter(Storage::disk('dataimport')->files(), function ($item) {
            return strpos($item, '.csv');
        });

        $files = [];

        foreach ($directory as $file => $filename) {
            $files[$filename] = $filename;
        }

        if (empty($files)) {
            alert('There are no CASES21 import files available. Check your Eduhub configuration.');
            return false;
        }

        $importfile = select(label: 'Which CASES21 Staff File do you want to import?', options: $files);

        $csvFile = fopen(Storage::disk('dataimport')->path($importfile), 'r');
        $firstline = true;
        $importcount = 0;

        /* Grab the row count, so we can pass it to the progress bar */
        $rowCount = 0;
        if (($fp = fopen(Storage::disk('dataimport')->path($importfile), "r")) !== FALSE) {
            while(!feof($fp)) {
                $data = fgetcsv($fp , 0 , ',' , '"', '"' );
                if(empty($data)) continue; //empty row
                $rowCount++;
            }
            fclose($fp);
        }

        if($rowCount == '0')
        {
            error('The data file was empty.');
            return false;
        }

        if( strpos(file_get_contents(Storage::disk('dataimport')->path($importfile)),'SFKEY') == false && strpos(file_get_contents(Storage::disk('dataimport')->path($importfile)),'FACULTY_01') == false) {
            // do stuff
        }

        $progress = progress(label: 'Processing accounts', steps: $rowCount);
        $progress->start();

        try {
            while (($data = fgetcsv($csvFile, 2000, ',')) !== false) {
                if (!$firstline) {

                    /* The fields to process from the CASES21 import */
                    $status = $data['47'];
                    $email = $data['13'];
                    $emplid = $data['52'];
                    $firstname = $data['3'];
                    $surname = $data['1'];

                    if (filter_var($email, FILTER_VALIDATE_EMAIL) && is_numeric($emplid) && $status == 'ACTV') {

                        /* Find the user including soft-deleted users  */
                        $user = User::where('email', $email)->where('staff_code', $emplid)->withTrashed()->first();

                        if(!$user)
                        {
                            $user = new User;
                            $user->password = Hash::make(Str::random(16));
                        }

                        $user->name = $firstname . ' ' . $surname;
                        $user->email = $email;
                        $user->staff_code = $emplid;

                        $user->assignRole('Helpdesk User');
                        $user->save();

                        $importcount++;

                    }
                }
                $firstline = false;
                $progress->advance();
            }

            fclose($csvFile);

        } catch (\Exception $e) {
            error('There was an error processing the data, or the file was not a proper CASES21 Staff File.');
            error($e->getMessage());
            fclose($csvFile);
        }

        $progress->finish();

        info('Staff data was successfully processed from CASES21. Processed ' . $importcount . ' total records.');

        return true;
    }
}
