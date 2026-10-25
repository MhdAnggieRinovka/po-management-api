<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class PurchaseOrder extends Model
{
    public const STATUS_OPEN = 'OPEN';
    public const STATUS_PARTIALLY_RECEIVED = 'PARTIALLY_RECEIVED';
    public const STATUS_RECEIVED = 'RECEIVED';
    public const STATUS_CANCELLED = 'CANCELLED';

    protected $fillable = [
        'po_number', 'supplier_id', 'created_by', 'order_date',
        'expected_delivery_date', 'status', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'order_date' => 'date',
            'expected_delivery_date' => 'date',
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function calendarEvents(): HasMany
    {
        return $this->hasMany(GoogleCalendarEvent::class);
    }

    public function isOverdue(): bool
    {
        return $this->expected_delivery_date !== null
            && $this->expected_delivery_date->isPast()
            && ! in_array($this->status, [self::STATUS_RECEIVED, self::STATUS_CANCELLED], true);
    }
}
