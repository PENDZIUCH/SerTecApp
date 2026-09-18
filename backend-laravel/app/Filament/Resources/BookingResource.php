<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BookingResource\Pages;
use App\Models\Booking;
use App\Models\User;
use App\Models\WorkOrder;
use App\Services\ModuleManager;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * UI de Filament para el motor de agenda GENERICO (App\Models\Booking).
 * La clase/tabla es generica a proposito (no "TechnicianSchedule"), pero
 * este formulario particular esta armado para el primer consumidor real:
 * resource=tecnico (App\Models\User), subject=orden de trabajo
 * (App\Models\WorkOrder). Un futuro caso de uso (mesas, clases, delivery)
 * agregaria su propio Resource/form sobre el mismo modelo Booking, sin
 * tocar este archivo.
 */
class BookingResource extends Resource
{
    public static function canAccess(): bool
    {
        // Reusa el mismo flag 'visits' ya existente - la agenda es una
        // extension del mismo modulo, no un modulo nuevo.
        return ModuleManager::isActive('visits');
    }

    protected static ?string $model = Booking::class;
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationLabel = 'Agenda';
    protected static ?string $modelLabel = 'Reserva';
    protected static ?string $pluralModelLabel = 'Agenda';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Hidden::make('resource_type')->default(User::class),
            Forms\Components\Select::make('resource_id')
                ->label('Técnico')
                ->options(fn () => User::query()->role(['técnico', 'tecnico'])->pluck('name', 'id'))
                ->searchable()
                ->required(),

            Forms\Components\Hidden::make('subject_type')->default(WorkOrder::class),
            Forms\Components\Select::make('subject_id')
                ->label('Orden de Trabajo')
                ->options(fn () => WorkOrder::query()->pluck('wo_number', 'id'))
                ->searchable()
                ->required(),

            Forms\Components\DateTimePicker::make('starts_at')->label('Inicio')->required(),
            Forms\Components\DateTimePicker::make('ends_at')->label('Fin'),

            Forms\Components\Select::make('status')->label('Estado')
                ->options([
                    'scheduled' => 'Programada',
                    'in_progress' => 'En Progreso',
                    'completed' => 'Completada',
                    'cancelled' => 'Cancelada',
                    'no_show' => 'No se presentó',
                ])
                ->default('scheduled')
                ->required(),

            Forms\Components\Textarea::make('notes')->label('Notas')->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('starts_at')->label('Inicio')->dateTime()->sortable(),
                Tables\Columns\TextColumn::make('resourceLabel')->label('Técnico')
                    ->state(fn (Booking $record) => $record->resource_type === User::class
                        ? optional(User::find($record->resource_id))->name
                        : $record->resource_type . ' #' . $record->resource_id),
                Tables\Columns\TextColumn::make('subjectLabel')->label('Orden')
                    ->state(fn (Booking $record) => $record->subject_type === WorkOrder::class
                        ? optional(WorkOrder::find($record->subject_id))->wo_number
                        : $record->subject_type . ' #' . $record->subject_id),
                Tables\Columns\BadgeColumn::make('status')->label('Estado')
                    ->colors([
                        'gray' => 'scheduled',
                        'warning' => 'in_progress',
                        'success' => 'completed',
                        'danger' => ['cancelled', 'no_show'],
                    ]),
            ])
            ->defaultSort('starts_at', 'asc')
            ->filters([
                SelectFilter::make('resource_id')
                    ->label('Técnico')
                    ->options(fn () => User::query()->role(['técnico', 'tecnico'])->pluck('name', 'id'))
                    ->query(fn (Builder $query, array $data) => $query
                        ->when($data['value'] ?? null, fn ($q, $value) => $q
                            ->where('resource_type', User::class)
                            ->where('resource_id', $value))),

                Filter::make('range')
                    ->label('Rango de fechas')
                    ->form([
                        Forms\Components\Select::make('range')
                            ->label('Rango')
                            ->options(['today' => 'Hoy', 'week' => 'Esta semana'])
                            ->placeholder('Todas'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        return match ($data['range'] ?? null) {
                            'today' => $query->whereDate('starts_at', today()),
                            'week' => $query->whereBetween('starts_at', [now()->startOfWeek(), now()->endOfWeek()]),
                            default => $query,
                        };
                    }),
            ])
            ->actions([Tables\Actions\EditAction::make()])
            ->bulkActions([Tables\Actions\DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBookings::route('/'),
            'create' => Pages\CreateBooking::route('/create'),
            'edit' => Pages\EditBooking::route('/{record}/edit'),
        ];
    }
}
