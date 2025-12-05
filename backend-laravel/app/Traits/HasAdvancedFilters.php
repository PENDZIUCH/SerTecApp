<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

/**
 * Trait HasAdvancedFilters
 * 
 * Provides advanced filtering capabilities for API queries.
 * Supports search, status, date ranges, sorting, and more.
 * 
 * @package App\Traits
 */
trait HasAdvancedFilters
{
    /**
     * Apply advanced filters to a query.
     * 
     * @param Builder $query
     * @param array $filters
     * @return Builder
     */
    public function scopeApplyFilters(Builder $query, array $filters): Builder
    {
        // Search filter
        if (!empty($filters['search'])) {
            $searchTerm = '%' . $filters['search'] . '%';
            $query->where(function ($q) use ($searchTerm) {
                $searchableFields = $this->searchableFields ?? ['name', 'description'];
                
                foreach ($searchableFields as $field) {
                    $q->orWhere($field, 'like', $searchTerm);
                }
            });
        }

        // Status filter
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Date range filter (created_at)
        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        // Custom date field range
        if (!empty($filters['date_field']) && !empty($filters['date_field_from'])) {
            $query->whereDate($filters['date_field'], '>=', $filters['date_field_from']);
        }

        if (!empty($filters['date_field']) && !empty($filters['date_field_to'])) {
            $query->whereDate($filters['date_field'], '<=', $filters['date_field_to']);
        }

        // Sorting
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        
        if (in_array(strtolower($sortOrder), ['asc', 'desc'])) {
            $query->orderBy($sortBy, $sortOrder);
        }

        return $query;
    }

    /**
     * Scope for active records.
     * 
     * @param Builder $query
     * @return Builder
     */
    public function scopeActive(Builder $query): Builder
    {
        if (in_array('is_active', $this->fillable)) {
            return $query->where('is_active', true);
        }
        
        if (in_array('status', $this->fillable)) {
            return $query->where('status', 'active');
        }

        return $query;
    }
}
