<?php

use Illuminate\Support\Facades\Route;
use App\Models\Admin;
use Illuminate\Support\Facades\Hash;

Route::get('/', function () {
    return view('welcome');
});

// One-time route to create superadmin
Route::get('/create-superadmin-once', function () {
    try {
        // Check if admin already exists
        $existingAdmin = Admin::where('email', 'admin@gifamz.com')->first();
        
        if ($existingAdmin) {
            return response()->json([
                'status' => 'info',
                'message' => 'Admin already exists',
                'email' => 'admin@gifamz.com'
            ]);
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

        return response()->json([
            'status' => 'success',
            'message' => 'Superadmin created successfully!',
            'email' => $admin->email,
            'role' => $admin->role,
            'instructions' => 'You can now login at /api/admins/login with email: admin@gifamz.com and password: Admin@123'
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage()
        ], 500);
    }
});
