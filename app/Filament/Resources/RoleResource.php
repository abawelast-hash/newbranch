<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RoleResource\Pages;
use App\Models\Permission;
use App\Models\Role;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Gate;

/**
 * SARH v4.1 — الأدوار فخرية فقط
 *
 * الأدوار لا تؤثر على الصلاحيات الفعلية.
 * الصلاحيات تُدار فرديًا عبر UserPermission في صفحة تعديل المستخدم.
 * الدور مجرد مسمى وظيفي / تصنيفي.
 */
class RoleResource extends Resource
{
    protected static ?string $model = Role::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?int $navigationSort = 20;

    public static function getNavigationGroup(): ?string
    {
        return 'الأمان والصلاحيات';
    }

    public static function getNavigationLabel(): string
    {
        return 'الأدوار (فخرية)';
    }

    public static function getModelLabel(): string
    {
        return 'دور';
    }

    public static function getPluralModelLabel(): string
    {
        return 'الأدوار';
    }

    /**
     * Only Level 10 / super_admin can access.
     */
    public static function canAccess(): bool
    {
        $user = auth()->user();
        return $user && ($user->is_super_admin || $user->security_level >= 10);
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public static function form(Form $form): Form
    {
        $permissionGroups = Permission::all()->groupBy('group');

        return $form->schema([
            Forms\Components\Section::make('⚠️ الأدوار فخرية فقط')
                ->description('تعيين الدور لا يمنح صلاحيات تلقائيًا. الصلاحيات تُدار فرديًا من صفحة تعديل الموظف ← تبويب "الصلاحيات الفردية".')
                ->icon('heroicon-o-exclamation-triangle')
                ->collapsed(false)
                ->schema([]),

            Forms\Components\Section::make('بيانات الدور')
                ->description('تعريف الدور ومستوى الأمان')
                ->schema([
                    Forms\Components\TextInput::make('name_ar')
                        ->label('الاسم بالعربية')
                        ->required()
                        ->maxLength(255)
                        ->hintIcon('heroicon-m-information-circle', tooltip: __('users.role_name_ar_hint')),

                    Forms\Components\TextInput::make('name_en')
                        ->label('الاسم بالإنجليزية')
                        ->required()
                        ->maxLength(255)
                        ->hintIcon('heroicon-m-information-circle', tooltip: __('users.role_name_en_hint')),

                    Forms\Components\TextInput::make('slug')
                        ->label('المعرّف')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(100)
                        ->hintIcon('heroicon-m-information-circle', tooltip: __('users.role_slug_hint'))
                        ->helperText('معرّف فريد — مثال: hr-manager'),

                    Forms\Components\Select::make('level')
                        ->label('مستوى الأمان')
                        ->options(fn () => collect(range(1, 10))->mapWithKeys(fn ($i) => [
                            $i => match ($i) {
                                10 => "المستوى {$i} — مالك النظام",
                                9  => "المستوى {$i} — مدير أمن",
                                8  => "المستوى {$i} — مدير عام",
                                7  => "المستوى {$i} — مدير إقليمي",
                                6  => "المستوى {$i} — مدير فرع",
                                5  => "المستوى {$i} — مشرف",
                                4  => "المستوى {$i} — رئيس قسم",
                                3  => "المستوى {$i} — موظف أول",
                                2  => "المستوى {$i} — موظف",
                                1  => "المستوى {$i} — متدرب",
                            },
                        ]))
                        ->required()
                        ->native(false)
                        ->hintIcon('heroicon-m-information-circle', tooltip: __('users.role_level_hint')),

                    Forms\Components\Textarea::make('description_ar')
                        ->label('الوصف بالعربية')
                        ->rows(2)
                        ->hintIcon('heroicon-m-information-circle', tooltip: __('users.role_description_ar_hint')),

                    Forms\Components\Textarea::make('description_en')
                        ->label('الوصف بالإنجليزية')
                        ->rows(2)
                        ->hintIcon('heroicon-m-information-circle', tooltip: __('users.role_description_en_hint')),

                    Forms\Components\Toggle::make('is_system')
                        ->label('دور نظامي (لا يمكن حذفه)')
                        ->disabled(fn (?Role $record) => $record?->is_system ?? false)
                        ->hintIcon('heroicon-m-information-circle', tooltip: __('users.role_is_system_hint')),
                ])->columns(['default' => 1, 'lg' => 2]),

            // ── Permission Matrix (reference only — does NOT grant permissions) ──
            Forms\Components\Section::make('مصفوفة الصلاحيات (مرجعية فقط)')
                ->description('⚠️ هذه الصلاحيات مرجعية فقط ولا تُمنح تلقائيًا للمستخدمين. لمنح صلاحيات فعلية، استخدم تبويب "الصلاحيات الفردية" في صفحة تعديل الموظف.')
                ->icon('heroicon-o-eye')
                ->collapsed(true)
                ->schema([
                    Forms\Components\CheckboxList::make('permissions')
                        ->label('')
                        ->relationship('permissions', 'name_ar')
                        ->columns(['default' => 1, 'lg' => 3])
                        ->bulkToggleable()
                        ->searchable()
                        ->hintIcon('heroicon-m-information-circle', tooltip: __('users.role_permissions_hint'))
                        ->descriptions(
                            Permission::all()->pluck('description_en', 'id')->toArray()
                        ),
                ]),
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

                Tables\Columns\TextColumn::make('level')
                    ->label('المستوى')
                    ->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state >= 10 => 'danger',
                        $state >= 7  => 'warning',
                        $state >= 4  => 'info',
                        default      => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('permissions_count')
                    ->label('عدد الصلاحيات')
                    ->counts('permissions')
                    ->badge()
                    ->color('success'),

                Tables\Columns\TextColumn::make('users_count')
                    ->label('عدد المستخدمين')
                    ->counts('users')
                    ->badge()
                    ->color('warning'),

                Tables\Columns\IconColumn::make('is_system')
                    ->label('نظامي')
                    ->boolean(),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->hidden(fn (Role $record) => $record->is_system),
            ])
            ->bulkActions([])
            ->defaultSort('level', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListRoles::route('/'),
            'create' => Pages\CreateRole::route('/create'),
            'edit'   => Pages\EditRole::route('/{record}/edit'),
        ];
    }
}
