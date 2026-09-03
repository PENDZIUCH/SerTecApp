<?php

namespace App\Services;

use App\Models\Budget;

class BudgetService
{
    public function create(array $data)
    {
        $data['created_by'] = auth()->id();
        
        $budget = Budget::create($data);
        $budget->calculateTotals()->save();
        
        return $budget;
    }

    public function update(Budget $budget, array $data)
    {
        $data['updated_by'] = auth()->id();
        
        $budget->update($data);
        $budget->calculateTotals()->save();
        
        return $budget->fresh();
    }

    public function delete(Budget $budget)
    {
        return $budget->delete();
    }

    public function addItem(Budget $budget, array $data)
    {
        $item = $budget->items()->create($data);
        
        $this->recalculateTotals($budget);
        
        return $item;
    }

    public function approve(Budget $budget)
    {
        $budget->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
        ]);

        return $budget;
    }

    public function reject(Budget $budget, string $reason)
    {
        $budget->update([
            'status' => 'rejected',
            'rejected_reason' => $reason,
        ]);

        return $budget;
    }

    private function recalculateTotals(Budget $budget)
    {
        $items = $budget->items;
        
        $budget->subtotal_services = $items->where('item_type', 'service')->sum('total');
        $budget->subtotal_parts = $items->where('item_type', 'part')->sum('total');
        
        $budget->calculateTotals()->save();
    }
}
