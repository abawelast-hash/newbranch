<?php

namespace App\Filament\Resources;

use App\Filament\Resources\HolidayResource\Pages;
use App\Models\Branch;
use App\Models\Holiday;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class HolidayResource extends Resource
{
    protected static ?string $model = Holiday::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?int $navigationSort = 12;

    public static function getNavigationGroup(): ?string
    {
        return __('holidays.navigation_group');
    }

    public static function getNavigationLabel(): string
    {
        return __('holidays.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('holidays.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('holidays.plural_model_label');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make(__('holidays.section_details'))
                ->icon('heroicon-o-calendar-days')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('name_ar')
                        ->label(__('holidays.name_ar'))
                        ->required()
                        ->maxLength(255),

                    Forms\Components\TextInput::make('name_en')
                        ->label(__('holidays.name_en'))
                        ->maxLength(255),

                    Forms\Components\DatePicker::make('date')
                        ->label(__('holidays.date'))
                        ->required(),

                    Forms\Components\Select::make('type')
                        ->label(__('holidays.type'))
                        ->options([
                            'national'  => __('holidays.type_national'),
                            'religious' => __('holidays.type_religious'),
                            'company'   => __('holidays.type_company'),
                        ])
                        ->default('national')
                        ->required(),

                    Forms\Components\Toggle::make('is_recurring')
                        ->label(__('holidays.is_recurring'))
                        ->helperText(__('holidays.is_recurring_help'))
                        ->default(false),

                    Forms\Components\Select::make('branch_id')
                        ->label(__('holidays.branch'))
                        ->options(Branch::pluck('name_ar', 'id'))
                        ->searchable()
                        ->placeholder(__('holidays.all_branches'))
                        ->helperText(__('holidays.branch_help')),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name_ar')
                    ->label(__('holidays.name_ar'))
                    ->searchable(),

                Tables\Columns\TextColumn::make('date')
                    ->label(__('holidays.date'))
                    ->date('Y-m-d')
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('type')
                    ->label(__('holidays.type'))
                    ->colors([
                        'primary' => 'national',
                        'success' => 'religious',
                        'warning' => 'company',
                    ])
                    ->formatStateUsing(fn (string $state) => __("holidays.type_{$state}")),

                Tables\Columns\IconColumn::make('is_recurring')
                    ->label(__('holidays.is_recurring'))
                    ->boolean(),

                Tables\Columns\TextColumn::make('branch.name_ar')
                    ->label(__('holidays.branch'))
                    ->default(__('holidays.all_branches')),
            ])
            ->defaultSort('date', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label(__('holidays.type'))
                    ->options([
                        'national'  => __('holidays.type_national'),
                        'religious' => __('holidays.type_religious'),
                        'company'   => __('holidays.type_company'),
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListHolidays::route('/'),
            'create' => Pages\CreateHoliday::route('/create'),
            'edit'   => Pages\EditHoliday::route('/{record}/edit'),
        ];
    }
}
