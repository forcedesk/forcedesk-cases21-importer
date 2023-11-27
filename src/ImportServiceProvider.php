<?php

namespace Schooldesk\Cases21Importer;

use Illuminate\Support\ServiceProvider;
use Schooldesk\Cases21Importer\Commands\ImportStaffData;

class ImportServiceProvider extends ServiceProvider
{
    /**
     * Run service provider boot operations.
     */
    public function boot(): void
    {
        $this->registerCommands();
    }

    /**
     * Register the CASES21 Importer artisan commands.
     */
    protected function registerCommands(): void
    {
        $this->commands([
            ImportStaffData::class,
        ]);
    }
}
