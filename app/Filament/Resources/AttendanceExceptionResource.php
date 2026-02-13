<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AttendanceExceptionResource\Pages;
use App\Models\AttendanceException;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * SARH v1.9.0 — Module 7: محرك استثناءات الحضور
 *
 * واجهة إدارية لتعريف استثناءات حضور لموظفين محددين.
 */
class AttendanceExceptionResource extends Resource
{
    protected static ?string $model = AttendanceException::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-path';

    protected static ?int $navigationSort = 5;

    public static function getNavigationGroup(): ?string
    {
        return __('الحضور والانصراف');
    }

    public static function getNavigationLabel(): string
    {
        return 'استثناءات الحضور';
    }

    public static function getModelLabel(): string
    {
        return 'استثناء حضور';
    }

    public static function getPluralModelLabel(): string
    {
        return 'استثناءات الحضور';
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();
        return $user && ($user->is_super_admin || $user->security_level >= 7);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('بيانات الاستثناء')
                ->description('تعريف استثناء حضور لموظف محدد')
                ->schema([
                    Forms\Components\Select::make('user_id')
                        ->label('الموظف')
                        ->relationship('user', 'name_ar')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->hintIcon('heroicon-m-information-circle', tooltip: 'اختر الموظف الذي سيُطبَّق عليه الاستثناء'),

                    Forms\Components\Select::make('exception_type')
                        ->label('نوع الاستثناء')
                        ->options([
                            'flexible_hours' => 'ساعات مرنة',
                            'remote_work'    => 'عمل عن بعد',
                            'vip_bypass'     => 'تجاوز VIP',
                            'medical'        => 'طبي',
                            'custom'         => 'مخصص',
                        ])
                        ->required()
                        ->native(false)
                        ->live()
                        ->hintIcon('heroicon-m-information-circle', tooltip: 'نوع الاستثناء يحدد القواعد المُطبَّقة على الموظف'),
                ])->columns(['default' => 1, 'lg' => 2]),

            Forms\Components\Section::make('إعدادات الدوام المخصص')
                ->description('تجاوز ساعات الدوام الرسمية لهذا الموظف')
                ->schema([
                    Forms\Components\TimePicker::make('custom_shift_start')
                        ->label('بداية الدوام المخصص')
                        ->seconds(false)
                        ->hintIcon('heroicon-m-information-circle', tooltip: 'وقت بداية الدوام المخصص لهذا الموظف')
                        ->helperText('اتركه فارغاً لاستخدام الدوام الرسمي'),

                    Forms\Components\TimePicker::make('custom_shift_end')
                        ->label('نهاية الدوام المخصص')
                        ->seconds(false)
                        ->hintIcon('heroicon-m-information-circle', tooltip: 'وقت نهاية الدوام المخصص لهذا الموظف'),

                    Forms\Components\TextInput::make('custom_grace_minutes')
                        ->label('فترة السماح المخصصة (دقيقة)')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(120)
                        ->hintIcon('heroicon-m-information-circle', tooltip: 'عدد الدقائق المسموحة بعد بداية الدوام'),
                ])->columns(['default' => 1, 'lg' => 3]),

            Forms\Components\Section::make('التجاوزات')
                ->schema([
                    Forms\Components\Toggle::make('bypass_geofence')
                        ->label('تجاوز السياج الجغرافي')
                        ->hintIcon('heroicon-m-information-circle', tooltip: 'يسمح بتسجيل الحضور من أي موقع جغرافي')
                        ->helperText('يسمح بتسجيل الحضور من أي موقع'),

                    Forms\Components\Toggle::make('bypass_late_flag')
                        ->label('تجاوز علامة التأخير')
                        ->default(true)
                        ->hintIcon('heroicon-m-information-circle', tooltip: 'لا يُسجَّل كمتأخر حتى لو وصل بعد الوقت')
                        ->helperText('لا يُسجَّل كمتأخر حتى لو وصل بعد الوقت'),

                    Forms\Components\Toggle::make('is_active')
                        ->label('مفعّل')
                        ->default(true)
                        ->hintIcon('heroicon-m-information-circle', tooltip: 'هل هذا الاستثناء نشط حالياً'),
                ])->columns(['default' => 1, 'lg' => 3]),

            Forms\Components\Section::make('الفترة والسبب')
                ->schema([
                    Forms\Components\DatePicker::make('start_date')
                        ->label('تاريخ البداية')
                        ->required()
                        ->default(now())
                        ->hintIcon('heroicon-m-information-circle', tooltip: 'تاريخ بداية سريان الاستثناء'),

                    Forms\Components\DatePicker::make('end_date')
                        ->label('تاريخ الانتهاء')
                        ->hintIcon('heroicon-m-information-circle', tooltip: 'اتركه فارغاً لجعل الاستثناء دائماً')
                        ->helperText('اتركه فارغاً للاستثناء الدائم'),

                    Forms\Components\Textarea::make('reason')
                        ->label('السبب')
                        ->rows(3)
                        ->columnSpanFull()
                        ->hintIcon('heroicon-m-information-circle', tooltip: 'سبب منح هذا الاستثناء للموظف'),

                    Forms\Components\Hidden::make('approved_by')
                        ->default(fn () => auth()->id()),
                ])->columns(['default' => 1, 'lg' => 2]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name_ar')
                    ->label('الموظف')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('user.employee_id')
                    ->label('الرقم الوظيفي')
                    ->badge()
                    ->color('warning'),

                Tables\Columns\TextColumn::make('exception_type')
                    ->label('النوع')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'flexible_hours' => 'ساعات مرنة',
                        'remote_work'    => 'عمل عن بعد',
                        'vip_bypass'     => 'تجاوز VIP',
                        'medical'        => 'طبي',
                        'custom'         => 'مخصص',
                        default          => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'vip_bypass' => 'danger',
                        'medical'    => 'warning',
                        'remote_work'=> 'info',
                        default      => 'gray',
                    }),

                Tables\Columns\IconColumn::make('bypass_geofence')
                    ->label('تجاوز الموقع')
                    ->boolean(),

                Tables\Columns\IconColumn::make('bypass_late_flag')
                    ->label('تجاوز التأخير')
                    ->boolean(),

                Tables\Columns\TextColumn::make('start_date')
                    ->label('من')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('end_date')
                    ->label('إلى')
                    ->date()
                    ->placeholder('دائم')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('مفعّل')
                    ->boolean(),

                Tables\Columns\TextColumn::make('approvedByUser.name_ar')
                    ->label('المعتمد')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('exception_type')
                    ->label('النوع')
                    ->options([
                        'flexible_hours' => 'ساعات مرنة',
                        'remote_work'    => 'عمل عن بعد',
                        'vip_bypass'     => 'تجاوز VIP',
                        'medical'        => 'طبي',
                        'custom'         => 'مخصص',
                    ]),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('مفعّل'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListAttendanceExceptions::route('/'),
            'create' => Pages\CreateAttendanceException::route('/create'),
            'edit'   => Pages\EditAttendanceException::route('/{record}/edit'),
        ];
    }
}
