<?php

namespace App\Http\Controllers;

use App\Services\NotificationService;
use Illuminate\Http\Request;

class NotificationsController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function index()
    {
        $result = $this->notificationService->listNotifications();
        if (is_array($result) && isset($result['error'])) {
            return response()->json(['message' => $result['error']], 401);
        }
        return response()->json(['message' => 'notifications retrieved', 'notifications' => $result]);
    }

    public function unreadCount()
    {
        $count = $this->notificationService->unreadCount();
        return response()->json(['unread' => $count]);
    }

    public function markAsRead($id)
    {
        $result = $this->notificationService->markAsRead($id);
        if (is_array($result) && isset($result['error'])) {
            return response()->json(['message' => $result['error']], 404);
        }
        return response()->json(['message' => 'marked as read']);
    }

    public function markAllRead()
    {
        $this->notificationService->markAllRead();
        return response()->json(['message' => 'all notifications marked as read']);
    }
}
