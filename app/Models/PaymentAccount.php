<?php

namespace App\Models;

use Akaunting\Money\Currency;
use Akaunting\Money\Money;
use App\Exceptions\TransferException;
use App\Models\Traits\Lock;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use UnexpectedValueException;

class PaymentAccount extends Model
{
    use HasFactory, Lock;

    protected $balanceAttribute;
    protected $balanceOnTradeAttribute;
    protected $availableAttribute;
    protected $totalReceivedAttribute;
    protected $totalPendingReceiveAttribute;
    protected $totalSentAttribute;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * The relationships that should always be loaded.
     *
     * @var array
     */
    protected $with = ['user'];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = ['user'];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'min_transferable',
        'max_transferable',
        'total_pending_receive',
        'formatted_total_pending_receive',
        'balance_on_trade',
        'formatted_balance_on_trade',
        'balance',
        'formatted_balance',
        'available',
        'formatted_available',
        'total_received',
        'formatted_total_received',
        'total_sent',
        'formatted_total_sent',
        'symbol'
    ];

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
//        static::retrieved(function (self $record) {
//            if ($record->available->isNegative()) {
//                SystemLog::warning("Unexpected negative {$record->currency} balance of {$record->user->name}");
//            }
//        });

        static::creating(function (self $record) {
            $record->assignReference();
        });
    }

    /**
     * Cast base value to money
     *
     * @param $amount
     * @param false $convert
     * @return Money
     */
    protected function castMoney($amount, $convert = false)
    {
        return new Money($amount, new Currency($this->currency), $convert);
    }

    /**
     * Parse money from input
     *
     * @param $inputAmount
     * @return Money
     */
    public function parseMoney($inputAmount)
    {
        return $this->castMoney($inputAmount, true);
    }

    /**
     * Get referenced user
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * Get payment transaction
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function transactions()
    {
        return $this->hasMany(PaymentTransaction::class, 'payment_account_id', 'id');
    }

    /**
     * Assign unique reference
     *
     * @return void
     */
    public function assignReference()
    {
        while (is_null($this->reference) || !$this->hasUniqueReference()) {
            $this->reference = strtoupper(Str::random(10));
        }
    }

    /**
     * Check if reference is unique
     *
     * @return bool
     */
    protected function hasUniqueReference()
    {
        $query = static::withoutGlobalScopes()
            ->where('reference', $this->reference);

        if ($this->exists) {
            $query->where($this->getKeyName(), '!=', $this->getKey());
        }

        return !$query->exists();
    }

    /**
     * Get min transferable
     *
     * @return Money
     */
    public function getMinTransferableAttribute()
    {
        $value = Money::USD(settings()->get('min_payment'), true);
        return exchanger($value, new Currency($this->currency));
    }

    /**
     * Get max transferable
     *
     * @return Money
     */
    public function getMaxTransferableAttribute()
    {
        $value = Money::USD(settings()->get('max_payment'), true);
        return exchanger($value, new Currency($this->currency));
    }

    /**
     * Available a.k.a spendable
     *
     * @return Money
     */
    public function getAvailableAttribute()
    {
        if (!isset($this->availableAttribute)) {
            $this->availableAttribute = $this->balance->subtract($this->balance_on_trade);
        }
        return $this->availableAttribute;
    }

    /**
     * Formatted available
     *
     * @return mixed
     */
    public function getFormattedAvailableAttribute()
    {
        return $this->available->format();
    }

    /**
     * Balance on trade
     *
     * @return Money
     */
    public function getBalanceOnTradeAttribute()
    {
        if (!isset($this->balanceOnTradeAttribute)) {
            $exchangeTrade = $this->exchangeTrades()
                ->where('type', 'buy')->where('status', 'pending')
                ->sum('payment_value');

            $this->balanceOnTradeAttribute = $this->castMoney($exchangeTrade);
        }
        return $this->balanceOnTradeAttribute;
    }

    /**
     * Formatted balance on trade
     *
     * @return string
     */
    public function getFormattedBalanceOnTradeAttribute()
    {
        return $this->balance_on_trade->format();
    }

    /**
     * Calculate Balance
     *
     * @return Money
     */
    public function getBalanceAttribute()
    {
        if (!isset($this->balanceAttribute)) {
            $this->balanceAttribute = $this->total_received->subtract($this->total_sent);
        }
        return $this->balanceAttribute;
    }

    /**
     * Get formatted balance object
     *
     * @return string
     */
    public function getFormattedBalanceAttribute()
    {
        return $this->balance->format();
    }

    /**
     * Total sent query
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    protected function totalSentQuery()
    {
        return $this->transactions()
            ->where('status', '!=', 'canceled')
            ->where('type', 'send');
    }

    /**
     * Sum total sent
     *
     * @return Money
     */
    public function getTotalSentAttribute()
    {
        if (!isset($this->totalSentAttribute)) {
            $this->totalSentAttribute = $this->castMoney(
                $this->totalSentQuery()->sum('value')
            );
        }
        return $this->totalSentAttribute;
    }

    /**
     * Format total sent
     *
     * @return string
     */
    public function getFormattedTotalSentAttribute()
    {
        return $this->total_sent->format();
    }

    /**
     * Total received query
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    protected function totalReceivedQuery()
    {
        return $this->transactions()
            ->where('status', 'completed')
            ->where('type', 'receive');
    }

    /**
     * Sum total received
     *
     * @return Money
     */
    public function getTotalReceivedAttribute()
    {
        if (!isset($this->totalReceivedAttribute)) {
            $this->totalReceivedAttribute = $this->castMoney(
                $this->totalReceivedQuery()->sum('value')
            );
        }
        return $this->totalReceivedAttribute;
    }

    /**
     * Get formatted Total received.
     *
     * @return string
     */
    public function getFormattedTotalReceivedAttribute()
    {
        return $this->total_received->format();
    }

    /**
     * Total pending receive query
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    protected function totalPendingReceiveQuery()
    {
        return $this->transactions()
            ->whereIn('status', ['pending-transfer', 'pending-gateway'])
            ->where('type', 'receive');
    }

    /**
     * Has maximum pending
     *
     * @return bool
     */
    public function hasMaximumPending()
    {
        return $this->totalPendingReceiveQuery()->count() > 2;
    }

    /**
     * Sum total pending receive
     *
     * @return Money
     */
    public function getTotalPendingReceiveAttribute()
    {
        if (!isset($this->totalPendingReceiveAttribute)) {
            $this->totalPendingReceiveAttribute = $this->castMoney(
                $this->totalPendingReceiveQuery()->sum('value')
            );
        }
        return $this->totalPendingReceiveAttribute;
    }

    /**
     * Get formatted total pending receive.
     *
     * @return string
     */
    public function getFormattedTotalPendingReceiveAttribute()
    {
        return $this->total_pending_receive->format();
    }

    /**
     * Supported currency
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function supportedCurrency()
    {
        return $this->belongsTo(SupportedCurrency::class, 'currency', 'code');
    }

    /**
     * Related exchange trades
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function exchangeTrades()
    {
        return $this->hasMany(ExchangeTrade::class, 'payment_account_id', 'id');
    }

    /**
     * Get symbol attribute
     *
     * @return string
     */
    public function getSymbolAttribute()
    {
        $currency = new Currency($this->currency);
        return $currency->getSymbol();
    }

    /**
     * Credit account
     *
     * @param Money $amount
     * @param $description
     * @return PaymentTransaction|Model
     */
    public function credit(Money $amount, $description)
    {
        $value = $this->validateAmount($amount);

        return $this->transactions()->create([
            'type'        => 'receive',
            'status'      => 'completed',
            'description' => $description,
            'value'       => $value
        ]);
    }

    /**
     * Debit account
     *
     * @param Money $amount
     * @param $description
     * @return PaymentTransaction|Model
     */
    public function debit(Money $amount, $description)
    {
        $value = $this->validateAmount($amount);

        return $this->transactions()->create([
            'type'        => 'send',
            'status'      => 'completed',
            'description' => $description,
            'value'       => $value
        ]);
    }

    /**
     * Create withdrawal request
     *
     * @param Money $amount
     * @param BankAccount|Model $bankAccount
     * @return PaymentTransaction
     */
    public function sendViaTransfer(Money $amount, BankAccount $bankAccount)
    {
        $value = $this->validateAmount($amount);

        return $this->acquireLock(function (self $account) use ($value, $bankAccount) {
            if ($account->available->lessThan($value)) {
                throw new TransferException(trans('payment.insufficient_balance'), 422);
            }

            return $account->transactions()->create([
                'type'                 => 'send',
                'status'               => 'pending-transfer',
                'value'                => $value,
                'description'          => $bankAccount->transferDescription(),
                'transfer_bank'        => $bankAccount->bank_name,
                'transfer_beneficiary' => $bankAccount->beneficiary,
                'transfer_number'      => $bankAccount->number,
                'transfer_country'     => $bankAccount->country,
                'transfer_note'        => $bankAccount->note,
            ]);
        });
    }

    /**
     * Create transfer receive
     *
     * @param Money $amount
     * @param BankAccount $bankAccount
     * @return PaymentTransaction|Model
     */
    public function receiveViaTransfer(Money $amount, BankAccount $bankAccount)
    {
        $value = $this->validateAmount($amount);

        return $this->transactions()->create([
            'type'                 => 'receive',
            'status'               => 'pending-transfer',
            'value'                => $value,
            'description'          => $bankAccount->transferDescription(),
            'transfer_bank'        => $bankAccount->bank_name,
            'transfer_beneficiary' => $bankAccount->beneficiary,
            'transfer_number'      => $bankAccount->number,
            'transfer_country'     => $bankAccount->country,
            'transfer_note'        => $bankAccount->note,
        ]);
    }

    /**
     * Create Gateway receive
     *
     * @param Money $amount
     * @param Collection $gatewayData
     * @return PaymentTransaction|Model
     */
    public function receiveViaGateway(Money $amount, Collection $gatewayData)
    {
        $data = $this->prepareGatewayData($gatewayData);
        $value = $this->validateAmount($amount);

        return $this->transactions()->create([
            'id'           => $data->get('uuid'),
            'type'         => 'receive',
            'status'       => 'pending-gateway',
            'value'        => $value,
            'description'  => $data->get('description'),
            'gateway_ref'  => $data->get('ref'),
            'gateway_name' => $data->get('name'),
            'gateway_url'  => $data->get('url'),
        ]);
    }

    /**
     * Validate Gateway data
     *
     * @param Collection $data
     * @return Collection
     */
    protected function prepareGatewayData(Collection $data)
    {
        return tap($data, function (Collection $data) {
            $validator = Validator::make($data->all(), [
                'uuid' => 'nullable|uuid',
                'ref'  => 'required|string',
                'name' => 'required|string',
                'url'  => 'required|url',
            ]);

            $data->put('description', "{$data->get('name')}: {$data->get('ref')}");

            if ($validator->fails()) {
                throw new UnexpectedValueException("Invalid gateway data");
            }
        });
    }

    /**
     * Validate amount
     *
     * @param Money $amount
     * @return Money
     */
    protected function validateAmount(Money $amount)
    {
        return tap($amount, function (Money $amount) {
            if ($this->currency != $amount->getCurrency()->getCurrency()) {
                throw new UnexpectedValueException("Unexpected currency.");
            }
        });
    }

    /**
     * Get daily chart data
     *
     * @param int|null $month
     * @param int|null $year
     * @return array
     */
    public function getDailyChartData(int $month = null, int $year = null)
    {
        $starts = Carbon::createFromDate($year ?: now()->year, $month ?: now()->month, 1);
        $ends = $starts->clone()->endOfMonth();

        $receiveAggregate = $this->totalReceivedQuery()
            ->selectRaw('sum(value) as total')
            ->selectRaw('day(created_at) as day')
            ->whereDate('created_at', '>=', $starts)
            ->whereDate('created_at', '<=', $ends)
            ->groupBy('day')->get()
            ->pluck('total', 'day');

        $sendAggregate = $this->totalSentQuery()
            ->selectRaw('day(created_at) as day')
            ->selectRaw('sum(value) as total')
            ->whereDate('created_at', '>=', $starts)
            ->whereDate('created_at', '<=', $ends)
            ->groupBy('day')->get()
            ->pluck('total', 'day');

        return tap(new Collection(), function ($data) use ($starts, $receiveAggregate, $sendAggregate) {
            for ($day = 1; $day <= $starts->daysInMonth; $day++) {
                $totalSent = $this->castMoney($sendAggregate->get($day, 0));
                $totalReceived = $this->castMoney($receiveAggregate->get($day, 0));
                $current = $starts->clone()->day($day);

                $data->push([
                    'total_sent'               => $totalSent->getValue(),
                    'formatted_total_sent'     => $totalSent->format(),
                    'total_received'           => $totalReceived->getValue(),
                    'formatted_total_received' => $totalReceived->format(),
                    'date'                     => $current->toDateString(),
                ]);
            }
        });
    }
}
