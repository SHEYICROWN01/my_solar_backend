<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;

class VerifyUserEmail extends Command
{
    protected $signature = 'user:verify {email}';
    protected $description = 'Manually verify a user email';

    public function handle()
    {
        $email = $this->argument('email');
        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("User with email {$email} not found.");
            return 1;
        }

        if ($user->hasVerifiedEmail()) {
            $this->info("User {$email} is already verified.");
            return 0;
        }

        $user->markEmailAsVerified();
        $this->info("User {$email} has been verified successfully!");
        return 0;
    }
}
