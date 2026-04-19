<?php

namespace App\Services\Admin;

use App\Models\AdminNotification;
use Illuminate\Support\Collection;

class AdminNotificationService
{
    public function create(array $data): AdminNotification
    {
        return AdminNotification::query()->create([
            'type_key' => $data['type_key'],
            'title' => $data['title'],
            'message' => $data['message'],
            'status' => $data['status'] ?? AdminNotification::STATUS_INFO,
            'priority' => $data['priority'] ?? 100,
            'is_read' => (bool) ($data['is_read'] ?? false),
            'read_at' => $data['read_at'] ?? null,
            'link_url' => $data['link_url'] ?? null,
            'link_label' => $data['link_label'] ?? null,
            'source_type' => $data['source_type'] ?? null,
            'source_id' => $data['source_id'] ?? null,
            'metadata' => $data['metadata'] ?? null,
        ]);
    }

    public function latestQueue(int $limit = 4): Collection
    {
        return AdminNotification::query()
            ->newest()
            ->limit($limit)
            ->get();
    }

    public function unreadCount(): int
    {
        return AdminNotification::query()
            ->unread()
            ->count();
    }

    public function markAsRead(AdminNotification $notification): void
    {
        $notification->markAsRead();
    }

    public function markAllAsRead(): int
    {
        return AdminNotification::query()
            ->unread()
            ->update([
                'is_read' => true,
                'read_at' => now(),
                'updated_at' => now(),
            ]);
    }
}
