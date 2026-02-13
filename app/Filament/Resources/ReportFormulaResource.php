<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReportFormulaResource\Pages;
use App\Models\ReportFormula;
use App\Services\FormulaEngineService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * SARH v1.9.0 — Module 8: صيغ التقارير الديناميكية
 *
 * واجهة لتعريف صيغ حسابية مخصصة يمكن للمالك استخدامها في التقارير.
 */
class ReportFormulaResource extends Resource
{
    protected static ?string $model = ReportFormula::class;

    protected static ?string $navigationIcon = 'heroicon-o-calculator';

    protected static ?int $navigationSort = 13;

    public static function getNavigationGroup(): ?string
    {
        return 'التقارير والتحليلات';
    }

    public static function getNavigationLabel(): string
    {
        return 'صيغ التقارير';
    }

    public static function getModelLabel(): string
    {
        return 'صيغة تقرير';
    }

    public static function getPluralModelLabel(): string
    {
        return 'صيغ التقارير';
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();
        return $user && ($user->is_super_admin || $user->security_level >= 10);
    }

    public static function form(Form $form): Form
    {
        $availableVars = FormulaEngineService::getAvailableVariables();

        return $form->schema([
            Forms\Components\Section::make('بيانات الصيغة')
                ->schema([
                    Forms\Components\TextInput::make('name_ar')
                        ->label('الاسم بالعربية')
                        ->required()
                        ->maxLength(255)
                        ->hintIcon('heroicon-m-information-circle', tooltip: 'اسم الصيغة كما يظهر في قائمة التقارير'),

                    Forms\Components\TextInput::make('name_en')
                        ->label('الاسم بالإنجليزية')
                        ->required()
                        ->maxLength(255)
                        ->hintIcon('heroicon-m-information-circle', tooltip: 'اسم الصيغة بالإنجليزية'),

                    Forms\Components\TextInput::make('slug')
                        ->label('المعرّف')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(100)
                        ->hintIcon('heroicon-m-information-circle', tooltip: 'معرّف فريد يُستخدم للإشارة لهذه الصيغة')
                        ->helperText('مثال: performance-score, branch-rating'),

                    Forms\Components\Toggle::make('is_active')
                        ->label('مفعّلة')
                        ->default(true)
                        ->hintIcon('heroicon-m-information-circle', tooltip: 'هل هذه الصيغة نشطة ومتاحة للاستخدام'),
                ])->columns(['default' => 1, 'lg' => 2]),

            Forms\Components\Section::make('الصيغة الحسابية')
                ->description('اكتب الصيغة باستخدام المتغيرات المتاحة. مثال: (attendance * 0.4) + (on_time_rate * 0.3) + (total_points * 0.003)')
                ->schema([
                    Forms\Components\Textarea::make('formula')
                        ->label('الصيغة')
                        ->required()
                        ->rows(3)
                        ->placeholder('(attendance * 0.4) + (on_time_rate * 0.3) + (total_points * 0.003)')
                        ->hintIcon('heroicon-m-information-circle', tooltip: 'اكتب الصيغة الحسابية باستخدام المتغيرات المتاحة')
                        ->helperText('العمليات المدعومة: + - * / () — استخدم أسماء المتغيرات من القائمة أدناه')
                        ->columnSpanFull(),

                    Forms\Components\CheckboxList::make('variables')
                        ->label('المتغيرات المستخدمة')
                        ->options(collect($availableVars)->mapWithKeys(function ($desc, $key) {
                            $label = is_array($desc) ? ($desc['ar'] ?? $key) : $desc;
                            return [$key => "{$key} — {$label}"];
                        })->toArray())
                        ->columns(['default' => 1, 'lg' => 3])
                        ->helperText('اختر المتغيرات التي تستخدمها في الصيغة')
                        ->columnSpanFull()
                        ->dehydrateStateUsing(function (array $state) use ($availableVars) {
                            $result = [];
                            foreach ($state as $key) {
                                $result[$key] = $availableVars[$key] ?? $key;
                            }
                            return $result;
                        }),
                ]),

            Forms\Components\Section::make('الوصف')
                ->collapsible()
                ->collapsed()
                ->schema([
                    Forms\Components\Textarea::make('description_ar')
                        ->label('الوصف بالعربية')
                        ->rows(2)
                        ->hintIcon('heroicon-m-information-circle', tooltip: 'وصف مختصر لهذه الصيغة بالعربية'),

                    Forms\Components\Textarea::make('description_en')
                        ->label('الوصف بالإنجليزية')
                        ->rows(2)
                        ->hintIcon('heroicon-m-information-circle', tooltip: 'وصف مختصر لهذه الصيغة بالإنجليزية'),
                ])->columns(['default' => 1, 'lg' => 2]),

            Forms\Components\Hidden::make('created_by')
                ->default(fn () => auth()->id()),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name_ar')
                    ->label('الاسم')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('slug')
                    ->label('المعرّف')
                    ->badge()
                    ->color('info')
                    ->searchable(),

                Tables\Columns\TextColumn::make('formula')
                    ->label('الصيغة')
                    ->limit(60)
                    ->fontFamily('mono')
                    ->wrap(),

                Tables\Columns\TextColumn::make('variables')
                    ->label('المتغيرات')
                    ->formatStateUsing(function ($state) {
                        if (is_array($state)) {
                            return implode(', ', array_keys($state));
                        }
                        return '—';
                    })
                    ->badge()
                    ->color('warning'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('مفعّلة')
                    ->boolean(),

                Tables\Columns\TextColumn::make('createdByUser.name_ar')
                    ->label('أنشأها')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('مفعّلة'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),

                // Test formula action
                Tables\Actions\Action::make('test_formula')
                    ->label('اختبار')
                    ->icon('heroicon-o-play')
                    ->color('success')
                    ->form([
                        Forms\Components\Select::make('user_id')
                            ->label('اختبار على موظف')
                            ->relationship('createdByUser', 'name_ar', fn ($query) => \App\Models\User::query())
                            ->options(\App\Models\User::active()->pluck('name_ar', 'id'))
                            ->searchable()
                            ->required(),
                        Forms\Components\DatePicker::make('start_date')
                            ->label('من')
                            ->required()
                            ->default(now()->startOfMonth()),
                        Forms\Components\DatePicker::make('end_date')
                            ->label('إلى')
                            ->required()
                            ->default(now()),
                    ])
                    ->action(function (ReportFormula $record, array $data) {
                        $user = \App\Models\User::find($data['user_id']);
                        $engine = app(FormulaEngineService::class);
                        $result = $engine->evaluateForUser($record, $user, $data['start_date'], $data['end_date']);

                        \Filament\Notifications\Notification::make()
                            ->title('نتيجة الاختبار')
                            ->body("الصيغة: {$record->formula}\nالنتيجة: " . ($result !== null ? number_format($result, 4) : 'خطأ في الصيغة'))
                            ->icon('heroicon-o-calculator')
                            ->color($result !== null ? 'success' : 'danger')
                            ->persistent()
                            ->send();
                    }),
            ])
            ->bulkActions([])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListReportFormulas::route('/'),
            'create' => Pages\CreateReportFormula::route('/create'),
            'edit'   => Pages\EditReportFormula::route('/{record}/edit'),
        ];
    }
}
