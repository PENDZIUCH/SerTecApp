<?php

namespace App\Observers;

use App\Models\WoPartUsed;
use App\Models\PartsMovement;

class WoPartsUsedObserver
{
    public function created(WoPartUsed $woPartUsed): void
    {
        $part = $woPartUsed->part;
        $part->decrement('stock_quantity', $woPartUsed->quantity);
        
        PartsMovement::create([
            'part_id' => $woPartUsed->part_id,
            'movement_type' => 'out',
            'quantity' => $woPartUsed->quantity,
            'reference_type' => 'work_order',
            'reference_id' => $woPartUsed->work_order_id,
            'notes' => "Usado en OT #{$woPartUsed->work_order_id}",
            'user_id' => auth()->id(),
        ]);
    }
    
    public function updated(WoPartUsed $woPartUsed): void
    {
        if ($woPartUsed->isDirty('quantity')) {
            $oldQuantity = $woPartUsed->getOriginal('quantity');
            $newQuantity = $woPartUsed->quantity;
            $diff = $newQuantity - $oldQuantity;
            
            $part = $woPartUsed->part;
            $part->decrement('stock_quantity', $diff);
            
            PartsMovement::create([
                'part_id' => $woPartUsed->part_id,
                'movement_type' => $diff > 0 ? 'out' : 'in',
                'quantity' => abs($diff),
                'reference_type' => 'work_order',
                'reference_id' => $woPartUsed->work_order_id,
                'notes' => "Ajuste en OT #{$woPartUsed->work_order_id}",
                'user_id' => auth()->id(),
            ]);
        }
    }
    
    public function deleted(WoPartUsed $woPartUsed): void
    {
        $part = $woPartUsed->part;
        $part->increment('stock_quantity', $woPartUsed->quantity);
        
        PartsMovement::create([
            'part_id' => $woPartUsed->part_id,
            'movement_type' => 'in',
            'quantity' => $woPartUsed->quantity,
            'reference_type' => 'work_order',
            'reference_id' => $woPartUsed->work_order_id,
            'notes' => "Devuelto de OT #{$woPartUsed->work_order_id}",
            'user_id' => auth()->id(),
        ]);
    }
}
