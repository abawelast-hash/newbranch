<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): ?string
    {
        return __('users.navigation_group');
    }

    public static function getNavigationLabel(): string
    {
        return __('users.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('users.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('users.plural_model_label');
    }

    /**
     * Branch Scope: non-super-admin sees only their branch's employees.
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

    public static function form(Form $form): Form
    {
        return $form->schema([
            // ── Section 1: Profile Image (Mandatory) ──────────────
            Forms\Components\Section::make(__('users.profile_section'))
                ->schema([
                    Forms\Components\FileUpload::make('avatar')
                        ->label(__('users.avatar'))
                        ->image()
                        ->avatar()
                        ->imageEditor()
                        ->circleCropper()
                        ->directory('avatars')
                        ->maxSize(2048)
                        ->required()
                        ->columnSpanFull()
                        ->hintIcon('heroicon-m-information-circle', tooltip: __('users.avatar_hint'))
                        ->helperText('صورة شخصية واضحة للموظف — تظهر في جميع الأقسام'),
                ]),

            // ── Section 2: Core Four ──────────────────────────────
            Forms\Components\Section::make(__('users.core_info_section'))
                ->description(__('users.core_info_description'))
                ->schema([
                    Forms\Components\TextInput::make('name_ar')
                        ->label(__('users.name_ar'))
                        ->required()
                        ->maxLength(255)
                        ->hintIcon('heroicon-m-information-circle', tooltip: __('users.name_ar_hint')),

                    Forms\Components\TextInput::make('name_en')
                        ->label(__('users.name_en'))
                        ->required()
                        ->maxLength(255)
                        ->hintIcon('heroicon-m-information-circle', tooltip: __('users.name_en_hint')),

                    Forms\Components\TextInput::make('email')
                        ->label(__('users.email'))
                        ->email()
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(255)
                        ->hintIcon('heroicon-m-information-circle', tooltip: __('users.email_hint')),

                    Forms\Components\TextInput::make('password')
                        ->label(__('users.password'))
                        ->password()
                        ->revealable()
                        ->dehydrateStateUsing(fn (string $state): string => Hash::make($state))
                        ->dehydrated(fn (?string $state): bool => filled($state))
                        ->required(fn (string $operation): bool => $operation === 'create')
                        ->maxLength(255)
                        ->hintIcon('heroicon-m-information-circle', tooltip: __('users.password_hint')),
                ])->columns(['default' => 1, 'lg' => 2]),

            // ── Section 3: Financial (Basic Salary — Core for cost-per-minute) ──
            Forms\Components\Section::make(__('users.financial_section'))
                ->description(__('users.financial_description'))
                ->schema([
                    Forms\Components\TextInput::make('basic_salary')
                        ->label(__('users.basic_salary'))
                        ->numeric()
                        ->required()
                        ->prefix(__('users.currency_sar'))
                        ->minValue(0)
                        ->step(100)
                        ->hintIcon('heroicon-m-information-circle', tooltip: __('users.basic_salary_hint')),

                    Forms\Components\TextInput::make('housing_allowance')
                        ->label(__('users.housing_allowance'))
                        ->numeric()
                        ->default(0)
                        ->prefix(__('users.currency_sar'))
                        ->hintIcon('heroicon-m-information-circle', tooltip: __('users.housing_allowance_hint')),

                    Forms\Components\TextInput::make('transport_allowance')
                        ->label(__('users.transport_allowance'))
                        ->numeric()
                        ->default(0)
                        ->prefix(__('users.currency_sar'))
                        ->hintIcon('heroicon-m-information-circle', tooltip: __('users.transport_allowance_hint')),

                    Forms\Components\TextInput::make('other_allowances')
                        ->label(__('users.other_allowances'))
                        ->numeric()
                        ->default(0)
                        ->prefix(__('users.currency_sar'))
                        ->hintIcon('heroicon-m-information-circle', tooltip: __('users.other_allowances_hint')),
                ])->columns(['default' => 1, 'lg' => 2]),

            // ── Section 4: Organizational (Optional, with smart defaults) ──
            Forms\Components\Section::make(__('users.organization_section'))
                ->collapsible()
                ->collapsed()
                ->schema([
                    Forms\Components\Select::make('branch_id')
                        ->label(__('users.branch'))
                        ->relationship('branch', 'name_ar')
                        ->searchable()
                        ->preload()
                        ->hintIcon('heroicon-m-information-circle', tooltip: __('users.branch_hint')),

                    Forms\Components\Select::make('department_id')
                        ->label(__('users.department'))
                        ->relationship('department', 'name_ar')
                        ->searchable()
                        ->preload()
                        ->hintIcon('heroicon-m-information-circle', tooltip: __('users.department_hint')),

                    Forms\Components\Select::make('role_id')
                        ->label(__('users.role') . ' (فخري)')
                        ->relationship('role', 'name_ar')
                        ->searchable()
                        ->preload()
                        ->helperText('⚠️ الدور فخري فقط — الصلاحيات تُدار من تبويب "الصلاحيات الفردية"')
                        ->hintIcon('heroicon-m-information-circle', tooltip: __('users.role_hint')),

                    Forms\Components\Select::make('direct_manager_id')
                        ->label(__('users.direct_manager'))
                        ->relationship('directManager', 'name_ar')
                        ->searchable()
                        ->preload()
                        ->hintIcon('heroicon-m-information-circle', tooltip: __('users.direct_manager_hint')),

                    Forms\Components\TextInput::make('phone')
                        ->label(__('users.phone'))
                        ->tel()
                        ->hintIcon('heroicon-m-information-circle', tooltip: __('users.phone_hint')),

                    Forms\Components\TextInput::make('employee_id')
                        ->label(__('users.employee_id'))
                        ->unique(ignoreRecord: true)
                        ->hintIcon('heroicon-m-information-circle', tooltip: __('users.employee_id_hint')),

                    Forms\Components\Select::make('status')
                        ->label(__('users.status'))
                        ->options([
                            'active'     => __('users.status_active'),
                            'suspended'  => __('users.status_suspended'),
                            'terminated' => __('users.status_terminated'),
                            'on_leave'   => __('users.status_on_leave'),
                        ])
                        ->default('active')
                        ->hintIcon('heroicon-m-information-circle', tooltip: __('users.status_hint')),

                    Forms\Components\Select::make('employment_type')
                        ->label(__('users.employment_type'))
                        ->options([
                            'full_time' => __('users.type_full_time'),
                            'part_time' => __('users.type_part_time'),
                            'contract'  => __('users.type_contract'),
                            'intern'    => __('users.type_intern'),
                        ])
                        ->default('full_time')
                        ->hintIcon('heroicon-m-information-circle', tooltip: __('users.employment_type_hint')),
                ])->columns(['default' => 1, 'lg' => 2]),

            // ── Hidden/Auto Defaults ──────────────────────────────
            Forms\Components\Hidden::make('working_days_per_month')
                ->default(22),

            Forms\Components\Hidden::make('working_hours_per_day')
                ->default(8),

            Forms\Components\Hidden::make('locale')
                ->default('ar'),

            Forms\Components\Hidden::make('timezone')
                ->default('Asia/Riyadh'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // ── Module 4: Mobile-First Stack/Split Layout ──────────
                Tables\Columns\Layout\Split::make([
                    Tables\Columns\ImageColumn::make('avatar')
                        ->label(__('users.avatar'))
                        ->circular()
                        ->grow(false)
                        ->defaultImageUrl(fn ($record) => 'https://ui-avatars.com/api/?name=' . urlencode($record->name_ar ?? 'U') . '&background=f97316&color=fff&font-size=0.5'),

                    Tables\Columns\Layout\Stack::make([
                        Tables\Columns\TextColumn::make('name_ar')
                            ->label(__('users.name_ar'))
                            ->searchable()
                            ->sortable()
                            ->weight('bold')
                            ->size('lg'),

                        Tables\Columns\TextColumn::make('employee_id')
                            ->label(__('users.employee_id'))
                            ->searchable()
                            ->sortable()
                            ->badge()
                            ->color('warning')
                            ->size('sm'),
                    ]),

                    Tables\Columns\Layout\Stack::make([
                        Tables\Columns\TextColumn::make('branch.name_ar')
                            ->label(__('users.branch'))
                            ->sortable()
                            ->icon('heroicon-m-building-office-2')
                            ->size('sm'),

                        Tables\Columns\TextColumn::make('role.name_ar')
                            ->label(__('users.role'))
                            ->badge()
                            ->color('info'),
                    ])->visibleFrom('md'),

                    Tables\Columns\Layout\Stack::make([
                        Tables\Columns\TextColumn::make('email')
                            ->label(__('users.email'))
                            ->searchable()
                            ->icon('heroicon-o-envelope')
                            ->size('sm'),

                        Tables\Columns\TextColumn::make('name_en')
                            ->label(__('users.name_en'))
                            ->searchable()
                            ->color('gray')
                            ->size('sm'),
                    ])->visibleFrom('lg'),
                ]),

                // ── Collapsible Detail Panel ──────────
                Tables\Columns\Layout\Panel::make([
                    Tables\Columns\Layout\Split::make([
                        Tables\Columns\TextColumn::make('basic_salary')
                            ->label(__('users.basic_salary'))
                            ->money('SAR')
                            ->sortable()
                            ->visible(fn (): bool => auth()->user()->security_level >= 6 || auth()->user()->is_super_admin),

                        Tables\Columns\TextColumn::make('total_points')
                            ->label(__('users.total_points'))
                            ->numeric()
                            ->sortable()
                            ->badge()
                            ->color(fn (?int $state): string => match (true) {
                                ($state ?? 0) >= 100 => 'success',
                                ($state ?? 0) >= 50  => 'warning',
                                ($state ?? 0) > 0    => 'info',
                                default               => 'gray',
                            }),

                        Tables\Columns\TextColumn::make('security_level')
                            ->label(__('users.security_level'))
                            ->badge()
                            ->color(fn (int $state): string => match (true) {
                                $state >= 10 => 'danger',
                                $state >= 7  => 'warning',
                                $state >= 4  => 'info',
                                default      => 'gray',
                            })
                            ->visible(fn (): bool => auth()->user()->security_level >= 7),


                        Tables\Columns\IconColumn::make('status')
                            ->label(__('users.status'))
                            ->icon(fn (string $state): string => match ($state) {
                                'active'     => 'heroicon-o-check-circle',
                                'suspended'  => 'heroicon-o-pause-circle',
                                'terminated' => 'heroicon-o-x-circle',
                                'on_leave'   => 'heroicon-o-clock',
                                default      => 'heroicon-o-question-mark-circle',
                            })
                            ->color(fn (string $state): string => match ($state) {
                                'active'     => 'success',
                                'suspended'  => 'warning',
                                'terminated' => 'danger',
                                'on_leave'   => 'info',
                                default      => 'gray',
                            }),
                    ]),
                ])->collapsible(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('users.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('branch_id')
                    ->label(__('users.branch'))
                    ->relationship('branch', 'name_ar'),

                Tables\Filters\SelectFilter::make('role_id')
                    ->label(__('users.role'))
                    ->relationship('role', 'name_ar'),

                Tables\Filters\SelectFilter::make('status')
                    ->label(__('users.status'))
                    ->options([
                        'active'     => __('users.status_active'),
                        'suspended'  => __('users.status_suspended'),
                        'terminated' => __('users.status_terminated'),
                        'on_leave'   => __('users.status_on_leave'),
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('adjust_points')
                    ->label(__('users.adjust_points'))
                    ->icon('heroicon-o-star')
                    ->color('warning')
                    ->visible(fn (): bool => Gate::allows('adjust-points'))
                    ->form([
                        Forms\Components\TextInput::make('points')
                            ->label(__('users.points_amount'))
                            ->numeric()
                            ->required()
                            ->helperText(__('users.points_helper')),
                        Forms\Components\Textarea::make('reason')
                            ->label(__('users.points_reason'))
                            ->required()
                            ->maxLength(500),
                    ])
                    ->action(function (User $record, array $data): void {
                        $delta = (int) $data['points'];
                        $newTotal = max(0, $record->total_points + $delta);
                        $record->update(['total_points' => $newTotal]);

                        // Log via PointsTransaction model
                        \App\Models\PointsTransaction::create([
                            'user_id'    => $record->id,
                            'points'     => $delta,
                            'reason'     => $data['reason'],
                            'awarded_by' => auth()->id(),
                        ]);

                        Notification::make()
                            ->title(__('users.points_adjusted'))
                            ->icon($delta >= 0 ? 'heroicon-o-arrow-trending-up' : 'heroicon-o-arrow-trending-down')
                            ->body(__('users.points_adjusted_body', [
                                'points' => ($delta >= 0 ? '+' : '') . $delta,
                                'name'   => $record->name_ar,
                                'total'  => $newTotal,
                            ]))
                            ->success()
                            ->send();
                    }),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),

                    Tables\Actions\BulkAction::make('bulk_adjust_salary')
                        ->label(__('users.bulk_adjust_salary'))
                        ->icon('heroicon-o-banknotes')
                        ->color('success')
                        ->form([
                            Forms\Components\Select::make('adjustment_type')
                                ->label(__('users.adjustment_type'))
                                ->options([
                                    'set'     => __('users.adjustment_set'),
                                    'add'     => __('users.adjustment_add'),
                                    'percent' => __('users.adjustment_percent'),
                                ])
                                ->required()
                                ->default('set'),
                            Forms\Components\TextInput::make('amount')
                                ->label(__('users.adjustment_amount'))
                                ->numeric()
                                ->required()
                                ->helperText(__('users.adjustment_amount_helper')),
                        ])
                        ->action(function (Collection $records, array $data): void {
                            $records->each(function (User $user) use ($data) {
                                $current = (float) $user->basic_salary;
                                $newSalary = match ($data['adjustment_type']) {
                                    'set'     => (float) $data['amount'],
                                    'add'     => $current + (float) $data['amount'],
                                    'percent' => $current * (1 + (float) $data['amount'] / 100),
                                    default   => $current,
                                };
                                $user->update(['basic_salary' => max(0, round($newSalary, 2))]);
                            });
                            Notification::make()
                                ->title(__('users.bulk_salary_updated'))
                                ->body(__('users.bulk_salary_updated_body', ['count' => $records->count()]))
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\BulkAction::make('bulk_change_branch')
                        ->label(__('users.bulk_change_branch'))
                        ->icon('heroicon-o-building-office-2')
                        ->color('info')
                        ->form([
                            Forms\Components\Select::make('branch_id')
                                ->label(__('users.branch'))
                                ->relationship('branch', 'name_ar')
                                ->searchable()
                                ->preload()
                                ->required(),
                        ])
                        ->action(function (Collection $records, array $data): void {
                            $records->each(fn (User $user) =>
                                $user->update(['branch_id' => $data['branch_id']])
                            );
                            Notification::make()
                                ->title(__('users.bulk_branch_updated'))
                                ->body(__('users.bulk_branch_updated_body', ['count' => $records->count()]))
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\BulkAction::make('bulk_change_status')
                        ->label(__('users.bulk_change_status'))
                        ->icon('heroicon-o-arrow-path')
                        ->color('warning')
                        ->form([
                            Forms\Components\Select::make('status')
                                ->label(__('users.status'))
                                ->options([
                                    'active'     => __('users.status_active'),
                                    'suspended'  => __('users.status_suspended'),
                                    'terminated' => __('users.status_terminated'),
                                    'on_leave'   => __('users.status_on_leave'),
                                ])
                                ->required(),
                        ])
                        ->action(function (Collection $records, array $data): void {
                            $records->each(fn (User $user) =>
                                $user->update(['status' => $data['status']])
                            );
                            Notification::make()
                                ->title(__('users.bulk_status_updated'))
                                ->body(__('users.bulk_status_updated_body', ['count' => $records->count()]))
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            \App\Filament\Resources\UserResource\RelationManagers\PermissionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'view'   => Pages\ViewUser::route('/{record}'),
            'edit'   => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
