<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WelcomeNotification extends Notification
{
    /**
     * Create a new notification instance.
     */
    public function __construct(
        public User $user
    ) {
        //
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Welcome to ' . config('app.name') . '!')
            ->greeting('Welcome, ' . $notifiable->name . '!')
            ->line('Thank you for joining ' . config('app.name') . ', your complete invoicing solution.')
            ->line('We\'re excited to help you streamline your billing and get paid faster.')
            ->line('---')
            ->line('**Here\'s what you can do:**')
            ->line('✓ Create professional invoices in seconds')
            ->line('✓ Send invoices via email with one click')
            ->line('✓ Accept online payments with Paystack')
            ->line('✓ Track invoice status and payment history')
            ->line('✓ Manage customers and business profiles')
            ->line('✓ Generate financial reports (P&L, income statements)')
            ->line('✓ Send automated payment reminders')
            ->line('---')
            ->line('**Quick Start Guide:**')
            ->line('1. **Set up your business profile** - Add your business details and logo')
            ->line('2. **Add customers** - Import or create customer records')
            ->line('3. **Create your first invoice** - Takes less than 2 minutes')
            ->line('4. **Send and get paid** - Email invoice with payment link')
            ->line('---')
            ->line('**Ready to get started?**')
            ->action('Create Your First Invoice Now', url('/app/invoices/create'))
            ->line('Need help? Check out our documentation or contact support anytime.')
            ->line('---')
            ->line('**Pro Tip:** Upgrade to our Professional or Business plan to unlock:')
            ->line('• Unlimited invoices and customers')
            ->line('• SMS & WhatsApp notifications')
            ->line('• Recurring invoices')
            ->line('• Priority support')
            ->action('View Plans', url('/app/subscription-plans'))
            ->line('Thank you for choosing ' . config('app.name') . '!')
            ->salutation('Best regards, The ' . config('app.name') . ' Team');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'welcome',
            'user_id' => $this->user->id,
            'user_name' => $this->user->name,
            'message' => 'Welcome to ' . config('app.name') . '! Get started by creating your first invoice.',
        ];
    }
}
