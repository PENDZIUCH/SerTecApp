<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WorkPartResource\Pages;
use App\Models\WorkPart;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use App\Mail\ParteRechazadoMail;
use Illuminate\Support\Facades\Mail;

class WorkPartResource extends Resource
{
    protected static ?string $model = WorkPart::class;
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static ?string $navigationLabel = 'Partes Pendientes';
    protected static ?int $navigationSort = 1;
    protected static ?string $modelLabel = 'Parte de Trabajo';
    protected static ?string $pluralModelLabel = 'Partes de Trabajo';

    // Sin grupo - nivel principal

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Información del Parte')
                ->schema([
                    Forms\Components\Select::make('work_order_id')
                        ->label('Orden de Trabajo')
                        ->relationship('workOrder', 'id')
                        ->disabled()
                        ->required(),
                    
                    Forms\Components\Select::make('technician_id')
                        ->label('Técnico')
                        ->relationship('technician', 'name')
                        ->disabled()
                        ->required(),
                    
                    Forms\Components\Textarea::make('diagnosis')
                        ->label('Diagnóstico')
                        ->disabled()
                        ->rows(3)
                        ->columnSpanFull(),
                    
                    Forms\Components\Textarea::make('work_done')
                        ->label('Trabajo Realizado')
                        ->disabled()
                        ->rows(3)
                        ->columnSpanFull(),
                ])->columns(2),
            
            Forms\Components\Section::make('Firma del Cliente')
                ->schema([
                    Forms\Components\View::make('filament.forms.signature-preview')
                        ->view('filament.forms.components.signature-preview'),
                ])
                ->visible(fn ($record) => $record && $record->signature),
            
            
            Forms\Components\Section::make('Ubicación del Parte')
                ->schema([
                    Forms\Components\Placeholder::make('mapa')
                        ->label('Dónde se completó')
                        ->content(function ($record) {
                            if (!$record || !$record->latitude || !$record->longitude) {
                                return 'Sin datos de ubicación';
                            }
                            $lat = $record->latitude;
                            $lng = $record->longitude;
                            $url = "https://www.google.com/maps?q={$lat},{$lng}";
                            return new \Illuminate\Support\HtmlString(
                                "<a href='{$url}' target='_blank' class='text-primary-600 hover:underline font-medium'>
                                    📍 Ver en Google Maps ({$lat}, {$lng})
                                </a>"
                            );
                        }),
                ])
                ->visible(fn ($record) => $record && $record->latitude),

            Forms\Components\Section::make('Supervisión')
                ->schema([
                    Forms\Components\Select::make('status')
                        ->label('Estado')
                        ->options([
                            'pending_approval' => 'Pendiente de Aprobación',
                            'approved' => 'Aprobado',
                            'rejected' => 'Rechazado',
                        ])
                        ->required(),
                    
                    Forms\Components\Textarea::make('supervisor_notes')
                        ->label('Notas del Supervisor')
                        ->rows(3)
                        ->columnSpanFull(),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('N° Parte')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('workOrder.id')
                    ->label('N° OT')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('workOrder.customer.business_name')
                    ->label('Cliente')
                    ->searchable()
                    ->limit(30),
                
                Tables\Columns\TextColumn::make('technician.name')
                    ->label('Técnico')
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('diagnosis')
                    ->label('Diagnóstico')
                    ->limit(40)
                    ->searchable(),
                
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Estado')
                    ->formatStateUsing(fn ($state) => match($state) {
                        'pending_approval' => 'Pendiente',
                        'approved' => 'Aprobado',
                        'rejected' => 'Rechazado',
                        default => $state,
                    })
                    ->colors([
                        'warning' => 'pending_approval',
                        'success' => 'approved',
                        'danger' => 'rejected',
                    ]),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'pending_approval' => 'Pendiente',
                        'approved' => 'Aprobado',
                        'rejected' => 'Rechazado',
                    ])
                    ->default('pending_approval'),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('Aprobar')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (WorkPart $record) => $record->status === 'pending_approval')
                    ->form([
                        Forms\Components\Textarea::make('supervisor_notes')
                            ->label('Notas para el técnico (opcional)')
                            ->placeholder('Trabajo bien realizado, todo correcto...')
                            ->rows(3),
                    ])
                    ->action(function (WorkPart $record, array $data) {
                        DB::transaction(function () use ($record, $data) {
                            $record->update([
                                'status' => 'approved',
                                'approved_at' => now(),
                                'supervisor_notes' => $data['supervisor_notes'] ?? null,
                            ]);
                            $record->workOrder->update([
                                'status' => 'completed',
                                'completed_at' => now(),
                            ]);
                        });

                        // Notificar al técnico
                        try {
                            $tecnico = $record->technician;
                            if ($tecnico) {
                                \Filament\Notifications\Notification::make()
                                    ->title('✅ Parte Aprobado')
                                    ->body('Tu parte de la Orden #' . $record->work_order_id . ' fue aprobado.' .
                                        ($data['supervisor_notes'] ? ' Nota: ' . $data['supervisor_notes'] : ''))
                                    ->success()
                                    ->sendToDatabase($tecnico);
                            }
                        } catch (\Exception $e) {}

                        Notification::make()
                            ->title('Parte Aprobado')
                            ->success()
                            ->send();
                    }),
                
                Tables\Actions\Action::make('reject')
                    ->label('Rechazar')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (WorkPart $record) => $record->status === 'pending_approval')
                    ->form([
                        Forms\Components\Textarea::make('supervisor_notes')
                            ->label('Motivo del rechazo (obligatorio)')
                            ->required()
                            ->placeholder('Indicar qué debe corregir el técnico...')
                            ->rows(3),
                        Forms\Components\Select::make('nuevo_tecnico_id')
                            ->label('Técnico asignado')
                            ->options(fn () => \App\Models\User::role('técnico')->pluck('name', 'id'))
                            ->default(fn (WorkPart $record) => $record->workOrder->assigned_tech_id)
                            ->searchable()
                            ->required(),
                    ])
                    ->action(function (WorkPart $record, array $data) {
                        DB::transaction(function () use ($record, $data) {
                            $record->update([
                                'status' => 'rejected',
                                'supervisor_notes' => $data['supervisor_notes'],
                            ]);
                            // La orden vuelve a pendiente
                            $orderData = ['status' => 'pending', 'completed_at' => null];
                            // Reasignar técnico si se seleccionó uno diferente
                            if (!empty($data['nuevo_tecnico_id'])) {
                                $orderData['assigned_tech_id'] = $data['nuevo_tecnico_id'];
                            }
                            $record->workOrder->update($orderData);
                        });

                        // Notificar al técnico
                        try {
                            $tecnico = $record->technician;
                            if ($tecnico) {
                                \Filament\Notifications\Notification::make()
                                    ->title('❌ Parte Rechazado')
                                    ->body('Tu parte de la Orden #' . $record->work_order_id . ' fue rechazado. Motivo: ' . $data['supervisor_notes'])
                                    ->danger()
                                    ->sendToDatabase($tecnico);
                            }
                        } catch (\Exception $e) {}

                        // Email al cliente
                        try {
                            $customerEmail = $record->workOrder->customer->email ?? null;
                            if ($customerEmail) {
                                $nuevoTecnico = !empty($data['nuevo_tecnico_id'])
                                    ? \App\Models\User::find($data['nuevo_tecnico_id'])?->name
                                    : $record->workOrder->assignedTech?->name;
                                Mail::to($customerEmail)->send(new ParteRechazadoMail($record->workOrder, $nuevoTecnico));
                            }
                        } catch (\Exception $e) {}

                        Notification::make()
                            ->title('Parte Rechazado — Orden volvió a Pendiente')
                            ->warning()
                            ->send();
                    }),
                
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWorkParts::route('/'),
            'view' => Pages\ViewWorkPart::route('/{record}'),
            'edit' => Pages\EditWorkPart::route('/{record}/edit'),
        ];
    }
    
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'pending_approval')->count();
    }
    
    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}
