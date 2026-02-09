<?php

namespace App\Filament\App\Resources\AttendanceResource\Pages;

use App\Filament\App\Resources\AttendanceResource;
use App\Models\AttendanceLog;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateAttendance extends CreateRecord
{
    protected static string $resource = AttendanceResource::class;

    public function getTitle(): string
    {
        return 'تسجيل الحضور';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    /**
     * حماية صارمة: فرض user_id + branch_id + IP من السيرفر.
     * لا نثق أبداً بالقيم القادمة من الـ client لهذه الحقول.
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        $data['user_id']         = $user->id;
        $data['branch_id']       = $user->branch_id;
        $data['check_in_ip']     = request()->ip();
        $data['check_in_device'] = request()->userAgent();
        $data['attendance_date'] = $data['attendance_date'] ?? now()->toDateString();
        $data['check_in_at']     = $data['check_in_at'] ?? now();
        $data['status']          = $data['status'] ?? 'present';

        return $data;
    }

    /**
     * منع التسجيل المزدوج: لا يمكن تسجيل حضور مرتين في نفس اليوم.
     */
    protected function handleRecordCreation(array $data): Model
    {
        $existingToday = AttendanceLog::query()
            ->where('user_id', auth()->id())
            ->whereDate('attendance_date', $data['attendance_date'] ?? today())
            ->first();

        if ($existingToday) {
            Notification::make()
                ->title('تم تسجيل الحضور مسبقاً')
                ->body('لقد سجّلت حضورك اليوم بالفعل. لا يمكن التسجيل مرتين في نفس اليوم.')
                ->danger()
                ->persistent()
                ->send();

            $this->halt();
        }

        return parent::handleRecordCreation($data);
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->title('تم تسجيل الحضور بنجاح ✓')
            ->body('تم تسجيل حضورك في ' . now()->format('H:i') . ' بتوقيت السيرفر.')
            ->success();
    }
}
