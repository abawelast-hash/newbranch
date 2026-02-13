<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LeaveRequestResource\Pages;
use App\Models\LeaveRequest;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LeaveRequestResource extends Resource
{
    protected static ?string $model = LeaveRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar';

    protected static ?int $navigationSort = 11;

    public static function getNavigationGroup(): ?string
    {
        return __('leaves.navigation_group');
    }

    public static function getNavigationLabel(): string
    {
        return __('leaves.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('leaves.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('leaves.plural_model_label');
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'pending')->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    /**
     * Branch-scoped: non-super-admin sees only their branch's leave requests.
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if ($user && !$user->is_super_admin && $user->branch_id) {
            $query->whereHas('user', fn (Builder $q) => $q->where('branch_id', $user->branch_id));
        }

        return $query;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make(__('leaves.section_request'))
                ->icon('heroicon-o-calendar')
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('user_id')
                        ->label(__('leaves.employee'))
                        ->relationship('user', 'name_ar')
                        ->searchable()
                        ->preload()
                        ->required(),

                    Forms\Components\Select::make('leave_type')
                        ->label(__('leaves.leave_type'))
                        ->options([
                            'annual'     => __('leaves.type_annual'),
                            'sick'       => __('leaves.type_sick'),
                            'emergency'  => __('leaves.type_emergency'),
                            'unpaid'     => __('leaves.type_unpaid'),
                            'maternity'  => __('leaves.type_maternity'),
                            'paternity'  => __('leaves.type_paternity'),
                            'hajj'       => __('leaves.type_hajj'),
                            'death'      => __('leaves.type_death'),
                            'marriage'   => __('leaves.type_marriage'),
                        ])
                        ->required(),

                    Forms\Components\DatePicker::make('start_date')
                        ->label(__('leaves.start_date'))
                        ->required(),

                    Forms\Components\DatePicker::make('end_date')
                        ->label(__('leaves.end_date'))
                        ->required()
                        ->afterOrEqual('start_date'),

                    Forms\Components\TextInput::make('total_days')
                        ->label(__('leaves.total_days'))
                        ->numeric()
                        ->required(),

                    Forms\Components\Textarea::make('reason')
                        ->label(__('leaves.reason'))
                        ->columnSpanFull(),

                    Forms\Components\FileUpload::make('attachment_path')
                        ->label(__('leaves.attachment'))
                        ->directory('leave-attachments')
                        ->columnSpanFull(),
                ]),

            Forms\Components\Section::make(__('leaves.section_decision'))
                ->icon('heroicon-o-check-badge')
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('status')
                        ->label(__('leaves.status'))
                        ->options([
                            'pending'  => __('leaves.status_pending'),
                            'approved' => __('leaves.status_approved'),
                            'rejected' => __('leaves.status_rejected'),
                        ])
                        ->default('pending')
                        ->required(),

                    Forms\Components\Textarea::make('rejection_reason')
                        ->label(__('leaves.rejection_reason'))
                        ->visible(fn (Forms\Get $get) => $get('status') === 'rejected')
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name_ar')
                    ->label(__('leaves.employee'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('leave_type')
                    ->label(__('leaves.leave_type'))
                    ->formatStateUsing(fn (string $state) => __("leaves.type_{$state}")),

                Tables\Columns\TextColumn::make('start_date')
                    ->label(__('leaves.start_date'))
                    ->date('Y-m-d')
                    ->sortable(),

                Tables\Columns\TextColumn::make('end_date')
                    ->label(__('leaves.end_date'))
                    ->date('Y-m-d'),

                Tables\Columns\TextColumn::make('total_days')
                    ->label(__('leaves.total_days'))
                    ->alignCenter(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label(__('leaves.status'))
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'approved',
                        'danger'  => 'rejected',
                    ])
                    ->formatStateUsing(fn (string $state) => __("leaves.status_{$state}")),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('leaves.submitted_at'))
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('leaves.status'))
                    ->options([
                        'pending'  => __('leaves.status_pending'),
                        'approved' => __('leaves.status_approved'),
                        'rejected' => __('leaves.status_rejected'),
                    ]),

                Tables\Filters\SelectFilter::make('leave_type')
                    ->label(__('leaves.leave_type'))
                    ->options([
                        'annual'    => __('leaves.type_annual'),
                        'sick'      => __('leaves.type_sick'),
                        'emergency' => __('leaves.type_emergency'),
                        'unpaid'    => __('leaves.type_unpaid'),
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label(__('leaves.btn_approve'))
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (LeaveRequest $record) => $record->status === 'pending')
                    ->action(function (LeaveRequest $record) {
                        $record->update([
                            'status'      => 'approved',
                            'approved_by' => auth()->id(),
                            'approved_at' => now(),
                        ]);
                        Notification::make()
                            ->title(__('leaves.approved_success'))
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('reject')
                    ->label(__('leaves.btn_reject'))
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Textarea::make('rejection_reason')
                            ->label(__('leaves.rejection_reason'))
                            ->required(),
                    ])
                    ->visible(fn (LeaveRequest $record) => $record->status === 'pending')
                    ->action(function (LeaveRequest $record, array $data) {
                        $record->update([
                            'status'           => 'rejected',
                            'approved_by'      => auth()->id(),
                            'approved_at'      => now(),
                            'rejection_reason'  => $data['rejection_reason'],
                        ]);
                        Notification::make()
                            ->title(__('leaves.rejected_success'))
                            ->warning()
                            ->send();
                    }),

                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListLeaveRequests::route('/'),
            'create' => Pages\CreateLeaveRequest::route('/create'),
            'edit'   => Pages\EditLeaveRequest::route('/{record}/edit'),
        ];
    }
}
