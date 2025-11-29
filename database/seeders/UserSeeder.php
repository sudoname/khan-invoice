<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Admin User
        User::firstOrCreate(
            ['email' => 'admin@khaninvoice.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'email_verified_at' => now(),
            ]
        );

        // Create Member User
        User::firstOrCreate(
            ['email' => 'member@khaninvoice.com'],
            [
                'name' => 'Member User',
                'password' => Hash::make('password'),
                'role' => 'member',
                'email_verified_at' => now(),
            ]
        );

        $this->command->info('Admin and Member users created successfully!');
        $this->command->info('Admin: admin@khaninvoice.com / password');
        $this->command->info('Member: member@khaninvoice.com / password');
    }
}
