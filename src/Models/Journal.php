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

class Journal extends Model
{
    protected $table = 'accounting_journals';
    
    protected $dates = [
        'deleted_at',
        'updated_at'
    ];
    
    protected $fillable = [
        'ledger_id',
        'balance',
        'currency',
        'morphed_type',
        'morphed_id',
    ];
    
    protected $casts = [
        'balance' => 'int',
        'morphed_id' => 'int',
    ];

    protected static function boot(): void
    {
        parent::boot();
        
        static::creating(function (self $journal): void {
            // Always initialize with zero balance if currency is set
            if (!empty($journal->currency)) {
                $journal->balance = 0;
            } else {
                // Set balance directly in attributes to avoid currency check
                $journal->attributes['balance'] = 0;
            }
        });
        
        static::created(function (self $journal): void {
            // Reset balances to ensure they're in sync with transactions
            // Only if currency is set
            if (!empty($journal->currency)) {
                $journal->resetCurrentBalances();
            }
        });
    }

    public function morphed(): MorphTo
    {
        return $this->morphTo();
    }

    public function ledger(): BelongsTo
    {
        return $this->belongsTo(Ledger::class);
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
        // Only reset if currency is set
        if (empty($this->currency)) {
            $this->attributes['balance'] = 0;
            return new Money(0, new Currency('USD')); // Default currency
        }

        // Recalculate balance from transactions if any exist
        if ($this->transactions()->exists()) {
            $this->balance = $this->getBalance();
            $this->save();
        } else {
            // Otherwise, ensure balance is zero
            $this->balance = new Money(0, new Currency($this->currency));
            $this->save();
        }

        return $this->balance;
    }

    protected function getBalanceAttribute(mixed $value): Money
    {
        return new Money((int) $value, new Currency($this->currency));
    }

    protected function setBalanceAttribute(mixed $value): void
    {
        // If value is a Money object, extract amount and currency
        if ($value instanceof Money) {
            $this->attributes['balance'] = (int) $value->getAmount();
            $this->currency = $value->getCurrency()->getCode();
            return;
        }
        
        // If currency is not set, set a default
        if (empty($this->currency)) {
            $this->currency = 'USD'; // Default currency
        }
        
        // Handle both string and numeric values
        $amount = is_numeric($value) ? (int) $value : 0;
        $money = new Money($amount, new Currency($this->currency));
        
        $this->attributes['balance'] = (int) $money->getAmount();
    }

    public function getDebitBalanceOn(Carbon $date): Money
    {
        $balance = $this->transactions()
            ->where('post_date', '<=', $date)
            ->sum('debit') ?: 0;
            
        return new Money($balance, new Currency($this->currency));
    }

    public function transactionsReferencingObjectQuery(Model $object): HasMany
    {
        return $this->transactions()
            ->where('ref_class', $object::class)
            ->where('ref_class_id', $object->id);
    }

    public function getCreditBalanceOn(Carbon $date): Money
    {
        $balance = $this->transactions()
            ->where('post_date', '<=', $date)
            ->sum('credit') ?: 0;
            
        return new Money($balance, new Currency($this->currency));
    }

    public function getBalanceOn(Carbon $date): Money
    {
        return $this->getCreditBalanceOn($date)
            ->subtract($this->getDebitBalanceOn($date));
    }

    public function getCurrentBalance(): Money
    {
        return $this->getBalanceOn(Carbon::now());
    }

    public function getBalance(): Money
    {
        $balance = $this->transactions()->exists()
            ? $this->transactions()->sum('debit') - $this->transactions()->sum('credit')
            : 0;

        return new Money($balance, new Currency($this->currency));
    }

    public function getCurrentBalanceInDollars(): float
    {
        return $this->getCurrentBalance()->getAmount() / 100;
    }

    public function getBalanceInDollars(): float
    {
        $amount = $this->getBalance()->getAmount();
        return round($amount / 100, 2);
    }

    public function credit(
        mixed $value,
        ?string $memo = null,
        ?Carbon $post_date = null,
        ?string $transaction_group = null
    ): JournalTransaction {
        $value = $value instanceof Money
            ? $value
            : new Money($value, new Currency($this->currency));
            
        return $this->post($value, null, $memo, $post_date, $transaction_group);
    }

    public function debit(
        mixed $value,
        ?string $memo = null,
        ?Carbon $post_date = null,
        ?string $transaction_group = null
    ): JournalTransaction {
        $value = $value instanceof Money
            ? $value
            : new Money($value, new Currency($this->currency));
            
        return $this->post(null, $value, $memo, $post_date, $transaction_group);
    }

    public function creditDollars(
        float $value,
        ?string $memo = null,
        ?Carbon $post_date = null
    ): JournalTransaction {
        return $this->credit((int) ($value * 100), $memo, $post_date);
    }

    public function debitDollars(
        float $value,
        ?string $memo = null,
        ?Carbon $post_date = null
    ): JournalTransaction {
        return $this->debit((int) ($value * 100), $memo, $post_date);
    }

    public function getDollarsDebitedToday(): float
    {
        return $this->getDollarsDebitedOn(Carbon::now());
    }

    public function getDollarsCreditedToday(): float
    {
        return $this->getDollarsCreditedOn(Carbon::now());
    }

    public function getDollarsDebitedOn(Carbon $date): float
    {
        return $this->transactions()
            ->whereBetween('post_date', [
                $date->copy()->startOfDay(),
                $date->copy()->endOfDay()
            ])
            ->sum('debit') / 100;
    }

    public function getDollarsCreditedOn(Carbon $date): float
    {
        return $this->transactions()
            ->whereBetween('post_date', [
                $date->copy()->startOfDay(),
                $date->copy()->endOfDay()
            ])
            ->sum('credit') / 100;
    }

    private function post(
        ?Money $credit = null,
        ?Money $debit = null,
        ?string $memo = null,
        ?Carbon $post_date = null,
        ?string $transaction_group = null
    ): JournalTransaction {
        $currencyCode = ($credit ?? $debit)->getCurrency()->getCode();
        
        // Create the transaction
        $transaction = $this->transactions()->create([
            'credit' => $credit?->getAmount(),
            'debit' => $debit?->getAmount(),
            'memo' => $memo,
            'currency' => $currencyCode,
            'post_date' => $post_date ?? Carbon::now(),
            'transaction_group' => $transaction_group,
        ]);
        
        // Update the journal's balance
        $this->refresh();
        $this->balance = $this->getCurrentBalance();
        $this->save();
        
        return $transaction;
    }
}
