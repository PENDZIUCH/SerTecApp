<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubscriptionRenewalHistory extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'subscription_id',
        'renewal_date',
        'previous_renewal_date',
        'visits_reset',
        'created_by',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'renewal_date' => 'date',
            'previous_renewal_date' => 'date',
            'visits_reset' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
