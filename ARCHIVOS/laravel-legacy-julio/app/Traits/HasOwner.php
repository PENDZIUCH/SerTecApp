<?php

namespace App\Traits;

use App\Models\User;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Builder;

/**
 * Trait HasOwner
 * 
 * Provides ownership tracking and scoping for models.
 * Supports both user_id and customer_id ownership patterns.
 * 
 * @package App\Traits
 */
trait HasOwner
{
    /**
     * Check if model is owned by the given user.
     * 
     * @param User|int $user
     * @return bool
     */
    public function isOwnedBy(User|int $user): bool
    {
        $userId = $user instanceof User ? $user->id : $user;

        // Check user_id ownership
        if (isset($this->user_id)) {
            return $this->user_id === $userId;
        }

        // Check created_by ownership
        if (isset($this->created_by)) {
            return $this->created_by === $userId;
        }

        return false;
    }

    /**
     * Check if model belongs to the given customer.
     * 
     * @param Customer|int $customer
     * @return bool
     */
    public function belongsToCustomer(Customer|int $customer): bool
    {
        $customerId = $customer instanceof Customer ? $customer->id : $customer;

        return isset($this->customer_id) && $this->customer_id === $customerId;
    }

    /**
     * Scope query to records owned by a specific user.
     * 
     * @param Builder $query
     * @param User|int $user
     * @return Builder
     */
    public function scopeOwnedBy(Builder $query, User|int $user): Builder
    {
        $userId = $user instanceof User ? $user->id : $user;

        // Try user_id first
        if (in_array('user_id', $this->fillable)) {
            return $query->where('user_id', $userId);
        }

        // Fallback to created_by
        if (in_array('created_by', $this->fillable)) {
            return $query->where('created_by', $userId);
        }

        return $query;
    }

    /**
     * Scope query to records for a specific customer.
     * 
     * @param Builder $query
     * @param Customer|int $customer
     * @return Builder
     */
    public function scopeForCustomer(Builder $query, Customer|int $customer): Builder
    {
        $customerId = $customer instanceof Customer ? $customer->id : $customer;

        return $query->where('customer_id', $customerId);
    }

    /**
     * Get the owner user of this model.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo|null
     */
    public function owner()
    {
        if (in_array('user_id', $this->fillable)) {
            return $this->belongsTo(User::class, 'user_id');
        }

        if (in_array('created_by', $this->fillable)) {
            return $this->belongsTo(User::class, 'created_by');
        }

        return null;
    }

    /**
     * Get the customer this model belongs to.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo|null
     */
    public function customer()
    {
        if (in_array('customer_id', $this->fillable)) {
            return $this->belongsTo(Customer::class, 'customer_id');
        }

        return null;
    }
}
