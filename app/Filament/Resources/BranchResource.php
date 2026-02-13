<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BranchResource\Pages;
use App\Models\Branch;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class BranchResource extends Resource
{
    protected static ?string $model = Branch::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?int $navigationSort = 3;

    public static function getNavigationGroup(): ?string
    {
        return __('branches.navigation_group');
    }

    public static function getNavigationLabel(): string
    {
        return __('branches.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('branches.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('branches.plural_model_label');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            // ── Section 1: Branch Identity ────────────────────────
            Forms\Components\Section::make(__('branches.identity_section'))
                ->schema([
                    Forms\Components\TextInput::make('name_ar')
                        ->label(__('branches.name_ar'))
                        ->required()
                        ->maxLength(255)
                        ->hintIcon('heroicon-m-information-circle', tooltip: 'اسم الفرع باللغة العربية كما يظهر في الواجهة'),

                    Forms\Components\TextInput::make('name_en')
                        ->label(__('branches.name_en'))
                        ->required()
                        ->maxLength(255)
                        ->hintIcon('heroicon-m-information-circle', tooltip: 'اسم الفرع بالإنجليزية للتقارير الرسمية'),

                    Forms\Components\TextInput::make('code')
                        ->label(__('branches.code'))
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(20)
                        ->placeholder('RYD-HQ')
                        ->hintIcon('heroicon-m-information-circle', tooltip: 'رمز مختصر يميّز الفرع — مثال: RYD-HQ'),

                    Forms\Components\TextInput::make('phone')
                        ->label(__('branches.phone'))
                        ->tel()
                        ->hintIcon('heroicon-m-information-circle', tooltip: 'رقم هاتف الفرع للتواصل'),

                    Forms\Components\TextInput::make('email')
                        ->label(__('branches.email'))
                        ->email()
                        ->hintIcon('heroicon-m-information-circle', tooltip: 'البريد الإلكتروني الرسمي للفرع'),

                    Forms\Components\Toggle::make('is_active')
                        ->label(__('branches.is_active'))
                        ->default(true)
                        ->hintIcon('heroicon-m-information-circle', tooltip: 'عند إيقافه لن يظهر الفرع في قوائم الاختيار')
                        ->helperText('عند إيقافه لن يظهر الفرع في قوائم الاختيار'),
                ])->columns(['default' => 1, 'lg' => 2]),

            // ── Section 2: Geolocation — Leaflet Map Picker ──────
            Forms\Components\Section::make(__('branches.geolocation_section'))
                ->description(__('branches.geolocation_description'))
                ->schema([
                    Forms\Components\ViewField::make('map_picker')
                        ->label(__('branches.map_picker'))
                        ->view('filament.forms.components.map-picker')
                        ->columnSpanFull(),

                    Forms\Components\TextInput::make('latitude')
                        ->label(__('branches.latitude'))
                        ->required()
                        ->numeric()
                        ->step(0.0000001)
                        ->minValue(-90)
                        ->maxValue(90)
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn (Set $set, ?string $state) => $set('latitude', $state))
                        ->hintIcon('heroicon-m-information-circle', tooltip: 'الإحداثية الجغرافية — تُحدَّد تلقائياً من الخريطة'),

                    Forms\Components\TextInput::make('longitude')
                        ->label(__('branches.longitude'))
                        ->required()
                        ->numeric()
                        ->step(0.0000001)
                        ->minValue(-180)
                        ->maxValue(180)
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn (Set $set, ?string $state) => $set('longitude', $state))
                        ->hintIcon('heroicon-m-information-circle', tooltip: 'الإحداثية الجغرافية — تُحدَّد تلقائياً من الخريطة'),

                    Forms\Components\TextInput::make('geofence_radius')
                        ->label(__('branches.geofence_radius'))
                        ->required()
                        ->numeric()
                        ->default(100)
                        ->minValue(1)
                        ->maxValue(100000)
                        ->suffix(__('branches.meters'))
                        ->helperText(__('branches.geofence_radius_help'))
                        ->hintIcon('heroicon-m-information-circle', tooltip: 'النطاق الجغرافي الذي يُسمح بتسجيل الحضور منه حول مقر الفرع'),
                ])->columns(['default' => 1, 'lg' => 2]),

            // ── Section 3: Shift & Policy ─────────────────────────
            Forms\Components\Section::make(__('branches.shift_section'))
                ->collapsible()
                ->collapsed()
                ->schema([
                    Forms\Components\TimePicker::make('default_shift_start')
                        ->label(__('branches.shift_start'))
                        ->default('08:00')
                        ->seconds(false)
                        ->hintIcon('heroicon-m-information-circle', tooltip: 'الوقت الرسمي لبداية الدوام في هذا الفرع'),

                    Forms\Components\TimePicker::make('default_shift_end')
                        ->label(__('branches.shift_end'))
                        ->default('17:00')
                        ->seconds(false)
                        ->hintIcon('heroicon-m-information-circle', tooltip: 'الوقت الرسمي لنهاية الدوام'),

                    Forms\Components\TextInput::make('grace_period_minutes')
                        ->label(__('branches.grace_period'))
                        ->numeric()
                        ->default(15)
                        ->minValue(0)
                        ->maxValue(120)
                        ->suffix(__('branches.minutes'))
                        ->hintIcon('heroicon-m-information-circle', tooltip: 'المدة المسموحة بعد بداية الدوام قبل احتساب التأخير'),
                ])->columns(['default' => 1, 'lg' => 3]),

            // ── Section 4: Address (Optional) ─────────────────────
            Forms\Components\Section::make(__('branches.address_section'))
                ->collapsible()
                ->collapsed()
                ->schema([
                    Forms\Components\Textarea::make('address_ar')
                        ->label(__('branches.address_ar'))
                        ->hintIcon('heroicon-m-information-circle', tooltip: 'العنوان الكامل للفرع باللغة العربية'),

                    Forms\Components\Textarea::make('address_en')
                        ->label(__('branches.address_en'))
                        ->hintIcon('heroicon-m-information-circle', tooltip: 'العنوان بالإنجليزية للتقارير الرسمية'),

                    Forms\Components\TextInput::make('city_ar')
                        ->label(__('branches.city_ar'))
                        ->hintIcon('heroicon-m-information-circle', tooltip: 'اسم المدينة بالعربية'),

                    Forms\Components\TextInput::make('city_en')
                        ->label(__('branches.city_en'))
                        ->hintIcon('heroicon-m-information-circle', tooltip: 'اسم المدينة بالإنجليزية'),
                ])->columns(['default' => 1, 'lg' => 2]),

            // ── Section 5: Financial (Optional) ───────────────────
            Forms\Components\Section::make(__('branches.financial_section'))
                ->collapsible()
                ->collapsed()
                ->schema([
                    Forms\Components\TextInput::make('monthly_salary_budget')
                        ->label(__('branches.salary_budget'))
                        ->numeric()
                        ->default(0)
                        ->prefix(__('branches.currency_sar'))
                        ->hintIcon('heroicon-m-information-circle', tooltip: 'إجمالي ميزانية الرواتب الشهرية للفرع'),

                    Forms\Components\TextInput::make('monthly_delay_losses')
                        ->label(__('branches.delay_losses'))
                        ->numeric()
                        ->default(0)
                        ->prefix(__('branches.currency_sar'))
                        ->disabled()
                        ->hintIcon('heroicon-m-information-circle', tooltip: 'إجمالي الخسائر المالية من تأخيرات الموظفين'),
                ])->columns(['default' => 1, 'lg' => 2]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label(__('branches.code'))
                    ->badge()
                    ->color('warning')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('name_ar')
                    ->label(__('branches.name_ar'))
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('name_en')
                    ->label(__('branches.name_en'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('city_ar')
                    ->label(__('branches.city_ar'))
                    ->sortable(),

                Tables\Columns\TextColumn::make('geofence_radius')
                    ->label(__('branches.geofence_radius'))
                    ->suffix(' ' . __('branches.meters'))
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('default_shift_start')
                    ->label(__('branches.shift_start'))
                    ->time('H:i'),

                Tables\Columns\TextColumn::make('default_shift_end')
                    ->label(__('branches.shift_end'))
                    ->time('H:i'),

                Tables\Columns\TextColumn::make('grace_period_minutes')
                    ->label(__('branches.grace_period'))
                    ->suffix(' ' . __('branches.minutes'))
                    ->numeric(),

                Tables\Columns\TextColumn::make('users_count')
                    ->label(__('branches.employees_count'))
                    ->counts('users')
                    ->badge()
                    ->color('info'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label(__('branches.is_active'))
                    ->boolean(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('branches.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label(__('branches.is_active')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('code');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListBranches::route('/'),
            'create' => Pages\CreateBranch::route('/create'),
            'view'   => Pages\ViewBranch::route('/{record}'),
            'edit'   => Pages\EditBranch::route('/{record}/edit'),
        ];
    }
}
