<?php

// One-time script to create superadmin
// Run this via: curl https://web-production-d1120.up.railway.app/create-admin-once.php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Admin;
use Illuminate\Support\Facades\Hash;

try {
    // Check if admin already exists
    $existingAdmin = Admin::where('email', 'admin@gifamz.com')->first();
    
    if ($existingAdmin) {
        echo json_encode([
            'status' => 'info',
            'message' => 'Admin already exists',
            'email' => 'admin@gifamz.com'
        ]);
        exit;
    }

    // Create superadmin
    $admin = Admin::create([
        'name' => 'Super Admin',
        'email' => 'admin@gifamz.com',
        'password' => Hash::make('Admin@123'),
        'role' => 'superadmin',
        'is_active' => true,
        'email_verified_at' => now(),
    ]);

    echo json_encode([
        'status' => 'success',
        'message' => 'Superadmin created successfully',
        'email' => $admin->email,
        'role' => $admin->role
    ]);

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
