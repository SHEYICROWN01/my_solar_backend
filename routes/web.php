<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;

Route::get('/', function () {
    return view('welcome');
});

// One-time route to create superadmin
Route::get('/create-superadmin-once', function () {
    try {
        // Run the artisan command
        Artisan::call('admin:create-super');
        
        $output = Artisan::output();
        
        return response()->json([
            'status' => 'success',
            'message' => 'Superadmin creation command executed',
            'output' => $output,
            'instructions' => 'Login at /api/admins/login with email: admin@gifamz.com and password: Admin@123'
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage()
        ], 500);
    }
});
