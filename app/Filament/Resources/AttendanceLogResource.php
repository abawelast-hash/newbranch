<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AttendanceLogResource\Pages;
use App\Models\AttendanceLog;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;

class AttendanceLogResource extends Resource
{
    protected static ?string $model = AttendanceLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-clock';

    public static function getNavigationGroup(): ?string
    {
        return __('attendance.navigation_group');
    }

    protected static ?int $navigationSort = 1;

    /**
     * Branch Scope: non-super-admin sees only their branch's data.
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        $user = auth()->user();

        if ($user && !$user->is_super_admin && $user->branch_id) {
            $query->where('branch_id', $user->branch_id);
        }

        return $query;
    }

    public static function getNavigationLabel(): string
    {
        return __('attendance.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('attendance.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('attendance.plural_model_label');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make(__('attendance.check_in_section'))
                ->schema([
                    Forms\Components\Select::make('user_id')
                        ->relationship('user', 'name_ar')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->label(__('attendance.employee'))
                        ->hintIcon('heroicon-m-information-circle', tooltip: 'اختر الموظف المراد تسجيل حضوره'),

                    Forms\Components\Select::make('branch_id')
                        ->relationship('branch', 'name_ar')
                        ->required()
                        ->label(__('attendance.branch'))
                        ->hintIcon('heroicon-m-information-circle', tooltip: 'الفرع الذي سُجّل منه الحضور'),

                    Forms\Components\DatePicker::make('attendance_date')
                        ->required()
                        ->label(__('attendance.date'))
                        ->hintIcon('heroicon-m-information-circle', tooltip: 'تاريخ يوم العمل'),

                    Forms\Components\DateTimePicker::make('check_in_at')
                        ->label(__('attendance.check_in_time'))
                        ->hintIcon('heroicon-m-information-circle', tooltip: 'الوقت الفعلي لتسجيل الدخول'),

                    Forms\Components\DateTimePicker::make('check_out_at')
                        ->label(__('attendance.check_out_time'))
                        ->hintIcon('heroicon-m-information-circle', tooltip: 'الوقت الفعلي لتسجيل الخروج'),

                    Forms\Components\Select::make('status')
                        ->options([
                            'present'  => __('attendance.status_present'),
                            'late'     => __('attendance.status_late'),
                            'absent'   => __('attendance.status_absent'),
                            'on_leave' => __('attendance.status_on_leave'),
                            'holiday'  => __('attendance.status_holiday'),
                            'remote'   => __('attendance.status_remote'),
                            'half_day' => __('attendance.status_half_day'),
                        ])
                        ->required()
                        ->label(__('attendance.status'))
                        ->hintIcon('heroicon-m-information-circle', tooltip: 'حالة الحضور للموظف في هذا اليوم'),
                ])->columns(['default' => 1, 'lg' => 3]),

            Forms\Components\Section::make(__('attendance.financial_section'))
                ->schema([
                    Forms\Components\TextInput::make('delay_minutes')
                        ->numeric()
                        ->default(0)
                        ->label(__('attendance.delay_minutes'))
                        ->hintIcon('heroicon-m-information-circle', tooltip: 'عدد دقائق التأخير عن بداية الدوام'),

                    Forms\Components\TextInput::make('cost_per_minute')
                        ->numeric()
                        ->disabled()
                        ->label(__('attendance.cost_per_minute'))
                        ->hintIcon('heroicon-m-information-circle', tooltip: 'تكلفة الدقيقة الواحدة محسوبة من الراتب'),

                    Forms\Components\TextInput::make('delay_cost')
                        ->numeric()
                        ->disabled()
                        ->label(__('attendance.delay_cost'))
                        ->hintIcon('heroicon-m-information-circle', tooltip: 'الخسارة المالية الناتجة عن التأخير'),

                    Forms\Components\TextInput::make('overtime_minutes')
                        ->numeric()
                        ->default(0)
                        ->label(__('attendance.overtime_minutes'))
                        ->hintIcon('heroicon-m-information-circle', tooltip: 'دقائق العمل الإضافي بعد نهاية الدوام'),

                    Forms\Components\TextInput::make('overtime_value')
                        ->numeric()
                        ->disabled()
                        ->label(__('attendance.overtime_value'))
                        ->hintIcon('heroicon-m-information-circle', tooltip: 'قيمة العمل الإضافي بمعدل 1.5× من تكلفة الدقيقة'),
                ])->columns(['default' => 1, 'lg' => 3]),

            Forms\Components\Section::make(__('attendance.gps_section'))
                ->schema([
                    Forms\Components\TextInput::make('check_in_distance_meters')
                        ->numeric()
                        ->disabled()
                        ->suffix(__('attendance.meters'))
                        ->label(__('attendance.check_in_distance'))
                        ->hintIcon('heroicon-m-information-circle', tooltip: 'المسافة بين موقع الموظف ومقر الفرع عند التسجيل'),

                    Forms\Components\Toggle::make('check_in_within_geofence')
                        ->disabled()
                        ->label(__('attendance.within_geofence'))
                        ->hintIcon('heroicon-m-information-circle', tooltip: 'هل كان الموظف داخل النطاق الجغرافي المسموح'),

                    Forms\Components\Toggle::make('is_manual_entry')
                        ->label(__('attendance.manual_entry'))
                        ->hintIcon('heroicon-m-information-circle', tooltip: 'هل تم إدخال السجل يدوياً بواسطة الإدارة'),
                ])->columns(['default' => 1, 'lg' => 3]),

            Forms\Components\Textarea::make('notes')
                ->label(__('attendance.notes'))
                ->columnSpanFull()
                ->hintIcon('heroicon-m-information-circle', tooltip: 'ملاحظات إضافية حول سجل الحضور'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // Module 4: Mobile-First — stacked layout for responsive design
                Tables\Columns\Layout\Stack::make([
                    Tables\Columns\Layout\Split::make([
                        Tables\Columns\TextColumn::make('attendance_date')
                            ->date()
                            ->sortable()
                            ->label(__('attendance.date'))
                            ->grow(false),

                        Tables\Columns\TextColumn::make('user.name_ar')
                            ->searchable()
                            ->sortable()
                            ->label(__('attendance.employee'))
                            ->weight('bold'),

                        Tables\Columns\BadgeColumn::make('status')
                            ->colors([
                                'success' => 'present',
                                'warning' => 'late',
                                'danger'  => 'absent',
                                'primary' => 'on_leave',
                                'gray'    => 'holiday',
                            ])
                            ->formatStateUsing(fn (string $state): string => __("attendance.status_{$state}"))
                            ->label(__('attendance.status'))
                            ->grow(false),
                    ]),

                    Tables\Columns\Layout\Split::make([
                        Tables\Columns\TextColumn::make('branch.name_ar')
                            ->sortable()
                            ->label(__('attendance.branch'))
                            ->icon('heroicon-m-building-office')
                            ->color('gray'),

                        Tables\Columns\TextColumn::make('check_in_at')
                            ->dateTime('H:i')
                            ->label(__('attendance.check_in_time'))
                            ->icon('heroicon-m-arrow-left-on-rectangle')
                            ->color('success'),

                        Tables\Columns\TextColumn::make('check_out_at')
                            ->dateTime('H:i')
                            ->label(__('attendance.check_out_time'))
                            ->icon('heroicon-m-arrow-right-on-rectangle')
                            ->color('danger')
                            ->placeholder('—'),
                    ])->visibleFrom('md'),
                ])->space(2),

                // Desktop-only detailed columns
                Tables\Columns\TextColumn::make('delay_minutes')
                    ->numeric()
                    ->suffix(' ' . __('attendance.min'))
                    ->color(fn (int $state): string => $state > 0 ? 'danger' : 'success')
                    ->label(__('attendance.delay_minutes'))
                    ->visibleFrom('lg'),

                Tables\Columns\TextColumn::make('delay_cost')
                    ->money('SAR')
                    ->color('danger')
                    ->label(__('attendance.delay_cost'))
                    ->summarize(Tables\Columns\Summarizers\Sum::make()->money('SAR'))
                    ->visibleFrom('lg'),

                Tables\Columns\TextColumn::make('overtime_value')
                    ->money('SAR')
                    ->color('success')
                    ->label(__('attendance.overtime_value'))
                    ->summarize(Tables\Columns\Summarizers\Sum::make()->money('SAR'))
                    ->visibleFrom('lg'),

                Tables\Columns\TextColumn::make('cost_per_minute')
                    ->numeric(4)
                    ->suffix(' ' . __('attendance.sar_min'))
                    ->label(__('attendance.cost_per_minute'))
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('check_in_distance_meters')
                    ->numeric(1)
                    ->suffix(' ' . __('attendance.meters'))
                    ->label(__('attendance.check_in_distance'))
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make('check_in_within_geofence')
                    ->boolean()
                    ->label(__('attendance.within_geofence'))
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('worked_minutes')
                    ->numeric()
                    ->suffix(' ' . __('attendance.min'))
                    ->label(__('attendance.worked_minutes'))
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('attendance_date', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'present'  => __('attendance.status_present'),
                        'late'     => __('attendance.status_late'),
                        'absent'   => __('attendance.status_absent'),
                        'on_leave' => __('attendance.status_on_leave'),
                    ])
                    ->label(__('attendance.status')),

                SelectFilter::make('branch_id')
                    ->relationship('branch', 'name_ar')
                    ->label(__('attendance.branch')),

                Filter::make('attendance_date')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label(__('attendance.from_date')),
                        Forms\Components\DatePicker::make('until')
                            ->label(__('attendance.until_date')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'], fn (Builder $q, $date) => $q->whereDate('attendance_date', '>=', $date))
                            ->when($data['until'], fn (Builder $q, $date) => $q->whereDate('attendance_date', '<=', $date));
                    })
                    ->label(__('attendance.date_range')),

                Filter::make('has_delay_cost')
                    ->toggle()
                    ->query(fn (Builder $query) => $query->where('delay_cost', '>', 0))
                    ->label(__('attendance.with_financial_loss')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
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
            'index'  => Pages\ListAttendanceLogs::route('/'),
            'create' => Pages\CreateAttendanceLog::route('/create'),
            'view'   => Pages\ViewAttendanceLog::route('/{record}'),
            'edit'   => Pages\EditAttendanceLog::route('/{record}/edit'),
        ];
    }
}
