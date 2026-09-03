<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'customer_type',
        'business_name',
        'first_name',
        'last_name',
        'email',
        'secondary_email',
        'phone',
        'tax_id',
        'address',
        'city',
        'state',
        'country',
        'postal_code',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    protected function fullName(): Attribute
    {
        return Attribute::make(
            get: fn() => match($this->customer_type) {
                'individual' => trim("{$this->first_name} {$this->last_name}"),
                default => $this->business_name,
            }
        );
    }

    protected function fullAddress(): Attribute
    {
        return Attribute::make(
            get: fn() => collect([
                $this->address,
                $this->city,
                $this->state,
                $this->postal_code,
                $this->country,
            ])->filter()->implode(', ')
        );
    }

    public function contacts()
    {
        return $this->hasMany(CustomerContact::class);
    }

    public function addresses()
    {
        return $this->hasMany(CustomerAddress::class);
    }

    public function files()
    {
        return $this->hasMany(CustomerFile::class);
    }

    public function notes()
    {
        return $this->hasMany(CustomerNote::class);
    }

    public function equipments()
    {
        return $this->hasMany(Equipment::class);
    }

    public function workOrders()
    {
        return $this->hasMany(WorkOrder::class);
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function budgets()
    {
        return $this->hasMany(Budget::class);
    }

    public function scopeActive($query)
    {
        return $this->where('is_active', true);
    }

    public function scopeType($query, $type)
    {
        return $query->where('customer_type', $type);
    }
}
