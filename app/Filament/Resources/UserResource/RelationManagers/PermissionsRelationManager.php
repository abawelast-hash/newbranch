<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use App\Models\Permission;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * SARH v4.1 — إدارة الصلاحيات الفردية لكل مستخدم
 *
 * هذا هو المصدر الوحيد للصلاحيات — الأدوار فخرية فقط.
 */
class PermissionsRelationManager extends RelationManager
{
    protected static string $relationship = 'userPermissions';

    protected static ?string $title = 'الصلاحيات الفردية';

    protected static ?string $icon = 'heroicon-o-key';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('permission_id')
                ->label('الصلاحية')
                ->options(
                    Permission::orderBy('group')
                        ->get()
                        ->mapWithKeys(fn ($p) => [
                            $p->id => "[{$p->group}] {$p->name_ar}",
                        ])
                )
                ->searchable()
                ->required()
                ->columnSpanFull(),

            Forms\Components\Select::make('type')
                ->label('النوع')
                ->options([
                    'grant'  => '✅ منح (Grant)',
                    'revoke' => '🚫 سحب (Revoke)',
                ])
                ->default('grant')
                ->required(),

            Forms\Components\DateTimePicker::make('expires_at')
                ->label('تاريخ الانتهاء')
                ->helperText('اتركه فارغًا للصلاحية الدائمة')
                ->nullable(),

            Forms\Components\Textarea::make('reason')
                ->label('السبب')
                ->placeholder('سبب منح أو سحب الصلاحية...')
                ->rows(2)
                ->columnSpanFull(),

            Forms\Components\Hidden::make('granted_by')
                ->default(fn () => auth()->id()),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('permission.group')
                    ->label('المجموعة')
                    ->badge()
                    ->color('info')
                    ->sortable(),

                Tables\Columns\TextColumn::make('permission.name_ar')
                    ->label('الصلاحية')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('permission.slug')
                    ->label('المعرّف')
                    ->color('gray')
                    ->size('sm'),

                Tables\Columns\BadgeColumn::make('type')
                    ->label('النوع')
                    ->colors([
                        'success' => 'grant',
                        'danger'  => 'revoke',
                    ])
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'grant'  => '✅ منح',
                        'revoke' => '🚫 سحب',
                        default  => $state,
                    }),

                Tables\Columns\TextColumn::make('expires_at')
                    ->label('الانتهاء')
                    ->dateTime('Y-m-d H:i')
                    ->placeholder('دائمة')
                    ->color(fn ($record) => $record->expires_at && $record->expires_at->isPast() ? 'danger' : 'gray'),

                Tables\Columns\TextColumn::make('reason')
                    ->label('السبب')
                    ->limit(40)
                    ->tooltip(fn ($record) => $record->reason),

                Tables\Columns\TextColumn::make('grantedByUser.name_ar')
                    ->label('بواسطة')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('التاريخ')
                    ->dateTime('Y-m-d')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('النوع')
                    ->options([
                        'grant'  => '✅ منح',
                        'revoke' => '🚫 سحب',
                    ]),

                Tables\Filters\SelectFilter::make('permission_group')
                    ->label('المجموعة')
                    ->options(
                        Permission::select('group')
                            ->distinct()
                            ->orderBy('group')
                            ->pluck('group', 'group')
                    )
                    ->query(fn ($query, array $data) =>
                        $data['value']
                            ? $query->whereHas('permission', fn ($q) => $q->where('group', $data['value']))
                            : $query
                    ),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('إضافة صلاحية')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['granted_by'] = auth()->id();
                        return $data;
                    }),

                Tables\Actions\Action::make('bulk_grant')
                    ->label('منح مجموعة')
                    ->icon('heroicon-o-squares-plus')
                    ->color('success')
                    ->form([
                        Forms\Components\CheckboxList::make('permission_ids')
                            ->label('اختر الصلاحيات')
                            ->options(
                                Permission::orderBy('group')
                                    ->get()
                                    ->mapWithKeys(fn ($p) => [
                                        $p->id => "[{$p->group}] {$p->name_ar}",
                                    ])
                            )
                            ->columns(2)
                            ->bulkToggleable()
                            ->searchable()
                            ->required(),

                        Forms\Components\Textarea::make('reason')
                            ->label('السبب')
                            ->placeholder('سبب منح هذه الصلاحيات...')
                            ->rows(2),
                    ])
                    ->action(function (array $data): void {
                        $user = $this->getOwnerRecord();
                        $created = 0;

                        foreach ($data['permission_ids'] as $permId) {
                            $exists = $user->userPermissions()
                                ->where('permission_id', $permId)
                                ->exists();

                            if (!$exists) {
                                $user->userPermissions()->create([
                                    'permission_id' => $permId,
                                    'type'          => 'grant',
                                    'granted_by'    => auth()->id(),
                                    'reason'        => $data['reason'] ?? null,
                                ]);
                                $created++;
                            } else {
                                // تحديث النوع إلى grant إذا كان revoke
                                $user->userPermissions()
                                    ->where('permission_id', $permId)
                                    ->where('type', 'revoke')
                                    ->update([
                                        'type'       => 'grant',
                                        'granted_by' => auth()->id(),
                                        'reason'     => $data['reason'] ?? null,
                                    ]);
                            }
                        }

                        $user->flushPermissionCache();

                        \Filament\Notifications\Notification::make()
                            ->title("تم منح {$created} صلاحية جديدة")
                            ->success()
                            ->send();
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->label('حذف'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('permission.group');
    }
}
