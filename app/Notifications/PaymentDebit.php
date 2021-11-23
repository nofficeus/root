<?php

namespace App\Notifications;

use App\Models\PaymentTransaction;
use App\Notifications\Middleware\CompletedPaymentTransaction;
use App\Notifications\Traits\Notifier;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class PaymentDebit extends Notification implements ShouldQueue
{
    use Queueable, Notifier;

    /**
     * Payment transaction
     *
     * @var PaymentTransaction
     */
    protected $transaction;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(PaymentTransaction $transaction)
    {
        $this->transaction = $transaction;
    }

    /**
     * Determine the time at which the job should timeout.
     *
     * @return \DateTime
     */
    public function retryUntil()
    {
        return now()->addHours(5);
    }

    /**
     * Get the middleware the job should pass through.
     *
     * @return array
     */
    public function middleware()
    {
        return [new CompletedPaymentTransaction($this->transaction)];
    }

    /**
     * Replacement parameters and Values
     *
     * @param $notifiable
     * @return array
     */
    protected function parameters($notifiable)
    {
        return [
            'value'           => $this->transaction->value->getValue(),
            'formatted_value' => $this->transaction->value->format(),
            'description'     => $this->transaction->description,
            'currency'        => $this->transaction->account->currency,
        ];
    }
}
