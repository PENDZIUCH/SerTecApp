<?php

namespace App\Services;

use App\Models\Part;
use App\Models\PartsMovement;

class PartService
{
    public function create(array $data)
    {
        return Part::create($data);
    }

    public function update(Part $part, array $data)
    {
        $part->update($data);
        return $part->fresh();
    }

    public function delete(Part $part)
    {
        return $part->delete();
    }

    public function addMovement(Part $part, string $type, int $quantity, array $additionalData = [])
    {
        $movement = $part->movements()->create([
            'movement_type' => $type,
            'quantity' => $quantity,
            'created_by' => auth()->id(),
            ...$additionalData,
        ]);

        $this->adjustStock($part, $type, $quantity);

        return $movement;
    }

    private function adjustStock(Part $part, string $type, int $quantity)
    {
        $currentStock = $part->stock_qty;

        switch ($type) {
            case 'in':
            case 'return':
                $newStock = $currentStock + $quantity;
                break;
            case 'out':
                $newStock = $currentStock - $quantity;
                break;
            case 'adjustment':
                $newStock = $quantity;
                break;
            default:
                $newStock = $currentStock;
        }

        $part->update(['stock_qty' => max(0, $newStock)]);
    }
}
