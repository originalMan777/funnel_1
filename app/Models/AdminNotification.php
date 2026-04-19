<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdminNotification extends Model
{
    use HasFactory;

    public const STATUS_INFO = 'info';
    public const STATUS_SUCCESS = 'success';
    public const STATUS_WARNING = 'warning';
    public const STATUS_ERROR = 'error';

    protected $fillable = [
        'type_key',
        'title',
        'message',
        'status',
        'priority',
        'is_read',
        'read_at',
        'link_url',
        'link_label',
        'source_type',
        'source_id',
        'metadata',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeNewest($query)
    {
        return $query->latest('created_at');
    }

    public function markAsRead(): void
    {
        if ($this->is_read) {
            return;
        }

        $this->forceFill([
            'is_read' => true,
            'read_at' => now(),
        ])->save();
    }
}
