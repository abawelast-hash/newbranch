<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ScoreAdjustmentResource\Pages;
use App\Models\ScoreAdjustment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * SARH v1.9.0 — Module 8: تعديلات النقاط/الدرجات اليدوية
 */
class ScoreAdjustmentResource extends Resource
{
    protected static ?string $model = ScoreAdjustment::class;

    protected static ?string $navigationIcon = 'heroicon-o-adjustments-horizontal';

    protected static ?int $navigationSort = 12;

    public static function getNavigationGroup(): ?string
    {
        return 'التقارير والتحليلات';
    }

    public static function getNavigationLabel(): string
    {
        return 'تعديلات النقاط';
    }

    public static function getModelLabel(): string
    {
        return 'تعديل نقاط';
    }

    public static function getPluralModelLabel(): string
    {
        return 'تعديلات النقاط';
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();
        return $user && ($user->is_super_admin || $user->security_level >= 10);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('نطاق التعديل')
                ->schema([
                    Forms\Components\Select::make('scope')
                        ->label('النطاق')
                        ->options([
                            'branch'     => 'فرع',
                            'user'       => 'موظف',
                            'department' => 'قسم',
                        ])
                        ->required()
                        ->live()
                        ->native(false)
                        ->hintIcon('heroicon-m-information-circle', tooltip: 'حدد ما إذا كان التعديل على فرع أو موظف أو قسم'),

                    Forms\Components\Select::make('branch_id')
                        ->label('الفرع')
                        ->relationship('branch', 'name_ar')
                        ->searchable()
                        ->preload()
                        ->visible(fn (Forms\Get $get) => $get('scope') === 'branch')
                        ->required(fn (Forms\Get $get) => $get('scope') === 'branch')
                        ->hintIcon('heroicon-m-information-circle', tooltip: 'الفرع الذي سيُطبَّق عليه التعديل'),

                    Forms\Components\Select::make('user_id')
                        ->label('الموظف')
                        ->relationship('user', 'name_ar')
                        ->searchable()
                        ->preload()
                        ->visible(fn (Forms\Get $get) => $get('scope') === 'user')
                        ->required(fn (Forms\Get $get) => $get('scope') === 'user')
                        ->hintIcon('heroicon-m-information-circle', tooltip: 'الموظف الذي سيُطبَّق عليه التعديل'),

                    Forms\Components\Select::make('department_id')
                        ->label('القسم')
                        ->relationship('department', 'name_ar')
                        ->searchable()
                        ->preload()
                        ->visible(fn (Forms\Get $get) => $get('scope') === 'department')
                        ->required(fn (Forms\Get $get) => $get('scope') === 'department')
                        ->hintIcon('heroicon-m-information-circle', tooltip: 'القسم الذي سيُطبَّق عليه التعديل'),
                ])->columns(['default' => 1, 'lg' => 2]),

            Forms\Components\Section::make('التعديل')
                ->schema([
                    Forms\Components\TextInput::make('points_delta')
                        ->label('تعديل النقاط')
                        ->numeric()
                        ->required()
                        ->helperText('موجب = إضافة، سالب = خصم')
                        ->prefix('±')
                        ->hintIcon('heroicon-m-information-circle', tooltip: 'العدد الذي سيُضاف أو يُخصم من رصيد النقاط'),

                    Forms\Components\TextInput::make('value_delta')
                        ->label('تعديل القيمة المالية (ريال)')
                        ->numeric()
                        ->default(0)
                        ->prefix('±')
                        ->hintIcon('heroicon-m-information-circle', tooltip: 'تعديل مالي إضافي إن وُجِد'),

                    Forms\Components\Select::make('category')
                        ->label('التصنيف')
                        ->options([
                            'manual'     => 'تعديل يدوي',
                            'bonus'      => 'مكافأة',
                            'penalty'    => 'خصم/جزاء',
                            'correction' => 'تصحيح',
                        ])
                        ->default('manual')
                        ->required()
                        ->hintIcon('heroicon-m-information-circle', tooltip: 'نوع التعديل: يدوي أو مكافأة أو خصم أو تصحيح'),

                    Forms\Components\Textarea::make('reason')
                        ->label('السبب')
                        ->required()
                        ->rows(3)
                        ->maxLength(500)
                        ->columnSpanFull()
                        ->hintIcon('heroicon-m-information-circle', tooltip: 'سبب إجراء هذا التعديل — مطلوب للتوثيق'),

                    Forms\Components\Hidden::make('adjusted_by')
                        ->default(fn () => auth()->id()),
                ])->columns(['default' => 1, 'lg' => 2]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('scope')
                    ->label('النطاق')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'branch'     => 'فرع',
                        'user'       => 'موظف',
                        'department' => 'قسم',
                        default      => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'branch'     => 'info',
                        'user'       => 'warning',
                        'department' => 'success',
                        default      => 'gray',
                    }),

                Tables\Columns\TextColumn::make('target_name')
                    ->label('الهدف')
                    ->searchable(false),

                Tables\Columns\TextColumn::make('points_delta')
                    ->label('النقاط')
                    ->badge()
                    ->color(fn (int $state): string => $state >= 0 ? 'success' : 'danger')
                    ->formatStateUsing(fn (int $state): string => ($state >= 0 ? '+' : '') . $state)
                    ->sortable(),

                Tables\Columns\TextColumn::make('value_delta')
                    ->label('المالي')
                    ->money('SAR')
                    ->sortable(),

                Tables\Columns\TextColumn::make('category')
                    ->label('التصنيف')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'manual'     => 'يدوي',
                        'bonus'      => 'مكافأة',
                        'penalty'    => 'خصم',
                        'correction' => 'تصحيح',
                        default      => $state,
                    }),

                Tables\Columns\TextColumn::make('reason')
                    ->label('السبب')
                    ->limit(40)
                    ->wrap(),

                Tables\Columns\TextColumn::make('adjustedByUser.name_ar')
                    ->label('بواسطة'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('التاريخ')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('scope')
                    ->options([
                        'branch'     => 'فرع',
                        'user'       => 'موظف',
                        'department' => 'قسم',
                    ]),
                Tables\Filters\SelectFilter::make('category')
                    ->options([
                        'manual'     => 'يدوي',
                        'bonus'      => 'مكافأة',
                        'penalty'    => 'خصم',
                        'correction' => 'تصحيح',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListScoreAdjustments::route('/'),
            'create' => Pages\CreateScoreAdjustment::route('/create'),
        ];
    }
}
