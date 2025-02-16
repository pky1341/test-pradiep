<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use App\Notifications\ContactFormNotification;

class SendContactFormNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $contactForm;

    /**
     * Create a new job instance.
     */
    public function __construct(User $contactForm)
    {
        $this->contactForm = $contactForm;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $adminEmail = config('mail.admin_address', env('ADMIN_EMAIL', 'admin@example.com'));
        Notification::route('mail', $adminEmail)
            ->notify(new ContactFormNotification($this->contactForm));
            
        $this->contactForm->update(['is_processed' => true]);
    }
}
