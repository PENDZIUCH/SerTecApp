<?php

namespace App\Filament\Resources\PdfTemplateResource\Pages;

use App\Filament\Resources\PdfTemplateResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPdfTemplate extends EditRecord
{
    protected static string $resource = PdfTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
