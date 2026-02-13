<?php

namespace App\Filament\Pages;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;

class Dashboard extends BaseDashboard
{
    use HasFiltersForm;

    protected static ?string $navigationIcon = 'heroicon-o-home';

    public function filtersForm(Form $form): Form
    {
        return $form
            ->schema([
                Section::make()
                    ->schema([
                        Select::make('period')
                            ->label(__('dashboard.period'))
                            ->options([
                                'today'  => __('dashboard.today'),
                                'week'   => __('dashboard.this_week'),
                                'month'  => __('dashboard.this_month'),
                                'year'   => __('dashboard.this_year'),
                                'custom' => __('dashboard.custom_range'),
                            ])
                            ->default('today')
                            ->reactive(),

                        DatePicker::make('start_date')
                            ->label(__('dashboard.start_date'))
                            ->visible(fn ($get) => $get('period') === 'custom')
                            ->maxDate(now()),

                        DatePicker::make('end_date')
                            ->label(__('dashboard.end_date'))
                            ->visible(fn ($get) => $get('period') === 'custom')
                            ->maxDate(now()),
                    ])
                    ->columns(4)
                    ->compact(),
            ]);
    }
}
