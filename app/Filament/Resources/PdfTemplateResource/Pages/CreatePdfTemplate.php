<?php

namespace App\Filament\Resources\PdfTemplateResource\Pages;

use App\Filament\Resources\PdfTemplateResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreatePdfTemplate extends CreateRecord
{
    protected static string $resource = PdfTemplateResource::class;
}
