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
            ['email' => 'info@khan.ng'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'email_verified_at' => now(),
            ]
        );

        // Create Member User
        User::firstOrCreate(
            ['email' => 'member@khan.ng'],
            [
                'name' => 'Member User',
                'password' => Hash::make('password'),
                'role' => 'member',
                'email_verified_at' => now(),
            ]
        );

        $this->command->info('Admin and Member users created successfully!');
        $this->command->info('Admin: info@khan.ng / password');
        $this->command->info('Member: member@khan.ng / password');
    }
}
