<?php

namespace App\Livewire;

use App\Models\Circular;
use App\Models\CircularAcknowledgment;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class CircularsWidget extends Component
{
    public $circulars = [];

    public function mount(): void
    {
        $this->loadCirculars();
    }

    public function loadCirculars(): void
    {
        /** @var \App\Models\User $user */
        $user   = Auth::user();
        $userId = $user->id;

        $this->circulars = Circular::active()
            ->forUser($user)                                   // ← فلتر الصلاحيات
            ->with(['acknowledgments' => function ($query) use ($userId) {
                $query->where('user_id', $userId);
            }])
            ->latest('published_at')
            ->take(5)
            ->get()
            ->map(function ($circular) use ($userId) {
                return [
                    'id'           => $circular->id,
                    'title'        => $circular->title,
                    'body'         => $circular->body,
                    'priority'     => $circular->priority,
                    'published_at' => $circular->published_at->diffForHumans(),
                    'acknowledged' => $circular->acknowledgments->isNotEmpty(),
                    'requires_ack' => $circular->requires_acknowledgment,
                ];
            })
            ->toArray();
    }

    public function acknowledge(int $circularId): void
    {
        CircularAcknowledgment::firstOrCreate([
            'circular_id'     => $circularId,
            'user_id'         => Auth::id(),
        ], [
            'acknowledged_at' => now(),
        ]);

        $this->loadCirculars();
    }

    public function render()
    {
        return view('livewire.circulars-widget');
    }
}
