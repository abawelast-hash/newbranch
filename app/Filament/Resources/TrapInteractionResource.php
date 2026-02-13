<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TrapInteractionResource\Pages;
use App\Models\TrapInteraction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Illuminate\Database\Eloquent\Builder;

class TrapInteractionResource extends Resource
{
    protected static ?string $model = TrapInteraction::class;

    protected static ?string $navigationIcon = 'heroicon-o-eye';

    public static function getNavigationGroup(): ?string
    {
        return __('traps.navigation_group');
    }

    protected static ?int $navigationSort = 2;

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
        return __('traps.interactions_label');
    }

    public static function getModelLabel(): string
    {
        return __('traps.interaction_model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('traps.interaction_plural_label');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make(__('traps.interaction_details'))
                ->schema([
                    Forms\Components\Select::make('user_id')
                        ->relationship('user', 'name_ar')
                        ->searchable()
                        ->preload()
                        ->disabled()
                        ->hintIcon('heroicon-m-information-circle', tooltip: 'الموظف الذي تفاعل مع المصيدة')
                        ->label(__('traps.employee')),

                    Forms\Components\Select::make('trap_id')
                        ->relationship('trap', 'trap_code')
                        ->disabled()
                        ->hintIcon('heroicon-m-information-circle', tooltip: 'رمز المصيدة التي تم التفاعل معها')
                        ->label(__('traps.trap_code')),

                    Forms\Components\TextInput::make('trap_type')
                        ->disabled()
                        ->hintIcon('heroicon-m-information-circle', tooltip: 'نوع المصيدة: رابط مخفي، صفحة وهمية، أو غيرها')
                        ->label(__('traps.trap_type')),

                    Forms\Components\TextInput::make('page_url')
                        ->disabled()
                        ->hintIcon('heroicon-m-information-circle', tooltip: 'عنوان الصفحة التي حدث فيها التفاعل')
                        ->label(__('traps.page_url')),

                    Forms\Components\TextInput::make('ip_address')
                        ->disabled()
                        ->hintIcon('heroicon-m-information-circle', tooltip: 'عنوان الإنترنت (IP) الخاص بالجهاز المستخدم')
                        ->label(__('traps.ip_address')),

                    Forms\Components\Select::make('risk_level')
                        ->options([
                            'low'      => __('traps.risk_levels.low'),
                            'medium'   => __('traps.risk_levels.medium'),
                            'high'     => __('traps.risk_levels.high'),
                            'critical' => __('traps.risk_levels.critical'),
                        ])
                        ->disabled()
                        ->hintIcon('heroicon-m-information-circle', tooltip: 'مستوى الخطورة: يحدد مدى أهمية هذا التفاعل الأمني')
                        ->label(__('traps.risk_level')),
                ])->columns(['default' => 1, 'lg' => 3]),

            Forms\Components\Section::make(__('traps.review_section'))
                ->schema([
                    Forms\Components\Toggle::make('is_reviewed')
                        ->hintIcon('heroicon-m-information-circle', tooltip: 'حدد ما إذا تمت مراجعة هذا التفاعل من قبل المسؤول')
                        ->label(__('traps.is_reviewed')),

                    Forms\Components\Textarea::make('review_notes')
                        ->hintIcon('heroicon-m-information-circle', tooltip: 'أضف ملاحظات المراجعة والإجراء المتخذ')
                        ->label(__('traps.review_notes')),
                ])->columns(1),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name_ar')
                    ->searchable()
                    ->label(__('traps.employee')),

                Tables\Columns\TextColumn::make('trap.trap_code')
                    ->badge()
                    ->color('danger')
                    ->label(__('traps.trap_code')),

                Tables\Columns\TextColumn::make('risk_level')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'low'      => 'success',
                        'medium'   => 'warning',
                        'high'     => 'danger',
                        'critical' => 'danger',
                        default    => 'gray',
                    })
                    ->label(__('traps.risk_level')),

                Tables\Columns\TextColumn::make('ip_address')
                    ->toggleable()
                    ->label(__('traps.ip_address')),

                Tables\Columns\TextColumn::make('page_url')
                    ->limit(30)
                    ->toggleable()
                    ->label(__('traps.page_url')),

                Tables\Columns\IconColumn::make('is_reviewed')
                    ->boolean()
                    ->label(__('traps.is_reviewed')),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->label(__('traps.triggered_at')),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('risk_level')
                    ->options([
                        'low'      => __('traps.risk_levels.low'),
                        'medium'   => __('traps.risk_levels.medium'),
                        'high'     => __('traps.risk_levels.high'),
                        'critical' => __('traps.risk_levels.critical'),
                    ])
                    ->label(__('traps.risk_level')),

                TernaryFilter::make('is_reviewed')
                    ->label(__('traps.is_reviewed')),

                SelectFilter::make('trap_id')
                    ->relationship('trap', 'trap_code')
                    ->label(__('traps.trap_code')),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTrapInteractions::route('/'),
            'edit'  => Pages\EditTrapInteraction::route('/{record}/edit'),
            'view'  => Pages\ViewTrapInteraction::route('/{record}'),
        ];
    }
}
