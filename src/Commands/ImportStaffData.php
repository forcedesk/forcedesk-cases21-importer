<?php

namespace SchoolDesk\Cases21Importer\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Str;
use function Laravel\Prompts\alert;
use function Laravel\Prompts\confirm;
use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\note;
use function Laravel\Prompts\progress;
use function Laravel\Prompts\select;
use function Laravel\Prompts\spin;

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

        $datadir = spin(
            fn () => Storage::disk('importers')->files(),
            'Parsing Import Directory...'
        );

        $directory = array_filter($datadir, function ($item) {
            return strpos($item, '.csv');
        });

        $files = [];

        foreach ($directory as $file => $filename) {
            $files[$filename] = $filename.' (Modified: '.date ("F d Y H:i:s", filemtime(Storage::disk('importers')->path($filename))).')';
        }

        if (empty($files)) {
            alert('Could not find any import files. Check that you have placed the appropriate CSV in the "storage/app/importers" folder.');
            return false;
        }

        $importfile = select(label: 'Which CASES21 Staff File do you want to import?', options: $files, scroll: 50);

        $csvFile = fopen(Storage::disk('importers')->path($importfile), 'r');
        $firstline = true;
        $importcount = 0;
        $newcount = 0;
        $updatedcount = 0;
        $updatedlist = [];
        $newlist = [];

        /* Grab the row count, so we can pass it to the progress bar */
        $rowCount = 0;
        if ($csvFile !== FALSE) {
            while(!feof($csvFile)) {
                $rowdata = fgetcsv($csvFile , 0 , ',' , '"', '"' );
                if(empty($rowdata)) continue; //empty row
                $rowCount++;
            }
        }

        if($rowCount == '0')
        {
            error('The data file was empty.');
            return false;
        }

        if(!strpos(file_get_contents(Storage::disk('importers')->path($importfile)), 'SFKEY')) {
            error('Not a valid CASES21 Staff File. Does not contain the SFKEY column or is the wrong type of file.');
            return false;
        }

        $confirmimport = confirm(label: 'Do you wish to process this import?', default: false);

        if (!$confirmimport) {
            error('Aborted import.');
            return false;
        }

        $progress = progress(label: 'Searching for Active Staff Records. Please wait....', steps: $rowCount);
        $progress->start();

        try {
            $csvFile = fopen(Storage::disk('importers')->path($importfile), 'r');
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
                            $user->name = $firstname . ' ' . $surname;
                            $user->email = $email;
                            $user->staff_code = $emplid;
                            $user->password = Hash::make(Str::random(16));
                            $newcount++;
                            $newlist[] = $user->name;
                        } else {
                            $user->name = $firstname . ' ' . $surname;
                            $user->email = $email;
                            $user->staff_code = $emplid;
                            $updatedcount++;
                            $updatedlist[] = $user->name;
                        }

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

        if(!empty($updatedlist))
        {
            note('Updated Records');

            foreach($updatedlist as $updated)
            {
                $this->info('Updated existing record for '.$updated);
            }
        }

        if(!empty($newlist))
        {
            note('Created Records');

            foreach($newlist as $new)
            {
                $this->info('Created new record for '.$new);
            }
        }


        info('Staff data was successfully processed from CASES21. Processed ' . $importcount . ' total active records out of '.$rowCount.': ('.$newcount.' new, '.$updatedcount.' updated.)');

        return true;
    }
}
