<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\User;

class ContactFormNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $contactForm;

    /**
     * Create a new notification instance.
     */
    public function __construct(User $contactForm)
    {
        $this->contactForm = $contactForm;
    }


    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New Contact Form Submission')
            ->greeting('Hello Admin!')
            ->line('You have received a new contact form submission.')
            ->line('Name: ' . $this->contactForm->name)
            ->line('Email: ' . $this->contactForm->email)
            ->line('Message:')
            ->line($this->contactForm->message)
            ->action('View in Dashboard', url('/admin/contact-forms'))
            ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
