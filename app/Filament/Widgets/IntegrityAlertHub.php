<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\HasDashboardFilter;
use App\Models\TrapInteraction;
use App\Models\WhistleblowerReport;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class IntegrityAlertHub extends BaseWidget
{
    use HasDashboardFilter;

    protected static ?string $heading = null;

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    /**
     * Only visible to Level 10 (Super Admin) users.
     */
    public static function canView(): bool
    {
        $user = auth()->user();

        if (!$user) {
            return false;
        }

        return $user->is_super_admin || $user->security_level >= 10;
    }

    public function getHeading(): string
    {
        return __('command.integrity_hub_title') . ' — ' . $this->getPeriodLabel();
    }

    public function table(Table $table): Table
    {
        [$startDate, $endDate] = $this->getFilterDates();

        return $table
            ->query(
                TrapInteraction::query()
                    ->with(['user', 'trap'])
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->latest()
                    ->limit(15)
            )
            ->columns([
                Tables\Columns\TextColumn::make('trap.name')
                    ->label(__('command.trap_type'))
                    ->badge()
                    ->color('danger'),

                Tables\Columns\TextColumn::make('user.name_ar')
                    ->label(__('command.triggered_by')),

                Tables\Columns\TextColumn::make('risk_level')
                    ->label(__('command.risk_level'))
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'low'      => 'success',
                        'medium'   => 'warning',
                        'high'     => 'danger',
                        'critical' => 'danger',
                        default    => 'gray',
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('command.triggered_at'))
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_reviewed')
                    ->label(__('command.review_status'))
                    ->boolean(),
            ])
            ->paginated(false)
            ->headerActions([
                Tables\Actions\Action::make('wb_overview')
                    ->label(__('command.wb_reports_status'))
                    ->icon('heroicon-o-shield-exclamation')
                    ->color('warning')
                    ->badge(WhistleblowerReport::pending()->count())
                    ->url(fn () => route('filament.admin.pages.whistleblower-vault')),
            ]);
    }
}
