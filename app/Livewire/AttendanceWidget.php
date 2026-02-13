<?php

namespace App\Livewire;

use App\Models\AttendanceLog;
use App\Models\WhistleblowerReport;
use App\Services\AttendanceService;
use App\Services\GeofencingService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class AttendanceWidget extends Component
{
    public ?string $status = null;
    public ?string $checkInTime = null;
    public ?string $checkOutTime = null;
    public bool $isInsideGeofence = false;
    public float $distanceMeters = 0;
    public float $geofenceRadius = 0;
    public string $message = '';
    public string $messageType = ''; // success | error

    // Whistleblower form
    public bool $showWhistleblowerForm = false;
    public string $wbCategory = '';
    public string $wbSeverity = 'medium';
    public string $wbContent = '';
    public ?string $wbTicket = null;
    public ?string $wbToken = null;

    public function mount(): void
    {
        $this->loadTodayStatus();
        $this->loadGeofenceInfo();
    }

    public function loadTodayStatus(): void
    {
        $user = Auth::user();
        $today = AttendanceLog::where('user_id', $user->id)
            ->where('attendance_date', now()->toDateString())
            ->first();

        if ($today) {
            $this->checkInTime = $today->check_in_at?->format('H:i');
            $this->checkOutTime = $today->check_out_at?->format('H:i');
            $this->status = $today->check_out_at ? 'checked_out' : 'checked_in';
        } else {
            $this->status = 'not_checked_in';
        }
    }

    public function loadGeofenceInfo(): void
    {
        $user = Auth::user();
        if ($user->branch) {
            $this->geofenceRadius = (float) $user->branch->geofence_radius;
        }
    }

    public function updateGeolocation(float $latitude, float $longitude): void
    {
        $user = Auth::user();
        if (!$user->branch) {
            return;
        }

        $geo = (new GeofencingService())->validatePosition($user->branch, $latitude, $longitude);
        $this->distanceMeters = round($geo['distance_meters']);
        $this->isInsideGeofence = $geo['within_geofence'];
    }

    public function checkIn(float $latitude, float $longitude): void
    {
        try {
            $service = new AttendanceService(new GeofencingService());
            $log = $service->checkIn(Auth::user(), $latitude, $longitude);
            $this->checkInTime = $log->check_in_at->format('H:i');
            $this->status = 'checked_in';
            $this->message = __('pwa.check_in_success');
            $this->messageType = 'success';
        } catch (\Exception $e) {
            $this->message = $e->getMessage();
            $this->messageType = 'error';
        }
    }

    public function checkOut(float $latitude, float $longitude): void
    {
        try {
            $service = new AttendanceService(new GeofencingService());
            $log = $service->checkOut(Auth::user(), $latitude, $longitude);
            $this->checkOutTime = $log->check_out_at->format('H:i');
            $this->status = 'checked_out';
            $this->message = __('pwa.check_out_success');
            $this->messageType = 'success';
        } catch (\Exception $e) {
            $this->message = $e->getMessage();
            $this->messageType = 'error';
        }
    }

    public function toggleWhistleblowerForm(): void
    {
        $this->showWhistleblowerForm = !$this->showWhistleblowerForm;
        $this->wbTicket = null;
        $this->wbToken = null;
    }

    public function submitWhistleblowerReport(): void
    {
        $this->validate([
            'wbCategory' => 'required|in:fraud,corruption,harassment,safety,discrimination,other',
            'wbSeverity' => 'required|in:low,medium,high,critical',
            'wbContent'  => 'required|min:20',
        ]);

        $ticket = WhistleblowerReport::generateTicketNumber();
        $token  = WhistleblowerReport::generateAnonymousToken();

        $report = WhistleblowerReport::create([
            'ticket_number'   => $ticket,
            'category'        => $this->wbCategory,
            'severity'        => $this->wbSeverity,
            'status'          => 'new',
            'anonymous_token' => hash('sha256', $token),
        ]);

        $report->setContent($this->wbContent);
        $report->save();

        $this->wbTicket = $ticket;
        $this->wbToken  = $token;

        // Reset form fields
        $this->wbCategory = '';
        $this->wbSeverity = 'medium';
        $this->wbContent = '';
    }

    public function render()
    {
        return view('livewire.attendance-widget');
    }
}
