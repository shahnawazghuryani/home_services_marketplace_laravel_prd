<?php

use App\Models\User;
use Database\Seeders\LaunchReadinessSeeder;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('marketplace:seed-launch', function () {
    $this->call('db:seed', ['--class' => LaunchReadinessSeeder::class, '--force' => true]);
    $this->info('Launch categories seeded successfully.');
})->purpose('Seed launch-ready categories and baseline marketplace data.');

Artisan::command('marketplace:secure-admin {email?} {--new-email=} {--password=} {--name=}', function (?string $email = null) {
    $admin = $email
        ? User::query()->where('email', $email)->where('role', 'admin')->first()
        : User::query()->where('role', 'admin')->first();

    if (! $admin) {
        $this->error('Admin user not found.');
        return 1;
    }

    $password = (string) $this->option('password');
    if ($password === '') {
        $this->error('Provide --password to secure the admin credentials.');
        return 1;
    }

    $admin->update(array_filter([
        'email' => $this->option('new-email') ?: null,
        'name' => $this->option('name') ?: null,
        'password' => Hash::make($password),
    ], static fn ($value) => $value !== null));

    $this->info('Admin credentials updated successfully.');
    return 0;
})->purpose('Secure the production admin account with a fresh password and optional email/name changes.');