<?php

namespace App\Models;

use Akaunting\Money\Currency;
use Akaunting\Money\Money;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Giftcard extends Model
{
    use HasFactory;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'stock',
    ];

    /**
     * Get path for thumbnail
     *
     * @return string
     */
    public function path()
    {
        return "giftcards/{$this->id}";
    }

    /**
     * Get logo url
     *
     * @param $value
     * @return string
     */
    public function getThumbnailAttribute($value)
    {
        return $value ? url($value) : $value;
    }

    /**
     * The relationships that should always be loaded.
     *
     * @var array
     */
    protected $with = ['brand'];

    /**
     * Get value in money object
     *
     * @param $value
     * @return Money
     */
    public function getValueAttribute($value)
    {
        return new Money($value, new Currency($this->currency), true);
    }

    /**
     * Total amount in stock
     *
     * @return int
     */
    public function getStockAttribute()
    {
        return $this->contents()->doesntHave('buyer')->count();
    }

    /**
     * Get price in another currency
     *
     * @param User|null $user
     * @return Money
     */
    public function getPrice($user)
    {
        $currency = new Currency(optional($user)->currency ?: defaultCurrency());
        return app('exchanger')->convert($this->value, $currency);
    }

    /**
     * Get related brand
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function brand()
    {
        return $this->belongsTo(GiftcardBrand::class, 'brand_id', 'id');
    }

    /**
     * Giftcard contents
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function contents()
    {
        return $this->hasMany(GiftcardContent::class, 'giftcard_id', 'id');
    }
}
