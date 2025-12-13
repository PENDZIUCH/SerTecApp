<?php

namespace App\Filament\Resources\PartResource\Pages;

use App\Filament\Resources\PartResource;
use App\Models\Part;
use App\Models\EquipmentModel;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;

class ListParts extends ListRecords
{
    protected static string $resource = PartResource::class;
    protected ?string $heading = 'Repuestos';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->color('success'),
            
            Actions\Action::make('import_life_fitness')
                ->label('Importar Life Fitness')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('warning')
                ->visible(fn () => auth()->user()->hasRole('admin'))
                ->form([
                    FileUpload::make('file')
                        ->label('Archivo Excel')
                        ->required()
                        ->acceptedFileTypes(['application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])
                        ->disk('local')
                        ->directory('imports'),
                ])
                ->action(function (array $data) {
                    try {
                        $filePath = Storage::disk('local')->path($data['file']);
                        $rows = Excel::toArray([], $filePath)[0];
                        
                        if (empty($rows)) {
                            Notification::make()->title('Error')->body('Archivo vacío')->danger()->send();
                            return;
                        }
                        
                        $headers = array_map('strtolower', array_map('trim', $rows[1]));
                        $imported = 0;
                        $updated = 0;
                        
                        for ($i = 2; $i < count($rows); $i++) {
                            $row = $rows[$i];
                            if (empty(array_filter($row))) continue;
                            
                            $rowData = array_combine($headers, $row);
                            
                            $modelName = $this->getColumnValue($rowData, ['modelo']);
                            $partNumber = $this->getColumnValue($rowData, ['n° de parte', 'nº de parte']);
                            $description = $this->getColumnValue($rowData, ['español']);
                            $location = $this->getColumnValue($rowData, ['estante']);
                            $stock = (int)($this->getColumnValue($rowData, ['stock']) ?? 0);
                            $fobPrice = (float)($this->getColumnValue($rowData, ['unid. fob u$s']) ?? 0);
                            
                            if (empty($partNumber)) continue;
                            
                            $equipmentModel = null;
                            if ($modelName) {
                                $equipmentModel = EquipmentModel::firstOrCreate(
                                    ['name' => $modelName],
                                    ['brand_id' => 1]
                                );
                            }
                            
                            $part = Part::where('part_number', $partNumber)->first();
                            
                            if ($part) {
                                $part->update([
                                    'name' => $description ?? $part->name,
                                    'description' => $description,
                                    'equipment_model_id' => $equipmentModel?->id,
                                    'location' => $location,
                                    'fob_price_usd' => $fobPrice,
                                    'sale_price_usd' => $fobPrice ? round($fobPrice * 1.20, 2) : null,
                                ]);
                                $updated++;
                            } else {
                                Part::create([
                                    'part_number' => $partNumber,
                                    'name' => $description ?? $partNumber,
                                    'description' => $description,
                                    'equipment_model_id' => $equipmentModel?->id,
                                    'stock_quantity' => $stock,
                                    'location' => $location,
                                    'fob_price_usd' => $fobPrice,
                                    'markup_percent' => 20,
                                    'sale_price_usd' => $fobPrice ? round($fobPrice * 1.20, 2) : null,
                                ]);
                                $imported++;
                            }
                        }
                        
                        Storage::disk('local')->delete($data['file']);
                        
                        Notification::make()
                            ->title('Importación completada')
                            ->body("✅ {$imported} nuevos | 🔄 {$updated} actualizados")
                            ->success()
                            ->persistent()
                            ->send();
                            
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Error')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }
    
    private function getColumnValue(array $row, array $possibleNames): ?string
    {
        foreach ($possibleNames as $name) {
            $normalized = strtolower(trim($name));
            foreach ($row as $key => $value) {
                if (strtolower(trim($key)) === $normalized && !empty(trim($value))) {
                    return trim($value);
                }
            }
        }
        return null;
    }
}
