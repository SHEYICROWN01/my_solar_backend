<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Admin;
use Illuminate\Support\Facades\Hash;

class CreateSuperAdmin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:create-super';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a super admin user';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Creating Super Admin...');

        try {
            // Check if admin with this email already exists
            $existingAdmin = Admin::where('email', 'oginni@2019@gmail.com')->first();
            
            if ($existingAdmin) {
                $this->error('Admin with email oginni@2019@gmail.com already exists!');
                return 1;
            }

            // Create the super admin
            $admin = Admin::create([
                'first_name' => 'Joseph',
                'last_name' => 'Oginni',
                'email' => 'oginni@2019@gmail.com',
                'phone_number' => '+1234567890', // Default phone number
                'role' => 'super_admin',
                'permissions' => [
                    'users.view',
                    'users.create',
                    'users.edit',
                    'users.delete',
                    'products.view',
                    'products.create',
                    'products.edit',
                    'products.delete',
                    'categories.view',
                    'categories.create',
                    'categories.edit',
                    'categories.delete',
                    'orders.view',
                    'orders.create',
                    'orders.edit',
                    'orders.delete',
                    'settings.view',
                    'settings.edit',
                    'admins.view',
                    'admins.create',
                    'admins.edit',
                    'admins.delete',
                    'analytics.view',
                    'promotions.view',
                    'promotions.create',
                    'promotions.edit',
                    'promotions.delete'
                ],
                'password' => Hash::make('Gifamz@2025@'),
                'status' => 'active'
            ]);

            $this->newLine();
            $this->info('✅ Super Admin created successfully!');
            $this->table(['Field', 'Value'], [
                ['ID', $admin->id],
                ['Name', $admin->first_name . ' ' . $admin->last_name],
                ['Email', $admin->email],
                ['Role', $admin->role],
                ['Status', $admin->status],
                ['Created At', $admin->created_at->format('Y-m-d H:i:s')]
            ]);

            $this->warn('⚠️  Please change the default phone number if needed.');
            
        } catch (\Exception $e) {
            $this->error('Failed to create super admin:');
            $this->error($e->getMessage());
            return 1;
        }

        return 0;
    }
}
