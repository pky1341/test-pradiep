<?php

namespace App\Listeners;

use App\Events\ContactForm;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Jobs\SendContactFormNotification;
use Illuminate\Queue\InteractsWithQueue;

class HandleContactFormSubmission
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(ContactForm $event): void
    {
        SendContactFormNotification::dispatch($event->contactForm);
    }
}
