<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CircularResource\Pages;
use App\Models\Branch;
use App\Models\Circular;
use App\Models\Department;
use App\Models\Role;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CircularResource extends Resource
{
    protected static ?string $model = Circular::class;

    protected static ?string $navigationIcon = 'heroicon-o-megaphone';

    protected static ?int $navigationSort = 10;

    public static function getNavigationGroup(): ?string
    {
        return __('circulars.navigation_group');
    }

    public static function getNavigationLabel(): string
    {
        return __('circulars.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('circulars.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('circulars.plural_model_label');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make(__('circulars.section_content'))
                ->icon('heroicon-o-document-text')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('title_ar')
                        ->label(__('circulars.title_ar'))
                        ->required()
                        ->maxLength(255),

                    Forms\Components\TextInput::make('title_en')
                        ->label(__('circulars.title_en'))
                        ->maxLength(255),

                    Forms\Components\RichEditor::make('body_ar')
                        ->label(__('circulars.body_ar'))
                        ->required()
                        ->columnSpanFull(),

                    Forms\Components\RichEditor::make('body_en')
                        ->label(__('circulars.body_en'))
                        ->columnSpanFull(),
                ]),

            Forms\Components\Section::make(__('circulars.section_settings'))
                ->icon('heroicon-o-cog-6-tooth')
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('priority')
                        ->label(__('circulars.priority'))
                        ->options([
                            'normal' => __('circulars.priority_normal'),
                            'high'   => __('circulars.priority_high'),
                            'urgent' => __('circulars.priority_urgent'),
                        ])
                        ->default('normal')
                        ->required(),

                    Forms\Components\Select::make('target_scope')
                        ->label(__('circulars.target_scope'))
                        ->options([
                            'all'        => __('circulars.scope_all'),
                            'branch'     => __('circulars.scope_branch'),
                            'department' => __('circulars.scope_department'),
                            'role'       => __('circulars.scope_role'),
                        ])
                        ->default('all')
                        ->required()
                        ->live(),

                    Forms\Components\Select::make('target_branch_id')
                        ->label(__('circulars.target_branch'))
                        ->options(Branch::pluck('name_ar', 'id'))
                        ->searchable()
                        ->visible(fn (Forms\Get $get) => $get('target_scope') === 'branch'),

                    Forms\Components\Select::make('target_department_id')
                        ->label(__('circulars.target_department'))
                        ->options(Department::pluck('name_ar', 'id'))
                        ->searchable()
                        ->visible(fn (Forms\Get $get) => $get('target_scope') === 'department'),

                    Forms\Components\Select::make('target_role_id')
                        ->label(__('circulars.target_role'))
                        ->options(Role::pluck('name_ar', 'id'))
                        ->searchable()
                        ->visible(fn (Forms\Get $get) => $get('target_scope') === 'role'),

                    Forms\Components\Toggle::make('requires_acknowledgment')
                        ->label(__('circulars.requires_acknowledgment'))
                        ->default(true),

                    Forms\Components\DateTimePicker::make('published_at')
                        ->label(__('circulars.published_at'))
                        ->default(now()),

                    Forms\Components\DateTimePicker::make('expires_at')
                        ->label(__('circulars.expires_at')),

                    Forms\Components\Hidden::make('created_by')
                        ->default(fn () => auth()->id()),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title_ar')
                    ->label(__('circulars.title_ar'))
                    ->searchable()
                    ->limit(50),

                Tables\Columns\BadgeColumn::make('priority')
                    ->label(__('circulars.priority'))
                    ->colors([
                        'gray'   => 'normal',
                        'warning' => 'high',
                        'danger'  => 'urgent',
                    ])
                    ->formatStateUsing(fn (string $state) => __("circulars.priority_{$state}")),

                Tables\Columns\TextColumn::make('target_scope')
                    ->label(__('circulars.target_scope'))
                    ->formatStateUsing(fn (string $state) => __("circulars.scope_{$state}")),

                Tables\Columns\IconColumn::make('requires_acknowledgment')
                    ->label(__('circulars.requires_acknowledgment'))
                    ->boolean(),

                Tables\Columns\TextColumn::make('acknowledgments_count')
                    ->label(__('circulars.acknowledgments_count'))
                    ->counts('acknowledgments'),

                Tables\Columns\TextColumn::make('published_at')
                    ->label(__('circulars.published_at'))
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('creator.name_ar')
                    ->label(__('circulars.created_by'))
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('published_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('priority')
                    ->label(__('circulars.priority'))
                    ->options([
                        'normal' => __('circulars.priority_normal'),
                        'high'   => __('circulars.priority_high'),
                        'urgent' => __('circulars.priority_urgent'),
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            'index'  => Pages\ListCirculars::route('/'),
            'create' => Pages\CreateCircular::route('/create'),
            'edit'   => Pages\EditCircular::route('/{record}/edit'),
        ];
    }
}
