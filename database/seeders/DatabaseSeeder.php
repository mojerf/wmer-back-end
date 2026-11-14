<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // The admin account comes from the environment (ADMIN_EMAIL / ADMIN_PASSWORD), never from the repository.
        $password = config('app.admin.password') ?? (app()->isLocal() ? 'password' : Str::password(24));

        User::factory()->admin()->create([
            'first_name' => 'مجتبی',
            'last_name' => 'عرفان‌راد',
            'email' => config('app.admin.email'),
            'phone_number' => null,
            'password' => $password,
        ]);

        if (! config('app.admin.password') && ! app()->isLocal()) {
            $this->command?->warn("Generated admin password (shown once): {$password}");
        }

        // Demo content is for local development and testing only; production gets just the administrator.
        if (! app()->isProduction()) {
            $this->call(DemoContentSeeder::class);

            $this->command?->info('Demo accounts (password: "password" unless ADMIN_PASSWORD is set for the admin):');
            $this->command?->line('  admin    '.config('app.admin.email'));
            $this->command?->line('  customer user@example.test');
        }
    }
}
