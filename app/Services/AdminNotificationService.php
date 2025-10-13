<?php

namespace App\Services;

use App\Models\AdminNotification;
use App\Models\User;
use App\Models\Order;
use App\Models\CustomerPreOrder;

class AdminNotificationService
{
    /**
     * Create a new admin notification
     */
    public function createNotification(array $data): AdminNotification
    {
        return AdminNotification::create([
            'type' => $data['type'],
            'title' => $data['title'],
            'message' => $data['message'],
            'data' => $data['data'] ?? null,
            'action_url' => $data['action_url'] ?? null,
            'related_id' => $data['related_id'] ?? null,
            'related_type' => $data['related_type'] ?? null,
            'priority' => $data['priority'] ?? 'normal',
            'icon' => $data['icon'] ?? $this->getDefaultIcon($data['type']),
        ]);
    }

    /**
     * Notify admin about new user registration
     */
    public function notifyUserRegistration(User $user): AdminNotification
    {
        return $this->createNotification([
            'type' => 'user_registration',
            'title' => 'New User Registration',
            'message' => "New customer {$user->name} ({$user->email}) has registered to the platform.",
            'data' => [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'user_email' => $user->email,
                'registration_date' => $user->created_at->format('M d, Y H:i'),
            ],
            'action_url' => "/admin/customers/{$user->id}",
            'related_id' => $user->id,
            'related_type' => User::class,
            'priority' => 'normal',
        ]);
    }

    /**
     * Notify admin about new product order
     */
    public function notifyNewOrder(Order $order): AdminNotification
    {
        $itemsCount = $order->orderItems->sum('quantity');
        
        return $this->createNotification([
            'type' => 'new_order',
            'title' => 'New Product Order',
            'message' => "New order #{$order->order_number} placed by {$order->first_name} {$order->last_name} for ₦" . number_format($order->total_amount, 2) . " ({$itemsCount} items).",
            'data' => [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'customer_name' => $order->first_name . ' ' . $order->last_name,
                'customer_email' => $order->customer_email,
                'total_amount' => $order->total_amount,
                'items_count' => $itemsCount,
                'fulfillment_method' => $order->fulfillment_method,
            ],
            'action_url' => "/admin/orders/{$order->id}",
            'related_id' => $order->id,
            'related_type' => Order::class,
            'priority' => 'high',
        ]);
    }

    /**
     * Notify admin about new pre-order
     */
    public function notifyNewPreOrder(CustomerPreOrder $preOrder): AdminNotification
    {
        return $this->createNotification([
            'type' => 'new_pre_order',
            'title' => 'New Pre-Order Placed',
            'message' => "New pre-order #{$preOrder->pre_order_number} placed by {$preOrder->full_name} for {$preOrder->preOrder->product_name} - ₦" . number_format($preOrder->total_amount, 2),
            'data' => [
                'pre_order_id' => $preOrder->id,
                'pre_order_number' => $preOrder->pre_order_number,
                'customer_name' => $preOrder->full_name,
                'customer_email' => $preOrder->customer_email,
                'product_name' => $preOrder->preOrder->product_name,
                'quantity' => $preOrder->quantity,
                'total_amount' => $preOrder->total_amount,
                'deposit_amount' => $preOrder->deposit_amount,
            ],
            'action_url' => "/admin/pre-orders/{$preOrder->id}",
            'related_id' => $preOrder->id,
            'related_type' => CustomerPreOrder::class,
            'priority' => 'high',
        ]);
    }

    /**
     * Notify admin about order payment completion
     */
    public function notifyOrderPaymentCompleted(Order $order): AdminNotification
    {
        return $this->createNotification([
            'type' => 'order_payment_completed',
            'title' => 'Order Payment Completed',
            'message' => "Payment for order #{$order->order_number} has been completed. Amount: ₦" . number_format($order->total_amount, 2),
            'data' => [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'customer_name' => $order->first_name . ' ' . $order->last_name,
                'customer_email' => $order->customer_email,
                'total_amount' => $order->total_amount,
                'payment_method' => $order->payment_method ?? 'N/A',
            ],
            'action_url' => "/admin/orders/{$order->id}",
            'related_id' => $order->id,
            'related_type' => Order::class,
            'priority' => 'high',
        ]);
    }

    /**
     * Notify admin about pre-order deposit payment
     */
    public function notifyPreOrderDepositPaid(CustomerPreOrder $preOrder): AdminNotification
    {
        return $this->createNotification([
            'type' => 'pre_order_deposit_paid',
            'title' => 'Pre-Order Deposit Paid',
            'message' => "Deposit payment for pre-order #{$preOrder->pre_order_number} has been received. Amount: ₦" . number_format($preOrder->deposit_amount, 2),
            'data' => [
                'pre_order_id' => $preOrder->id,
                'pre_order_number' => $preOrder->pre_order_number,
                'customer_name' => $preOrder->full_name,
                'customer_email' => $preOrder->customer_email,
                'product_name' => $preOrder->preOrder->product_name,
                'deposit_amount' => $preOrder->deposit_amount,
                'remaining_amount' => $preOrder->remaining_amount,
            ],
            'action_url' => "/admin/pre-orders/{$preOrder->id}",
            'related_id' => $preOrder->id,
            'related_type' => CustomerPreOrder::class,
            'priority' => 'normal',
        ]);
    }

    /**
     * Notify admin about pre-order full payment completion
     */
    public function notifyPreOrderFullyPaid(CustomerPreOrder $preOrder): AdminNotification
    {
        return $this->createNotification([
            'type' => 'pre_order_fully_paid',
            'title' => 'Pre-Order Fully Paid',
            'message' => "Pre-order #{$preOrder->pre_order_number} has been fully paid. Total: ₦" . number_format($preOrder->total_amount, 2),
            'data' => [
                'pre_order_id' => $preOrder->id,
                'pre_order_number' => $preOrder->pre_order_number,
                'customer_name' => $preOrder->full_name,
                'customer_email' => $preOrder->customer_email,
                'product_name' => $preOrder->preOrder->product_name,
                'total_amount' => $preOrder->total_amount,
                'payment_method' => $preOrder->payment_method ?? 'N/A',
            ],
            'action_url' => "/admin/pre-orders/{$preOrder->id}",
            'related_id' => $preOrder->id,
            'related_type' => CustomerPreOrder::class,
            'priority' => 'high',
        ]);
    }

    /**
     * Notify admin about order status changes
     */
    public function notifyOrderStatusChanged(Order $order, string $oldStatus, string $newStatus): AdminNotification
    {
        return $this->createNotification([
            'type' => 'order_status_changed',
            'title' => 'Order Status Updated',
            'message' => "Order #{$order->order_number} status changed from '{$oldStatus}' to '{$newStatus}'",
            'data' => [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'customer_name' => $order->first_name . ' ' . $order->last_name,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'total_amount' => $order->total_amount,
            ],
            'action_url' => "/admin/orders/{$order->id}",
            'related_id' => $order->id,
            'related_type' => Order::class,
            'priority' => 'normal',
        ]);
    }

    /**
     * Notify admin about email verification
     */
    public function notifyUserEmailVerified(User $user): AdminNotification
    {
        return $this->createNotification([
            'type' => 'user_email_verified',
            'title' => 'User Email Verified',
            'message' => "Customer {$user->name} ({$user->email}) has verified their email address.",
            'data' => [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'user_email' => $user->email,
                'verified_at' => $user->email_verified_at->format('M d, Y H:i'),
            ],
            'action_url' => "/admin/customers/{$user->id}",
            'related_id' => $user->id,
            'related_type' => User::class,
            'priority' => 'low',
        ]);
    }

    /**
     * Get default icon for notification type
     */
    private function getDefaultIcon(string $type): string
    {
        return match($type) {
            'user_registration' => '👤',
            'user_email_verified' => '✅',
            'new_order' => '🛒',
            'new_pre_order' => '💎',
            'order_payment_completed' => '💰',
            'pre_order_deposit_paid' => '💵',
            'pre_order_fully_paid' => '💳',
            'order_status_changed' => '📦',
            default => '🔔',
        };
    }

    /**
     * Get notification statistics
     */
    public function getNotificationStats(): array
    {
        return [
            'total' => AdminNotification::count(),
            'unread' => AdminNotification::unread()->count(),
            'today' => AdminNotification::whereDate('created_at', today())->count(),
            'high_priority' => AdminNotification::unread()->byPriority('high')->count(),
        ];
    }

    /**
     * Mark multiple notifications as read
     */
    public function markMultipleAsRead(array $ids): int
    {
        return AdminNotification::whereIn('id', $ids)->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead(): int
    {
        return AdminNotification::unread()->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
    }

    /**
     * Delete old notifications (older than specified days)
     */
    public function deleteOldNotifications(int $days = 30): int
    {
        return AdminNotification::where('created_at', '<', now()->subDays($days))->delete();
    }
}