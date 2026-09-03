<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SignatureToken extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'work_order_id',
        'token',
        'expires_at',
        'signed_by_name',
        'signed_by_email',
        'signed_by_phone',
        'signed_at',
        'signature_image_path',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'signed_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function workOrder()
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<', now())
            ->whereNull('signed_at');
    }

    public function scopeActive($query)
    {
        return $query->where('expires_at', '>', now())
            ->whereNull('signed_at');
    }

    public function isExpired()
    {
        return $this->expires_at < now() && is_null($this->signed_at);
    }

    public function isSigned()
    {
        return !is_null($this->signed_at);
    }

    public static function generate($workOrderId, $expiresInHours = 72)
    {
        return static::create([
            'work_order_id' => $workOrderId,
            'token' => Str::random(64),
            'expires_at' => now()->addHours($expiresInHours),
            'created_at' => now(),
        ]);
    }
}
