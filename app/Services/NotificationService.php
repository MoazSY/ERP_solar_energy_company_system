<?php
namespace App\Services;

use Illuminate\Support\Facades\Auth;

class NotificationService
{
    protected function getNotifiable()
    {
        // check guards in order
        $guards = ['admin', 'company_manager', 'agency_manager', 'employee', 'customer'];
        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                return Auth::guard($guard)->user();
            }
        }

        // fallback to default authenticated user (sanctum)
        if (Auth::check()) {
            return Auth::user();
        }

        return null;
    }

    public function listNotifications()
    {
        $user = $this->getNotifiable();
        if (!$user) {
            return ['error' => 'Unauthorized'];
        }
        return $user->notifications()->orderBy('created_at', 'desc')->get();
    }

    public function unreadCount()
    {
        $user = $this->getNotifiable();
        if (!$user) {
            return 0;
        }
        return $user->unreadNotifications()->count();
    }

    public function markAsRead($id)
    {
        $user = $this->getNotifiable();
        if (!$user) {
            return ['error' => 'Unauthorized'];
        }
        $notification = $user->unreadNotifications()->where('id', $id)->first();
        if (!$notification) {
            return ['error' => 'Notification not found'];
        }
        $notification->markAsRead();
        return ['ok' => true];
    }

    public function markAllRead()
    {
        $user = $this->getNotifiable();
        if (!$user) {
            return ['error' => 'Unauthorized'];
        }
        foreach ($user->unreadNotifications as $n) {
            $n->markAsRead();
        }
        return ['ok' => true];
    }
}
