<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PermissionResource\Pages;
use App\Models\Permission;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * SARH v1.9.0 — Module 2: إدارة الصلاحيات
 */
class PermissionResource extends Resource
{
    protected static ?string $model = Permission::class;

    protected static ?string $navigationIcon = 'heroicon-o-key';

    protected static ?int $navigationSort = 21;

    public static function getNavigationGroup(): ?string
    {
        return __('الأمان والصلاحيات');
    }

    public static function getNavigationLabel(): string
    {
        return __('الصلاحيات');
    }

    public static function getModelLabel(): string
    {
        return __('صلاحية');
    }

    public static function getPluralModelLabel(): string
    {
        return __('الصلاحيات');
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();
        return $user && ($user->is_super_admin || $user->security_level >= 10);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('بيانات الصلاحية')
                ->schema([
                    Forms\Components\TextInput::make('name_ar')
                        ->label('الاسم بالعربية')
                        ->required()
                        ->maxLength(255)
                        ->hintIcon('heroicon-m-information-circle', tooltip: 'اسم الصلاحية كما يظهر في الواجهة'),

                    Forms\Components\TextInput::make('name_en')
                        ->label('الاسم بالإنجليزية')
                        ->required()
                        ->maxLength(255)
                        ->hintIcon('heroicon-m-information-circle', tooltip: 'اسم الصلاحية بالإنجليزية'),

                    Forms\Components\TextInput::make('slug')
                        ->label('المعرّف')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(100)
                        ->hintIcon('heroicon-m-information-circle', tooltip: 'معرّف فريد يُستخدم برمجياً للتحقق من الصلاحية')
                        ->helperText('مثال: view-attendance, manage-users'),

                    Forms\Components\Select::make('group')
                        ->label('المجموعة')
                        ->options([
                            'attendance'  => 'الحضور والانصراف',
                            'finance'     => 'المالية',
                            'users'       => 'المستخدمين',
                            'branches'    => 'الفروع',
                            'reports'     => 'التقارير',
                            'security'    => 'الأمان',
                            'competition' => 'المنافسة',
                            'messaging'   => 'الرسائل',
                            'system'      => 'النظام',
                        ])
                        ->required()
                        ->native(false)
                        ->searchable()
                        ->hintIcon('heroicon-m-information-circle', tooltip: 'المجموعة التي تنتمي إليها هذه الصلاحية'),

                    Forms\Components\Textarea::make('description_en')
                        ->label('الوصف')
                        ->rows(2)
                        ->columnSpanFull()
                        ->hintIcon('heroicon-m-information-circle', tooltip: 'وصف مختصر لما تسمح به هذه الصلاحية'),
                ])->columns(['default' => 1, 'lg' => 2]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name_ar')
                    ->label('الاسم')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('slug')
                    ->label('المعرّف')
                    ->badge()
                    ->color('info')
                    ->searchable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('group')
                    ->label('المجموعة')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'security'   => 'danger',
                        'finance'    => 'warning',
                        'attendance' => 'success',
                        'users'      => 'info',
                        default      => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('roles_count')
                    ->label('أدوار مرتبطة')
                    ->counts('roles')
                    ->badge()
                    ->color('success'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('group')
                    ->label('المجموعة')
                    ->options([
                        'attendance'  => 'الحضور والانصراف',
                        'finance'     => 'المالية',
                        'users'       => 'المستخدمين',
                        'branches'    => 'الفروع',
                        'reports'     => 'التقارير',
                        'security'    => 'الأمان',
                        'competition' => 'المنافسة',
                        'messaging'   => 'الرسائل',
                        'system'      => 'النظام',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ])
            ->defaultSort('group');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListPermissions::route('/'),
            'create' => Pages\CreatePermission::route('/create'),
            'edit'   => Pages\EditPermission::route('/{record}/edit'),
        ];
    }
}
