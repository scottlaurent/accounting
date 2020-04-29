<?php

declare(strict_types=1);

namespace Scottlaurent\Accounting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Money\Money;
use Money\Currency;
use Carbon\Carbon;

/**
 * @property    Money $balance
 * @property    string $currency
 * @property    Carbon $updated_at
 * @property    Carbon $post_date
 * @property    Carbon $created_at
 */
class Journal extends Model
{
    /**
     * @var string
     */
    protected $table = 'accounting_journals';

    public function morphed(): MorphTo
    {
        return $this->morphTo();
    }

    public function ledger(): BelongsTo
    {
        return $this->belongsTo(Ledger::class);
    }

    /**
     * @var array
     */
    protected $dates = [
        'deleted_at',
        'updated_at'
    ];

    protected static function boot()
    {
        parent::boot();
        static::created(function (Journal $journal) {
            $journal->resetCurrentBalances();
        });

        parent::boot();
    }

    public function setCurrency(string $currency): void
    {
        $this->currency = $currency;
    }

    public function assignToLedger(Ledger $ledger): void
    {
        $ledger->journals()->save($this);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(JournalTransaction::class);
    }

    public function resetCurrentBalances(): Money
    {
        $this->balance = $this->getBalance();
        $this->save();
        return $this->balance;
    }

    /**
     * @param Money|float $value
     */
    protected function getBalanceAttribute($value): Money
    {
        return new Money($value, new Currency($this->currency));
    }

    /**
     * @param Money|float $value
     */
    protected function setBalanceAttribute($value): void
    {
        $value = is_a($value, Money::class)
            ? $value
            : new Money($value, new Currency($this->currency));
        $this->attributes['balance'] = $value ? (int)$value->getAmount() : null;
    }

    /**
     * Get the debit only balance of the journal based on a given date.
     */
    public function getDebitBalanceOn(Carbon $date): Money
    {
        $balance = $this->transactions()->where('post_date', '<=', $date)->sum('debit') ?: 0;
        return new Money($balance, new Currency($this->currency));

    }

    public function transactionsReferencingObjectQuery(Model $object): HasMany
    {
        return $this
            ->transactions()
            ->where('ref_class', get_class($object))
            ->where('ref_class_id', $object->id);
    }

    /**
     * Get the credit only balance of the journal based on a given date.
     */
    public function getCreditBalanceOn(Carbon $date): Money
    {
        $balance = $this->transactions()->where('post_date', '<=', $date)->sum('credit') ?: 0;
        return new Money($balance, new Currency($this->currency));
    }

    /**
     * Get the balance of the journal based on a given date.
     */
    public function getBalanceOn(Carbon $date): Money
    {
        return $this->getCreditBalanceOn($date)->subtract($this->getDebitBalanceOn($date));
    }

    /**
     * Get the balance of the journal as of right now, excluding future transactions.
     */
    public function getCurrentBalance(): Money
    {
        return $this->getBalanceOn(Carbon::now());
    }

    /**
     * Get the balance of the journal.  This "could" include future dates.
     */
    public function getBalance(): Money
    {
        if ($this->transactions()->count() > 0) {
            $balance = $this->transactions()->sum('credit') - $this->transactions()->sum('debit');
        } else {
            $balance = 0;
        }

        return new Money($balance, new Currency($this->currency));
    }

    /**
     * Get the balance of the journal in dollars.  This "could" include future dates.
     * @return float|int
     */
    public function getCurrentBalanceInDollars()
    {
        return $this->getCurrentBalance()->getAmount() / 100;
    }

    /**
     * Get balance
     * @return float|int
     */
    public function getBalanceInDollars()
    {
        return $this->getBalance()->getAmount() / 100;
    }

    public function credit(
        $value,
        string $memo = null,
        Carbon $post_date = null,
        string $transaction_group = null
    ): JournalTransaction {
        $value = is_a($value, Money::class)
            ? $value
            : new Money($value, new Currency($this->currency));
        return $this->post($value, null, $memo, $post_date, $transaction_group);
    }

    public function debit(
        $value,
        string $memo = null,
        Carbon $post_date = null,
        $transaction_group = null
    ): JournalTransaction {
        $value = is_a($value, Money::class)
            ? $value
            : new Money($value, new Currency($this->currency));
        return $this->post(null, $value, $memo, $post_date, $transaction_group);
    }

    /**
     * Credit a journal by a given dollar amount
     * @param Money|float $value
     * @param string  $memo
     * @param Carbon $post_date
     * @return JournalTransaction
     */
    public function creditDollars($value, string $memo = null, Carbon $post_date = null): JournalTransaction
    {
        $value = (int)($value * 100);
        return $this->credit($value, $memo, $post_date);
    }

    /**
     * Debit a journal by a given dollar amount
     * @param Money|float $value
     * @param string $memo
     * @param Carbon $post_date
     * @return JournalTransaction
     */
    public function debitDollars($value, string $memo = null, Carbon $post_date = null): JournalTransaction
    {
        $value = (int)($value * 100);
        return $this->debit($value, $memo, $post_date);
    }

    /**
     * Calculate the dollar amount debited to a journal today
     * @return float|int
     */
    public function getDollarsDebitedToday()
    {
        $today = Carbon::now();
        return $this->getDollarsDebitedOn($today);
    }

    /**
     * Calculate the dollar amount credited to a journal today
     * @return float|int
     */
    public function getDollarsCreditedToday()
    {
        $today = Carbon::now();
        return $this->getDollarsCreditedOn($today);
    }

    /**
     * Calculate the dollar amount debited to a journal on a given day
     * @param Carbon $date
     * @return float|int
     */
    public function getDollarsDebitedOn(Carbon $date)
    {
        return $this
                ->transactions()
                ->whereBetween('post_date', [
                    $date->copy()->startOfDay(),
                    $date->copy()->endOfDay()
                ])
                ->sum('debit') / 100;
    }

    /**
     * Calculate the dollar amount credited to a journal on a given day
     * @param Carbon $date
     * @return float|int
     */
    public function getDollarsCreditedOn(Carbon $date)
    {
        return $this
                ->transactions()
                ->whereBetween('post_date', [
                    $date->copy()->startOfDay(),
                    $date->copy()->endOfDay()
                ])
                ->sum('credit') / 100;
    }

    private function post(
        Money $credit = null,
        Money $debit = null,
        string $memo = null,
        Carbon $post_date = null,
        string $transaction_group = null
    ): JournalTransaction {
        $transaction = new JournalTransaction;
        $transaction->credit = $credit ? $credit->getAmount() : null;
        $transaction->debit = $debit ? $debit->getAmount() : null;
        $currency_code = $credit
            ? $credit->getCurrency()->getCode()
            : $debit->getCurrency()->getCode();
        $transaction->memo = $memo;
        $transaction->currency = $currency_code;
        $transaction->post_date = $post_date ?: Carbon::now();
        $transaction->transaction_group = $transaction_group;
        $this->transactions()->save($transaction);
        return $transaction;
    }
}
