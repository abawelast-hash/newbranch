<?php

namespace App\Filament\Pages;

use App\Models\Branch;
use App\Models\Department;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class BroadcastPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-signal';

    protected static ?string $navigationLabel = 'إرسال جماعي';

    protected static ?string $title = 'إرسال جماعي';

    protected static ?string $navigationGroup = 'التواصل';

    protected static ?int $navigationSort = 13;

    protected static string $view = 'filament.pages.broadcast';

    public ?string $subject = '';
    public ?string $body = '';
    public string $target_scope = 'all';
    public ?int $target_branch_id = null;
    public ?int $target_department_id = null;
    public string $channel = 'database';

    public function getFormSchema(): array
    {
        return [
            Forms\Components\Section::make('رسالة جماعية')
                ->icon('heroicon-o-signal')
                ->description('أرسل إشعاراً لمجموعة من الموظفين')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('subject')
                        ->label('العنوان')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),

                    Forms\Components\RichEditor::make('body')
                        ->label('نص الرسالة')
                        ->required()
                        ->columnSpanFull(),

                    Forms\Components\Select::make('target_scope')
                        ->label('النطاق')
                        ->options([
                            'all'        => 'جميع الموظفين',
                            'branch'     => 'فرع محدد',
                            'department' => 'قسم محدد',
                        ])
                        ->default('all')
                        ->required()
                        ->live(),

                    Forms\Components\Select::make('target_branch_id')
                        ->label('الفرع')
                        ->options(Branch::pluck('name_ar', 'id'))
                        ->searchable()
                        ->visible(fn (Forms\Get $get) => $get('target_scope') === 'branch'),

                    Forms\Components\Select::make('target_department_id')
                        ->label('القسم')
                        ->options(Department::pluck('name_ar', 'id'))
                        ->searchable()
                        ->visible(fn (Forms\Get $get) => $get('target_scope') === 'department'),

                    Forms\Components\Select::make('channel')
                        ->label('قناة الإرسال')
                        ->options([
                            'database' => 'إشعار داخلي',
                        ])
                        ->default('database')
                        ->required(),
                ]),
        ];
    }

    public function send(): void
    {
        $this->validate([
            'subject' => 'required|max:255',
            'body'    => 'required',
        ]);

        // Build target users query
        $query = User::where('status', 'active');

        match ($this->target_scope) {
            'branch'     => $query->where('branch_id', $this->target_branch_id),
            'department' => $query->where('department_id', $this->target_department_id),
            default      => null,
        };

        $users = $query->get();
        $count = 0;

        // Insert into notifications table (Filament database notifications)
        foreach ($users as $user) {
            DB::table('notifications')->insert([
                'id'              => \Illuminate\Support\Str::uuid()->toString(),
                'type'            => 'broadcast',
                'notifiable_type' => User::class,
                'notifiable_id'   => $user->id,
                'data'            => json_encode([
                    'title'   => $this->subject,
                    'body'    => strip_tags($this->body),
                    'sent_by' => auth()->user()->name_ar,
                ]),
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
            $count++;
        }

        $this->reset(['subject', 'body', 'target_scope', 'target_branch_id', 'target_department_id']);

        Notification::make()
            ->title('تم الإرسال بنجاح')
            ->body("تم إرسال الإشعار إلى {$count} موظف")
            ->success()
            ->send();
    }
}
