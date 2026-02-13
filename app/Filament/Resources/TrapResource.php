<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TrapResource\Pages;
use App\Models\Trap;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TrapResource extends Resource
{
    protected static ?string $model = Trap::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-exclamation';

    public static function getNavigationGroup(): ?string
    {
        return __('traps.navigation_group');
    }

    protected static ?int $navigationSort = 1;

    /**
     * Module 3: Stealth Visibility — Hidden from navigation unless Level 10.
     */
    public static function canAccess(): bool
    {
        $user = auth()->user();
        return $user && ($user->is_super_admin || $user->security_level >= 10);
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public static function getNavigationLabel(): string
    {
        return __('traps.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('traps.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('traps.plural_model_label');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make(__('traps.trap_details'))
                ->schema([
                    Forms\Components\TextInput::make('name_ar')
                        ->required()
                        ->label(__('traps.name_ar'))
                        ->hintIcon('heroicon-m-information-circle', tooltip: 'اسم المصيدة الأمنية بالعربية'),

                    Forms\Components\TextInput::make('name_en')
                        ->required()
                        ->label(__('traps.name_en'))
                        ->hintIcon('heroicon-m-information-circle', tooltip: 'اسم المصيدة بالإنجليزية'),

                    Forms\Components\TextInput::make('trap_code')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->label(__('traps.trap_code'))
                        ->helperText(__('traps.trap_code_helper'))
                        ->hintIcon('heroicon-m-information-circle', tooltip: 'كود فريد يُميّز المصيدة في النظام'),

                    Forms\Components\Textarea::make('description_ar')
                        ->label(__('traps.description_ar'))
                        ->hintIcon('heroicon-m-information-circle', tooltip: 'وصف المصيدة بالعربية'),

                    Forms\Components\Textarea::make('description_en')
                        ->label(__('traps.description_en'))
                        ->hintIcon('heroicon-m-information-circle', tooltip: 'وصف المصيدة بالإنجليزية'),
                ])->columns(['default' => 1, 'lg' => 2]),

            Forms\Components\Section::make(__('traps.risk_config'))
                ->schema([
                    Forms\Components\TextInput::make('risk_weight')
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(10)
                        ->default(5)
                        ->required()
                        ->label(__('traps.risk_weight'))
                        ->hintIcon('heroicon-m-information-circle', tooltip: 'كلما زاد الرقم زادت خطورة التفاعل مع هذه المصيدة'),

                    Forms\Components\Select::make('fake_response_type')
                        ->options([
                            'success' => __('traps.response_types.success'),
                            'error'   => __('traps.response_types.error'),
                            'warning' => __('traps.response_types.warning'),
                        ])
                        ->default('success')
                        ->required()
                        ->label(__('traps.fake_response_type'))
                        ->hintIcon('heroicon-m-information-circle', tooltip: 'نوع الرسالة المزيفة التي تظهر للموظف عند التفاعل'),

                    Forms\Components\Toggle::make('is_active')
                        ->default(true)
                        ->label(__('traps.is_active'))
                        ->hintIcon('heroicon-m-information-circle', tooltip: 'هل المصيدة نشطة حالياً'),
                ])->columns(['default' => 1, 'lg' => 3]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('trap_code')
                    ->badge()
                    ->color('danger')
                    ->searchable()
                    ->label(__('traps.trap_code')),

                Tables\Columns\TextColumn::make('name_ar')
                    ->searchable()
                    ->label(__('traps.name_ar')),

                Tables\Columns\TextColumn::make('name_en')
                    ->searchable()
                    ->label(__('traps.name_en')),

                Tables\Columns\TextColumn::make('risk_weight')
                    ->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state <= 3  => 'success',
                        $state <= 6  => 'warning',
                        $state <= 8  => 'danger',
                        default      => 'danger',
                    })
                    ->label(__('traps.risk_weight')),

                Tables\Columns\TextColumn::make('fake_response_type')
                    ->badge()
                    ->label(__('traps.fake_response_type')),

                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label(__('traps.is_active')),

                Tables\Columns\TextColumn::make('interactions_count')
                    ->counts('interactions')
                    ->label(__('traps.total_triggers')),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label(__('traps.is_active')),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListTraps::route('/'),
            'create' => Pages\CreateTrap::route('/create'),
            'edit'   => Pages\EditTrap::route('/{record}/edit'),
            'view'   => Pages\ViewTrap::route('/{record}'),
        ];
    }
}
