<?php

namespace App\Notifications\Middleware;

use App\Models\PaymentTransaction;

class CompletedPaymentTransaction
{
    /**
     * Payment transaction
     *
     * @var PaymentTransaction
     */
    protected $transaction;

    /**
     * Constructor
     *
     * @param PaymentTransaction $transaction
     */
    public function __construct(PaymentTransaction $transaction)
    {
        $this->transaction = $transaction;
    }

    /**
     * Process the queued job.
     *
     * @param mixed $job
     * @param callable $next
     * @return void
     */
    public function handle($job, $next)
    {
        if ($this->transaction->refresh()->isCompleted()) {
            return $next($job);
        }

        $job->release(60);
    }
}