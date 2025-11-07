<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Admin;

class MakeSuperAdmin extends Command
{
    protected $signature = 'user:make-superadmin {email}';
    protected $description = 'Convert a user to superadmin';

    public function handle()
    {
        $email = $this->argument('email');
        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("User with email {$email} not found.");
            return 1;
        }

        // Verify email if not verified
        if (!$user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
            $this->info("Email verified for {$email}");
        }

        // Check if already an admin
        $admin = Admin::where('user_id', $user->id)->first();
        if ($admin) {
            $this->info("User is already an admin with role: {$admin->role}");
            
            if ($admin->role !== 'superadmin') {
                $admin->role = 'superadmin';
                $admin->save();
                $this->info("User role updated to superadmin!");
            }
        } else {
            // Create admin record
            Admin::create([
                'user_id' => $user->id,
                'role' => 'superadmin'
            ]);
            $this->info("User {$email} is now a superadmin!");
        }

        return 0;
    }
}
