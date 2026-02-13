<?php

namespace App\Filament\App\Resources\LeaveResource\Pages;

use App\Filament\App\Resources\LeaveResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLeaves extends ListRecords
{
    protected static string $resource = LeaveResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('طلب إجازة جديد'),
        ];
    }
}
