<?php

namespace App\Filament\Resources\EmployeeReminderResource\Pages;

use App\Filament\Resources\EmployeeReminderResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEmployeeReminders extends ListRecords
{
    protected static string $resource = EmployeeReminderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
