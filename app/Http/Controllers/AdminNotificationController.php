<?php

namespace App\Http\Controllers;

use App\Models\AdminNotification;
use App\Services\AdminNotificationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AdminNotificationController extends Controller
{
    protected $notificationService;

    public function __construct(AdminNotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Get all admin notifications with filtering and pagination
     */
    public function index(Request $request): JsonResponse
    {
        $query = AdminNotification::orderBy('created_at', 'desc');

        // Filter by read/unread status
        if ($request->has('status')) {
            if ($request->status === 'unread') {
                $query->unread();
            } elseif ($request->status === 'read') {
                $query->read();
            }
        }

        // Filter by type
        if ($request->has('type')) {
            $query->ofType($request->type);
        }

        // Filter by priority
        if ($request->has('priority')) {
            $query->byPriority($request->priority);
        }

        // Filter by date range
        if ($request->has('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }

        if ($request->has('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        // Pagination
        $perPage = $request->get('per_page', 20);
        $notifications = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $notifications,
            'message' => 'Notifications retrieved successfully'
        ]);
    }

    /**
     * Get notification statistics
     */
    public function getStats(): JsonResponse
    {
        $stats = $this->notificationService->getNotificationStats();

        // Add additional stats
        $stats['recent_activity'] = AdminNotification::orderBy('created_at', 'desc')
            ->limit(5)
            ->get(['id', 'type', 'title', 'created_at', 'priority', 'icon']);

        $stats['by_type'] = AdminNotification::selectRaw('type, COUNT(*) as count')
            ->groupBy('type')
            ->get()
            ->pluck('count', 'type');

        return response()->json([
            'success' => true,
            'data' => $stats,
            'message' => 'Notification statistics retrieved successfully'
        ]);
    }

    /**
     * Get unread notifications count
     */
    public function getUnreadCount(): JsonResponse
    {
        $count = AdminNotification::unread()->count();

        return response()->json([
            'success' => true,
            'data' => ['count' => $count],
            'message' => 'Unread count retrieved successfully'
        ]);
    }

    /**
     * Get recent notifications (for header/dropdown)
     */
    public function getRecent(Request $request): JsonResponse
    {
        $limit = $request->get('limit', 10);
        
        $notifications = AdminNotification::orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $notifications,
            'message' => 'Recent notifications retrieved successfully'
        ]);
    }

    /**
     * Mark a specific notification as read
     */
    public function markAsRead(AdminNotification $notification): JsonResponse
    {
        $notification->markAsRead();

        return response()->json([
            'success' => true,
            'data' => $notification->fresh(),
            'message' => 'Notification marked as read'
        ]);
    }

    /**
     * Mark a specific notification as unread
     */
    public function markAsUnread(AdminNotification $notification): JsonResponse
    {
        $notification->markAsUnread();

        return response()->json([
            'success' => true,
            'data' => $notification->fresh(),
            'message' => 'Notification marked as unread'
        ]);
    }

    /**
     * Mark multiple notifications as read
     */
    public function markMultipleAsRead(Request $request): JsonResponse
    {
        $request->validate([
            'notification_ids' => 'required|array',
            'notification_ids.*' => 'exists:admin_notifications,id'
        ]);

        $updatedCount = $this->notificationService->markMultipleAsRead($request->notification_ids);

        return response()->json([
            'success' => true,
            'data' => ['updated_count' => $updatedCount],
            'message' => "{$updatedCount} notifications marked as read"
        ]);
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead(): JsonResponse
    {
        $updatedCount = $this->notificationService->markAllAsRead();

        return response()->json([
            'success' => true,
            'data' => ['updated_count' => $updatedCount],
            'message' => "All {$updatedCount} notifications marked as read"
        ]);
    }

    /**
     * Delete a specific notification
     */
    public function destroy(AdminNotification $notification): JsonResponse
    {
        $notification->delete();

        return response()->json([
            'success' => true,
            'message' => 'Notification deleted successfully'
        ]);
    }

    /**
     * Delete multiple notifications
     */
    public function deleteMultiple(Request $request): JsonResponse
    {
        $request->validate([
            'notification_ids' => 'required|array',
            'notification_ids.*' => 'exists:admin_notifications,id'
        ]);

        $deletedCount = AdminNotification::whereIn('id', $request->notification_ids)->delete();

        return response()->json([
            'success' => true,
            'data' => ['deleted_count' => $deletedCount],
            'message' => "{$deletedCount} notifications deleted successfully"
        ]);
    }

    /**
     * Delete old notifications
     */
    public function deleteOld(Request $request): JsonResponse
    {
        $days = $request->get('days', 30);
        $deletedCount = $this->notificationService->deleteOldNotifications($days);

        return response()->json([
            'success' => true,
            'data' => ['deleted_count' => $deletedCount],
            'message' => "Deleted {$deletedCount} notifications older than {$days} days"
        ]);
    }

    /**
     * Get notification details
     */
    public function show(AdminNotification $notification): JsonResponse
    {
        // Mark as read when viewed
        if (!$notification->is_read) {
            $notification->markAsRead();
        }

        return response()->json([
            'success' => true,
            'data' => $notification,
            'message' => 'Notification details retrieved successfully'
        ]);
    }

    /**
     * Get notifications grouped by type
     */
    public function getByType(): JsonResponse
    {
        $notifications = AdminNotification::orderBy('created_at', 'desc')
            ->get()
            ->groupBy('type');

        return response()->json([
            'success' => true,
            'data' => $notifications,
            'message' => 'Notifications grouped by type retrieved successfully'
        ]);
    }

    /**
     * Test notification creation (for development)
     */
    public function createTestNotification(Request $request): JsonResponse
    {
        if (!app()->environment('local')) {
            return response()->json([
                'success' => false,
                'message' => 'Test notifications only available in development'
            ], 403);
        }

        $notification = $this->notificationService->createNotification([
            'type' => 'test',
            'title' => 'Test Notification',
            'message' => 'This is a test notification created at ' . now()->format('Y-m-d H:i:s'),
            'priority' => $request->get('priority', 'normal'),
            'action_url' => '/admin/test'
        ]);

        return response()->json([
            'success' => true,
            'data' => $notification,
            'message' => 'Test notification created successfully'
        ]);
    }
}




