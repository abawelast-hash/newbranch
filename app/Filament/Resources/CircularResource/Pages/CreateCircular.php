<?php

namespace App\Filament\Resources\CircularResource\Pages;

use App\Filament\Resources\CircularResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCircular extends CreateRecord
{
    protected static string $resource = CircularResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
