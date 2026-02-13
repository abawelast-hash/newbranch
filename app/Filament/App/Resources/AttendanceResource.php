<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\AttendanceResource\Pages;
use App\Models\AttendanceLog;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * SARH v1.9.0 — سجل حضور الموظف (بوابة /app فقط)
 *
 * ⚠️ SCOPED: getEloquentQuery() يقيّد البيانات لـ auth()->id() فقط.
 * الموظف لا يستطيع رؤية/تعديل/حذف سجلات غيره.
 */
class AttendanceResource extends Resource
{
    protected static ?string $model = AttendanceLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-clock';

    protected static ?string $navigationLabel = 'سجل الحضور';

    protected static ?string $modelLabel = 'سجل حضور';

    protected static ?string $pluralModelLabel = 'سجل الحضور';

    protected static ?string $navigationGroup = 'الحضور والانصراف';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'my-attendance';

    /**
     * ⚠️ أهم سطر: الموظف يرى سجلاته فقط.
     * بدون هذا → يرى كل سجلات الشركة = كارثة أمنية.
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('user_id', auth()->id())
            ->latest('attendance_date');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('بيانات الحضور')
                    ->icon('heroicon-o-map-pin')
                    ->description('يتم تسجيل موقعك الجغرافي وعنوان IP تلقائياً.')
                    ->schema([
                        Forms\Components\Hidden::make('user_id')
                            ->default(auth()->id()),

                        Forms\Components\Hidden::make('branch_id')
                            ->default(fn () => auth()->user()?->branch_id),

                        Forms\Components\DatePicker::make('attendance_date')
                            ->label('التاريخ')
                            ->hintIcon('heroicon-m-information-circle', tooltip: 'تاريخ يوم الحضور — يُحدد تلقائياً')
                            ->default(now())
                            ->disabled()
                            ->dehydrated()
                            ->required(),

                        Forms\Components\TimePicker::make('check_in_at')
                            ->label('وقت الحضور')
                            ->hintIcon('heroicon-m-information-circle', tooltip: 'وقت تسجيل الدخول — يُسجل تلقائياً عند الضغط')
                            ->seconds(false)
                            ->default(now()->format('H:i'))
                            ->disabled()
                            ->dehydrated()
                            ->required(),

                        Forms\Components\Hidden::make('check_in_latitude'),
                        Forms\Components\Hidden::make('check_in_longitude'),

                        Forms\Components\Hidden::make('check_in_ip')
                            ->default(fn () => request()->ip()),

                        Forms\Components\Hidden::make('check_in_device')
                            ->default(fn () => request()->userAgent()),

                        Forms\Components\Hidden::make('status')
                            ->default('present'),

                        Forms\Components\Textarea::make('notes')
                            ->label('ملاحظات')
                            ->hintIcon('heroicon-m-information-circle', tooltip: 'أضف أي ملاحظة مثل سبب التأخير أو طلب إذن مبكر')
                            ->rows(2)
                            ->maxLength(255)
                            ->placeholder('أضف ملاحظة إن وجدت...'),
                    ])
                    ->columns(['default' => 1, 'lg' => 2]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // ── Module 4: Mobile-First Stack/Split Layout ──────────
                Tables\Columns\Layout\Split::make([
                    Tables\Columns\Layout\Stack::make([
                        Tables\Columns\TextColumn::make('attendance_date')
                            ->label('التاريخ')
                            ->date('Y-m-d')
                            ->sortable()
                            ->searchable()
                            ->weight('bold')
                            ->size('lg'),

                        Tables\Columns\TextColumn::make('status')
                            ->label('الحالة')
                            ->badge()
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'present'   => 'حاضر',
                                'late'      => 'متأخر',
                                'absent'    => 'غائب',
                                'excused'   => 'مستأذن',
                                'vacation'  => 'إجازة',
                                default     => $state,
                            })
                            ->color(fn (string $state): string => match ($state) {
                                'present'   => 'success',
                                'late'      => 'warning',
                                'absent'    => 'danger',
                                'excused'   => 'info',
                                'vacation'  => 'primary',
                                default     => 'gray',
                            }),
                    ]),

                    Tables\Columns\Layout\Stack::make([
                        Tables\Columns\TextColumn::make('check_in_at')
                            ->label('وقت الحضور')
                            ->dateTime('H:i')
                            ->sortable()
                            ->color('success')
                            ->icon('heroicon-m-arrow-left-on-rectangle'),

                        Tables\Columns\TextColumn::make('check_out_at')
                            ->label('وقت الانصراف')
                            ->dateTime('H:i')
                            ->sortable()
                            ->color('danger')
                            ->icon('heroicon-m-arrow-right-on-rectangle')
                            ->placeholder('لم يُسجَّل بعد'),
                    ]),

                    Tables\Columns\Layout\Stack::make([
                        Tables\Columns\TextColumn::make('worked_minutes')
                            ->label('ساعات العمل')
                            ->formatStateUsing(function (?int $state): string {
                                if ($state === null || $state <= 0) {
                                    return '—';
                                }
                                $hours = intdiv($state, 60);
                                $mins = $state % 60;
                                return sprintf('%02d:%02d', $hours, $mins);
                            })
                            ->badge()
                            ->color('info'),

                        Tables\Columns\TextColumn::make('delay_minutes')
                            ->label('تأخير (دقيقة)')
                            ->formatStateUsing(fn (?int $state): string => $state > 0 ? "{$state} دقيقة" : '—')
                            ->color(fn (?int $state): string => ($state ?? 0) > 0 ? 'danger' : 'success'),
                    ])->visibleFrom('md'),
                ]),

                // ── Collapsible Detail Panel ──────────
                Tables\Columns\Layout\Panel::make([
                    Tables\Columns\Layout\Split::make([
                        Tables\Columns\IconColumn::make('check_in_within_geofence')
                            ->label('داخل النطاق')
                            ->boolean()
                            ->trueIcon('heroicon-o-check-circle')
                            ->falseIcon('heroicon-o-x-circle')
                            ->trueColor('success')
                            ->falseColor('danger'),

                        Tables\Columns\TextColumn::make('notes')
                            ->label('ملاحظات')
                            ->limit(50),
                    ]),
                ])->collapsible(),
            ])
            ->defaultSort('attendance_date', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('الحالة')
                    ->options([
                        'present'   => 'حاضر',
                        'late'      => 'متأخر',
                        'absent'    => 'غائب',
                        'excused'   => 'مستأذن',
                        'vacation'  => 'إجازة',
                    ]),

                Filter::make('date_range')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('من تاريخ'),
                        Forms\Components\DatePicker::make('until')
                            ->label('إلى تاريخ'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'] ?? null,
                                fn (Builder $q, $date): Builder => $q->whereDate('attendance_date', '>=', $date),
                            )
                            ->when(
                                $data['until'] ?? null,
                                fn (Builder $q, $date): Builder => $q->whereDate('attendance_date', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['from'] ?? null) {
                            $indicators[] = 'من: ' . $data['from'];
                        }
                        if ($data['until'] ?? null) {
                            $indicators[] = 'إلى: ' . $data['until'];
                        }
                        return $indicators;
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('عرض'),
            ])
            // ⚠️ الموظف لا يحذف سجلات الحضور — حظر كامل
            ->bulkActions([])
            ->emptyStateHeading('لا توجد سجلات حضور')
            ->emptyStateDescription('لم يتم تسجيل أي حضور بعد.')
            ->emptyStateIcon('heroicon-o-clock')
            ->poll('60s');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListAttendances::route('/'),
            'create' => Pages\CreateAttendance::route('/create'),
        ];
    }

    /**
     * الموظف لا يعدّل سجلات الحضور — فقط الإدارة.
     */
    public static function canEdit(Model $record): bool
    {
        return false;
    }

    /**
     * الموظف لا يحذف سجلات الحضور — أبداً.
     */
    public static function canDelete(Model $record): bool
    {
        return false;
    }
}
