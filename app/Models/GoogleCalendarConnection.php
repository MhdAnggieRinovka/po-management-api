<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class GoogleCalendarConnection extends Model
{
    protected $fillable = [
        'user_id', 'google_account_email', 'refresh_token', 'calendar_id',
        'sync_status', 'connected_at',
    ];

    protected function casts(): array
    {
        return ['connected_at' => 'datetime'];
    }

    public function setRefreshTokenAttribute(?string $value): void
    {
        $this->attributes['refresh_token'] = $value === null ? null : Crypt::encryptString($value);
    }

    public function getRefreshTokenAttribute(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (\Illuminate\Contracts\Encryption\DecryptException) {
            return null;
        }
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
