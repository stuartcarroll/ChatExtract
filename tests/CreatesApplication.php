<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;

trait CreatesApplication
{
    /**
     * Creates the application.
     */
    public function createApplication(): Application
    {
        // Set APP_KEY before app creation
        $_ENV['APP_KEY'] = 'base64:fFBYk/HBY5qvWPzoHEO6PRcJJHzfZP0+aq0SS7ZXnjk=';
        putenv('APP_KEY=base64:fFBYk/HBY5qvWPzoHEO6PRcJJHzfZP0+aq0SS7ZXnjk=');

        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        return $app;
    }
}
