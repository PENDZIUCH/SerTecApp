<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subscription extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'customer_id',
        'plan_name',
        'visits_per_period',
        'visits_used',
        'billing_cycle',
        'renewal_date',
        'grace_period_days',
        'auto_renew',
        'status',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'visits_per_period' => 'integer',
            'visits_used' => 'integer',
            'grace_period_days' => 'integer',
            'renewal_date' => 'date',
            'auto_renew' => 'boolean',
        ];
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function renewalHistory()
    {
        return $this->hasMany(SubscriptionRenewalHistory::class);
    }

    public function visits()
    {
        return $this->hasMany(Visit::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeExpiringSoon($query, $days = 30)
    {
        return $query->where('renewal_date', '<=', now()->addDays($days))
            ->where('status', 'active');
    }

    public function isExpired()
    {
        return $this->renewal_date->addDays($this->grace_period_days) < now();
    }

    public function hasVisitsAvailable()
    {
        return $this->visits_used < $this->visits_per_period;
    }
}
