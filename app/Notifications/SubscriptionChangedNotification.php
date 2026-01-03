<?php

namespace App\Notifications;

use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SubscriptionChangedNotification extends Notification
{

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Subscription $subscription,
        public Plan $oldPlan,
        public Plan $newPlan,
        public string $changeType, // 'upgrade' or 'downgrade'
        public ?float $creditIssued = null,
        public ?float $amountCharged = null
    ) {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
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
        $message = (new MailMessage)
            ->subject('Your Subscription Has Been ' . ucfirst($this->changeType) . 'd')
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('Your subscription plan has been ' . $this->changeType . 'd.');

        // Show plan change details
        $message->line('**Previous Plan:** ' . $this->oldPlan->name)
            ->line('**New Plan:** ' . $this->newPlan->name)
            ->line('**Billing Cycle:** ' . ucfirst($this->subscription->billing_cycle));

        // Show credit issued for downgrades
        if ($this->changeType === 'downgrade' && $this->creditIssued) {
            $message->line('---')
                ->line('**Credit Issued:** ₦' . number_format($this->creditIssued, 2))
                ->line('A prorated credit of ₦' . number_format($this->creditIssued, 2) . ' has been added to your account. This credit will be automatically applied to your next payment.');
        }

        // Show amount charged for upgrades
        if ($this->changeType === 'upgrade' && $this->amountCharged) {
            $message->line('---')
                ->line('**Amount Charged:** ₦' . number_format($this->amountCharged, 2))
                ->line('You have been charged ₦' . number_format($this->amountCharged, 2) . ' for the prorated upgrade amount.');
        }

        // Show new plan features
        $message->line('---')
            ->line('**Your New Plan Features:**');

        if ($this->newPlan->max_invoices == -1) {
            $message->line('• Unlimited invoices per month');
        } else {
            $message->line('• ' . $this->newPlan->max_invoices . ' invoices per month');
        }

        if ($this->newPlan->max_customers == -1) {
            $message->line('• Unlimited customers');
        } else {
            $message->line('• ' . $this->newPlan->max_customers . ' customers');
        }

        if ($this->newPlan->max_users == -1) {
            $message->line('• Unlimited team members');
        } else {
            $message->line('• ' . $this->newPlan->max_users . ' team members');
        }

        $message->line('---');

        if ($this->subscription->next_billing_date) {
            $message->line('Your next billing date is ' . $this->subscription->next_billing_date->format('F j, Y') . '.');
        }

        $message->action('View Subscription', url('/app/my-subscription'))
            ->line('Thank you for using Khan Invoice!');

        return $message;
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'subscription_id' => $this->subscription->id,
            'old_plan_id' => $this->oldPlan->id,
            'old_plan_name' => $this->oldPlan->name,
            'new_plan_id' => $this->newPlan->id,
            'new_plan_name' => $this->newPlan->name,
            'change_type' => $this->changeType,
            'billing_cycle' => $this->subscription->billing_cycle,
            'credit_issued' => $this->creditIssued,
            'amount_charged' => $this->amountCharged,
            'next_billing_date' => $this->subscription->next_billing_date->toDateString(),
        ];
    }
}
