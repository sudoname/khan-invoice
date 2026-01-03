<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class SearchUserCommand extends Command
{
    protected $signature = 'user:search {query}';
    protected $description = 'Search for a user by name or email';

    public function handle(): int
    {
        $query = $this->argument('query');

        $users = User::where('name', 'like', "%{$query}%")
            ->orWhere('email', 'like', "%{$query}%")
            ->get();

        if ($users->isEmpty()) {
            $this->error("No users found matching: {$query}");
            $this->newLine();
            $this->info("Showing all users for reference:");
            $this->newLine();

            $allUsers = User::all();
            foreach ($allUsers as $user) {
                $verified = $user->email_verified_at ? '✓' : '✗';
                $this->line("[{$user->id}] {$verified} {$user->name} <{$user->email}>");
            }

            return 1;
        }

        $this->info("Found " . $users->count() . " user(s):");
        $this->newLine();

        foreach ($users as $user) {
            $verified = $user->email_verified_at ? 'Yes ✓' : 'No ✗';
            $this->line("User ID: {$user->id}");
            $this->line("Name: {$user->name}");
            $this->line("Email: {$user->email}");
            $this->line("Verified: {$verified}");
            $this->line("Verified At: " . ($user->email_verified_at ?? 'Not verified'));
            $this->line("API Enabled: " . ($user->api_enabled ? 'Yes' : 'No'));
            $this->newLine();
        }

        return 0;
    }
}
