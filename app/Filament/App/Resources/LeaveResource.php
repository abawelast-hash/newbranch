<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\LeaveResource\Pages;
use App\Models\LeaveRequest;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * SARH v2.0 — طلب إجازة للموظف (بوابة /app فقط)
 *
 * ⚠️ SCOPED: الموظف يرى طلباته فقط.
 */
class LeaveResource extends Resource
{
    protected static ?string $model = LeaveRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar';

    protected static ?string $navigationLabel = 'طلبات الإجازة';

    protected static ?string $modelLabel = 'طلب إجازة';

    protected static ?string $pluralModelLabel = 'طلبات الإجازة';

    protected static ?string $navigationGroup = 'الخدمات الذاتية';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'my-leaves';

    /**
     * الموظف يرى طلباته فقط.
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('user_id', auth()->id())
            ->latest('created_at');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('بيانات الإجازة')
                ->icon('heroicon-o-calendar')
                ->columns(2)
                ->schema([
                    Forms\Components\Hidden::make('user_id')
                        ->default(fn () => auth()->id()),

                    Forms\Components\Select::make('leave_type')
                        ->label('نوع الإجازة')
                        ->options([
                            'annual'     => 'سنوية',
                            'sick'       => 'مرضية',
                            'emergency'  => 'اضطرارية',
                            'unpaid'     => 'بدون راتب',
                            'maternity'  => 'أمومة',
                            'paternity'  => 'أبوة',
                            'hajj'       => 'حج',
                            'death'      => 'وفاة',
                            'marriage'   => 'زواج',
                        ])
                        ->required(),

                    Forms\Components\DatePicker::make('start_date')
                        ->label('تاريخ البداية')
                        ->required()
                        ->minDate(now()),

                    Forms\Components\DatePicker::make('end_date')
                        ->label('تاريخ النهاية')
                        ->required()
                        ->afterOrEqual('start_date'),

                    Forms\Components\TextInput::make('total_days')
                        ->label('عدد الأيام')
                        ->numeric()
                        ->required()
                        ->minValue(1),

                    Forms\Components\Textarea::make('reason')
                        ->label('السبب')
                        ->required()
                        ->columnSpanFull(),

                    Forms\Components\FileUpload::make('attachment_path')
                        ->label('مرفق (اختياري)')
                        ->directory('leave-attachments')
                        ->acceptedFileTypes(['application/pdf', 'image/*'])
                        ->maxSize(5120)
                        ->columnSpanFull(),

                    Forms\Components\Hidden::make('status')
                        ->default('pending'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\BadgeColumn::make('leave_type')
                    ->label('النوع')
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'annual'    => 'سنوية',
                        'sick'      => 'مرضية',
                        'emergency' => 'اضطرارية',
                        'unpaid'    => 'بدون راتب',
                        'maternity' => 'أمومة',
                        'paternity' => 'أبوة',
                        'hajj'      => 'حج',
                        'death'     => 'وفاة',
                        'marriage'  => 'زواج',
                        default     => $state,
                    }),

                Tables\Columns\TextColumn::make('start_date')
                    ->label('من')
                    ->date('Y-m-d'),

                Tables\Columns\TextColumn::make('end_date')
                    ->label('إلى')
                    ->date('Y-m-d'),

                Tables\Columns\TextColumn::make('total_days')
                    ->label('الأيام')
                    ->alignCenter(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('الحالة')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'approved',
                        'danger'  => 'rejected',
                    ])
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'pending'  => 'قيد المراجعة',
                        'approved' => 'مقبول',
                        'rejected' => 'مرفوض',
                        default    => $state,
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ التقديم')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options([
                        'pending'  => 'قيد المراجعة',
                        'approved' => 'مقبول',
                        'rejected' => 'مرفوض',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function canCreate(): bool
    {
        return true;
    }

    public static function canEdit($record): bool
    {
        // Only allow editing pending requests
        return $record->status === 'pending';
    }

    public static function canDelete($record): bool
    {
        return $record->status === 'pending';
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListLeaves::route('/'),
            'create' => Pages\CreateLeave::route('/create'),
        ];
    }
}
