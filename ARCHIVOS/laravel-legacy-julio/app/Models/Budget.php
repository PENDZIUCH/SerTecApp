<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Budget extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'customer_id',
        'title',
        'status',
        'valid_until',
        'tax_percent',
        'discount_type',
        'discount_value',
        'subtotal_services',
        'subtotal_parts',
        'subtotal_before_discount',
        'discount_amount',
        'subtotal_after_discount',
        'tax_amount',
        'total_amount',
        'notes',
        'created_by',
        'updated_by',
        'approved_by',
        'rejected_reason',
    ];

    protected function casts(): array
    {
        return [
            'valid_until' => 'date',
            'tax_percent' => 'decimal:2',
            'discount_value' => 'decimal:2',
            'subtotal_services' => 'decimal:2',
            'subtotal_parts' => 'decimal:2',
            'subtotal_before_discount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'subtotal_after_discount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
        ];
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function items()
    {
        return $this->hasMany(BudgetItem::class);
    }

    public function notes()
    {
        return $this->hasMany(BudgetNote::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function scopeStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeExpired($query)
    {
        return $query->where('valid_until', '<', now())
            ->where('status', 'sent');
    }

    public function isExpired()
    {
        return $this->valid_until && $this->valid_until < now() && $this->status === 'sent';
    }

    public function calculateTotals()
    {
        $this->subtotal_before_discount = $this->subtotal_services + $this->subtotal_parts;

        if ($this->discount_type === 'percent') {
            $this->discount_amount = ($this->subtotal_before_discount * $this->discount_value) / 100;
        } elseif ($this->discount_type === 'amount') {
            $this->discount_amount = $this->discount_value;
        } else {
            $this->discount_amount = 0;
        }

        $this->subtotal_after_discount = $this->subtotal_before_discount - $this->discount_amount;
        $this->tax_amount = ($this->subtotal_after_discount * $this->tax_percent) / 100;
        $this->total_amount = $this->subtotal_after_discount + $this->tax_amount;

        return $this;
    }
}
