<?php

namespace App\Models;

use Akaunting\Money\Currency;
use Akaunting\Money\Money;
use App\Events\PaymentTransactionSaved;
use App\Models\Traits\Lock;
use App\Models\Traits\Uuid;
use App\Notifications\PaymentCredit;
use App\Notifications\PaymentDebit;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use UnexpectedValueException;

class PaymentTransaction extends Model
{
    use HasFactory, Uuid, Lock;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [];

    /**
     * The relationships that should always be loaded.
     *
     * @var array
     */
    protected $with = ['account'];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'formatted_value',
        'formatted_balance'
    ];

    /**
     * The event map for the model.
     *
     * @var array
     */
    protected $dispatchesEvents = [
        'saved' => PaymentTransactionSaved::class
    ];

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        static::created(function (self $record) {
            $user = $record->account->user;

            switch ($record->type) {
                case "receive":
                    $user->notify(new PaymentCredit($record));
                    break;
                case "send":
                    $user->notify(new PaymentDebit($record));
                    break;
            }
        });

        static::saved(function (self $record) {
            if ($record->status == "completed" && $record->balance->isZero()) {
                $account = $record->account->fresh();

                $record->updateQuietly([
                    'balance' => $account->balance,
                ]);
            }
        });
    }

    /**
     * Set base value from money object
     *
     * @param Money $value
     */
    public function setValueAttribute(Money $value)
    {
        $this->attributes['value'] = $value->getAmount();
    }

    /**
     * Get value as money object
     *
     * @param $value
     * @return Money
     */
    public function getValueAttribute($value)
    {
        return new Money($value, new Currency($this->account->currency));
    }

    /**
     * Get formatted value
     *
     * @return string
     */
    public function getFormattedValueAttribute()
    {
        return $this->value->format();
    }

    /**
     * Set base value from money object
     *
     * @param Money $value
     */
    public function setBalanceAttribute(Money $value)
    {
        $this->attributes['balance'] = $value->getAmount();
    }

    /**
     * Get balance as money object
     *
     * @param $value
     * @return Money
     */
    public function getBalanceAttribute($value)
    {
        return new Money($value ?? 0, new Currency($this->account->currency));
    }

    /**
     * Get formatted balance
     *
     * @return mixed
     */
    public function getFormattedBalanceAttribute()
    {
        return $this->balance->format();
    }

    /**
     * Complete gateway
     *
     * @return bool|void
     * @throws Exception
     */
    public function completeGateway()
    {
        if (!$this->isPendingGateway() || $this->type != "receive") {
            throw new Exception("Transaction is not a pending gateway.");
        }

        $gateway = app('multipay')->gateway($this->gateway_name);

        if ($gateway->verify($this->gateway_ref)) {
            FeatureLimit::paymentsDeposit()->setUsage($this->value, $this->account->user);
            return $this->update(['status' => 'completed']);
        }
    }

    /**
     * Complete transfer
     *
     * @return bool
     * @throws Exception
     */
    public function completeTransfer()
    {
        if (!$this->isPendingTransfer()) {
            throw new Exception("Transaction is not pending.");
        }

        switch ($this->type) {
            case "receive":
                FeatureLimit::paymentsDeposit()->setUsage($this->value, $this->account->user);
                break;
            case "send":
                FeatureLimit::paymentsWithdrawal()->setUsage($this->value, $this->account->user);
                break;
        }

        return $this->update(['status' => 'completed']);
    }

    /**
     * Check if transaction is completed
     *
     * @return bool
     */
    public function isCompleted()
    {
        return $this->status == "completed";
    }

    /**
     * Check if transaction is pending
     *
     * @return bool
     */
    public function isPending()
    {
        return in_array($this->status, ["pending-gateway", "pending-transfer"]);
    }

    /**
     * Check pending gateway
     *
     * @return bool
     */
    public function isPendingGateway()
    {
        return $this->status == "pending-gateway";
    }

    /**
     * Check pending transfer
     *
     * @return bool
     */
    public function isPendingTransfer()
    {
        return $this->status == "pending-transfer";
    }

    /**
     * Check if it is overdue
     *
     * @return bool
     */
    public function isPendingOverdue()
    {
        return $this->isPending() && $this->created_at->addHours(5) < now();
    }

    /**
     * Cancel transaction
     *
     * @return bool
     * @throws Exception
     */
    public function cancelPending()
    {
        if ($this->isPending()) {
            return $this->update(['status' => 'canceled']);
        }
    }

    /**
     * Get payment account
     *
     * @return BelongsTo
     */
    public function account()
    {
        return $this->belongsTo(PaymentAccount::class, 'payment_account_id', 'id');
    }

    /**
     * Scope pending query
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopePending($query)
    {
        return $query->whereIn('status', ['pending-transfer', 'pending-gateway']);
    }

    /**
     * Scope pending transfer query
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopePendingTransfer($query)
    {
        return $query->where('status', 'pending-transfer');
    }

    /**
     * Scope pending gateway query
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopePendingGateway($query)
    {
        return $query->where('status', 'pending-gateway');
    }
}
