<?php

namespace App\Filament\Pages;

use App\Services\FinancialReportingService;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;

class FinancialReportsPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    public static function getNavigationGroup(): ?string
    {
        return __('command.navigation_group');
    }

    protected static ?int $navigationSort = 9;

    protected static string $view = 'filament.pages.financial-reports';

    protected static ?string $slug = 'financial-reports';

    // Form state
    public ?string $scope = 'company';
    public ?int $scope_id = null;
    public ?string $period_start = null;
    public ?string $period_end = null;

    // Report results
    public ?array $impactAnalysis = null;
    public ?array $predictiveData = null;

    public static function getNavigationLabel(): string
    {
        return __('command.financial_reports');
    }

    public function getTitle(): string
    {
        return __('command.delay_impact_title');
    }

    public function mount(): void
    {
        $this->period_start = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->period_end = Carbon::now()->format('Y-m-d');

        $this->generateReport();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(['default' => 1, 'lg' => 4])
                    ->schema([
                        Forms\Components\Select::make('scope')
                            ->label(__('command.scope'))
                            ->options([
                                'company'    => __('command.scope_company'),
                                'branch'     => __('command.scope_branch'),
                                'department' => __('command.scope_department'),
                                'employee'   => __('command.scope_employee'),
                            ])
                            ->default('company')
                            ->live(),

                        Forms\Components\Select::make('scope_id')
                            ->label(fn () => match ($this->scope) {
                                'branch'     => __('command.scope_branch'),
                                'department' => __('command.scope_department'),
                                'employee'   => __('command.scope_employee'),
                                default      => __('command.scope'),
                            })
                            ->options(function () {
                                return match ($this->scope) {
                                    'branch'     => \App\Models\Branch::active()->pluck('name_ar', 'id'),
                                    'department' => \App\Models\Department::where('is_active', true)->pluck('name_ar', 'id'),
                                    'employee'   => \App\Models\User::active()->pluck('name_ar', 'id'),
                                    default      => [],
                                };
                            })
                            ->searchable()
                            ->visible(fn () => $this->scope !== 'company'),

                        Forms\Components\DatePicker::make('period_start')
                            ->label(__('command.period_start'))
                            ->required(),

                        Forms\Components\DatePicker::make('period_end')
                            ->label(__('command.period_end'))
                            ->required(),
                    ]),
            ]);
    }

    public function generateReport(): void
    {
        $service = app(FinancialReportingService::class);

        $this->impactAnalysis = $service->getDelayImpactAnalysis(
            $this->period_start ?? Carbon::now()->startOfMonth()->format('Y-m-d'),
            $this->period_end ?? Carbon::now()->format('Y-m-d'),
            $this->scope ?? 'company',
            $this->scope_id
        );

        $this->predictiveData = $service->getPredictiveMonthlyLoss(Carbon::now());
    }
}
