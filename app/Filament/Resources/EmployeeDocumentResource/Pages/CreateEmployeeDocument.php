<?php

namespace App\Filament\Resources\EmployeeDocumentResource\Pages;

use App\Filament\Resources\EmployeeDocumentResource;
use Filament\Resources\Pages\CreateRecord;

class CreateEmployeeDocument extends CreateRecord
{
    protected static string $resource = EmployeeDocumentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['uploaded_by'] = auth()->id();
        
        // تحديد نوع الملف تلقائياً
        if (isset($data['file_path'])) {
            $extension = pathinfo($data['file_path'], PATHINFO_EXTENSION);
            $data['file_type'] = strtolower($extension);
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
