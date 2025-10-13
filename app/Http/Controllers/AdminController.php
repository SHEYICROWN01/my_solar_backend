<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Admin;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class AdminController extends Controller
{
    /**
     * Add a new admin.
     */
    public function addAdmin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:admins,email|max:255',
            'phone_number' => 'required|string|max:20',
            'role' => 'required|string|max:255',
            'permissions' => 'required|array',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $admin = Admin::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'phone_number' => $request->phone_number,
            'role' => $request->role,
            'permissions' => $request->permissions,
            'password' => Hash::make($request->password),
        ]);

        return response()->json(['message' => 'Admin created successfully', 'admin' => $admin], 201);
    }

    /**
     * Fetch the admin list.
     */
    public function getAdminList()
    {
        $admins = Admin::all();

        return response()->json(['admins' => $admins], 200);
    }

    /**
     * Edit an admin.
     */
    public function editAdmin(Request $request, $id)
    {
        $admin = Admin::find($id);

        if (!$admin) {
            return response()->json(['message' => 'Admin not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'first_name' => 'sometimes|string|max:255',
            'last_name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:admins,email,' . $id . '|max:255',
            'phone_number' => 'sometimes|string|max:20',
            'role' => 'sometimes|string|max:255',
            'permissions' => 'sometimes|array',
            'password' => 'sometimes|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $admin->update([
            'first_name' => $request->first_name ?? $admin->first_name,
            'last_name' => $request->last_name ?? $admin->last_name,
            'email' => $request->email ?? $admin->email,
            'phone_number' => $request->phone_number ?? $admin->phone_number,
            'role' => $request->role ?? $admin->role,
            'permissions' => $request->permissions ?? $admin->permissions,
            'password' => $request->password ? Hash::make($request->password) : $admin->password,
        ]);

        return response()->json(['message' => 'Admin updated successfully', 'admin' => $admin], 200);
    }

    /**
     * Delete an admin.
     */
    public function deleteAdmin($id)
    {
        $admin = Admin::find($id);

        if (!$admin) {
            return response()->json(['message' => 'Admin not found'], 404);
        }

        $admin->delete();

        return response()->json(['message' => 'Admin deleted successfully'], 200);
    }

    /**
     * Admin login.
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Find admin by email
        $admin = Admin::where('email', $request->email)->first();

        // Check if admin exists and password is correct
        if (!$admin || !Hash::check($request->password, $admin->password)) {
            return response()->json(['message' => 'Invalid login credentials'], 401);
        }

        // Check if admin is active
        if ($admin->status !== 'active') {
            return response()->json(['message' => 'Account is not active'], 401);
        }

        // Update last login timestamp
        $admin->update(['last_login' => now()]);

        // Create token
        $token = $admin->createToken('AdminToken')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'admin' => $admin,
            'token' => $token,
        ], 200);
    }

    /**
     * Get all customer pre-orders for admin management
     */
    public function getCustomerPreOrders(Request $request)
    {
        $query = \App\Models\CustomerPreOrder::with(['preOrder.category']);

        // Add filtering by status
        if ($request->has('status')) {
            $query->where('status', $request->get('status'));
        }

        // Add filtering by payment status
        if ($request->has('payment_status')) {
            $query->where('payment_status', $request->get('payment_status'));
        }

        // Add search functionality
        if ($request->has('search')) {
            $searchTerm = $request->get('search');
            $query->where(function($q) use ($searchTerm) {
                $q->where('pre_order_number', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('customer_email', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('first_name', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('last_name', 'LIKE', "%{$searchTerm}%");
            });
        }

        // Add sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $request->get('per_page', 15);
        $customerPreOrders = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $customerPreOrders,
            'message' => 'Customer pre-orders retrieved successfully'
        ], 200);
    }

    /**
     * Update customer pre-order status (Admin only)
     */
    public function updateCustomerPreOrderStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending,deposit_paid,fully_paid,ready_for_pickup,completed,cancelled',
            'notes' => 'nullable|string|max:1000'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $customerPreOrder = \App\Models\CustomerPreOrder::with('preOrder')->findOrFail($id);
        $previousStatus = $customerPreOrder->status;
        $newStatus = $request->status;

        // Validate status transition
        $validTransitions = [
            'pending' => ['deposit_paid', 'cancelled'],
            'deposit_paid' => ['ready_for_pickup', 'cancelled'],
            'fully_paid' => ['ready_for_pickup', 'cancelled'],
            'ready_for_pickup' => ['completed', 'cancelled'],
            'completed' => [], // No transitions from completed
            'cancelled' => [] // No transitions from cancelled
        ];

        if (!in_array($newStatus, $validTransitions[$previousStatus] ?? [])) {
            return response()->json([
                'success' => false,
                'message' => "Cannot transition from {$previousStatus} to {$newStatus}"
            ], 400);
        }

        // Update the status
        $customerPreOrder->update([
            'status' => $newStatus,
            'notes' => $request->notes ?? $customerPreOrder->notes
        ]);

        // Set timestamps based on status
        switch ($newStatus) {
            case 'ready_for_pickup':
                $customerPreOrder->update(['ready_at' => now()]);
                break;
            case 'completed':
                $customerPreOrder->update(['completed_at' => now()]);
                break;
        }

        // Send status update email
        try {
            \Mail::to($customerPreOrder->customer_email)->send(
                new \App\Mail\PreOrderStatusUpdated($customerPreOrder, $previousStatus)
            );
        } catch (\Exception $e) {
            \Log::error('Failed to send pre-order status email: ' . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'data' => $customerPreOrder->fresh(['preOrder.category']),
            'message' => 'Customer pre-order status updated successfully'
        ], 200);
    }

    /**
     * Get specific customer pre-order details for admin
     */
    public function getCustomerPreOrder($id)
    {
        $customerPreOrder = \App\Models\CustomerPreOrder::with(['preOrder.category'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $customerPreOrder,
            'message' => 'Customer pre-order details retrieved successfully'
        ], 200);
    }

    /**
     * Bulk update customer pre-order statuses
     */
    public function bulkUpdateCustomerPreOrderStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'pre_order_ids' => 'required|array',
            'pre_order_ids.*' => 'exists:customer_pre_orders,id',
            'status' => 'required|in:pending,deposit_paid,fully_paid,ready_for_pickup,completed,cancelled',
            'notes' => 'nullable|string|max:1000'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $updatedCount = 0;
        $errors = [];

        foreach ($request->pre_order_ids as $id) {
            try {
                $customerPreOrder = \App\Models\CustomerPreOrder::with('preOrder')->findOrFail($id);
                $previousStatus = $customerPreOrder->status;
                
                // Update the status
                $customerPreOrder->update([
                    'status' => $request->status,
                    'notes' => $request->notes ?? $customerPreOrder->notes
                ]);

                // Set timestamps based on status
                switch ($request->status) {
                    case 'ready_for_pickup':
                        $customerPreOrder->update(['ready_at' => now()]);
                        break;
                    case 'completed':
                        $customerPreOrder->update(['completed_at' => now()]);
                        break;
                }

                // Send status update email
                try {
                    \Mail::to($customerPreOrder->customer_email)->send(
                        new \App\Mail\PreOrderStatusUpdated($customerPreOrder, $previousStatus)
                    );
                } catch (\Exception $e) {
                    \Log::error('Failed to send pre-order status email: ' . $e->getMessage());
                }

                $updatedCount++;
            } catch (\Exception $e) {
                $errors[] = "Failed to update pre-order ID {$id}: " . $e->getMessage();
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Successfully updated {$updatedCount} customer pre-orders",
            'updated_count' => $updatedCount,
            'errors' => $errors
        ], 200);
    }
}
