<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateAdminUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:create-admin {--email=admin@khaninvoice.com} {--password=password} {--name=Admin}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create an admin user for the application';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->option('email');
        $password = $this->option('password');
        $name = $this->option('name');

        // Check if user already exists
        if (User::where('email', $email)->exists()) {
            $this->error("User with email {$email} already exists!");

            if ($this->confirm('Do you want to reset the password for this user?')) {
                $user = User::where('email', $email)->first();
                $user->password = Hash::make($password);
                $user->role = 'admin';
                $user->email_verified_at = now();
                $user->save();

                $this->info('Password reset successfully!');
                $this->line('');
                $this->info('Login Credentials:');
                $this->line("Email: {$email}");
                $this->line("Password: {$password}");
                $this->line('');
                $this->info('Login URL: ' . config('app.url') . '/app/login');
                return 0;
            }

            return 1;
        }

        // Create new admin user
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        $this->info('Admin user created successfully!');
        $this->line('');
        $this->info('Login Credentials:');
        $this->line("Email: {$email}");
        $this->line("Password: {$password}");
        $this->line('');
        $this->info('Login URL: ' . config('app.url') . '/app/login');
        $this->info('Admin Panel: ' . config('app.url') . '/admin');

        return 0;
    }
}
