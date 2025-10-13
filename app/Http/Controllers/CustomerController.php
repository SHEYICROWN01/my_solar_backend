<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Carbon\Carbon;

class CustomerController extends Controller
{
    public function getAllCustomers()
    {
        // Fetch all customers (users with role set to false)
        $customers = User::where('role', false)->get();

        // Calculate statistics
        $totalCustomers = $customers->count();
        $activeCustomers = $customers->where('status', 'active')->count();
        $vipCustomers = $customers->where('is_vip', true)->count();
        $newThisMonth = $customers->filter(function ($customer) {
            return Carbon::parse($customer->created_at)->isCurrentMonth();
        })->count();

        // Return response
        return response()->json([
            'total_customers' => $totalCustomers,
            'active_customers' => $activeCustomers,
            'vip_customers' => $vipCustomers,
            'new_this_month' => $newThisMonth,
            'customers' => $customers
        ]);
    }
}