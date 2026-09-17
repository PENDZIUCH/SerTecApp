<?php

namespace App\Filament\Resources\LookupValueResource\Pages;

use App\Filament\Resources\LookupValueResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageLookupValues extends ManageRecords
{
    protected static string $resource = LookupValueResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
