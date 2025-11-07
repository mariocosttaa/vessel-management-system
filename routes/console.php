<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Register test data seeder command
Artisan::command('test:seed', function () {
    $this->call('db:seed', ['--class' => \Database\Seeders\Test\ComprehensiveTestSeeder::class]);
})->purpose('Seed comprehensive test data for user permissions and vessel management');
