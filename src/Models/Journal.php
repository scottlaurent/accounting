<?php

namespace Scottlaurent\Accounting\Models;

use Illuminate\Database\Eloquent\Model;
use Money\Money;
use Money\Currency;
use Carbon\Carbon;

/**
 * Class Journal
 * @package Scottlaurent\Accounting
 * @property    Money                  $balance
 * @property    string                 $currency
 * @property    Carbon                 $updated_at
 * @property    Carbon                 $post_date
 * @property    Carbon                 $created_at
 */
class Journal extends Model
{
	
	/**
	 * @var string
	 */
	protected $table = 'accounting_journals';
	
    /**
     * Get all of the morphed models.
     */
    public function morphed()
    {
        return $this->morphTo();
    }
	
	/**
	 * @var array
	 */
	protected $dates = [
		'post_date',
		'deleted_at',
		'udpated_at'
	];
	
	
	/**
	 * @param string $currency
	 */
	public function setCurrency($currency)
	{
		$this->currency = $currency;
	}
	
	
	/**
	 * @return \Illuminate\Database\Eloquent\Relations\HasMany
	 */
	public function transactions()
    {
        return $this->hasMany(JournalTransaction::class);
    }

	/**
	 * @internal Journal $journal
	 */
	protected static function boot()
	{
		static::created(function (Journal $journal) {
			$journal->resetCurrentBalances();
		});
	}
	
	/**
	 *
	 */
	public function resetCurrentBalances()
	{
		$this->balance = $this->getBalance();
		$this->save();
	}
	
	/**
	 * @param $value
	 * @return Money
	 */
	public function getBalanceAttribute($value) {
		return new Money($value, new Currency($this->currency));
	}
	
	/**
	 * @param $value
	 */
	public function setBalanceAttribute($value) {
		$value = is_a($value,Money::class)
			? $value
			: new Money($value, new Currency($this->currency));
		$this->attributes['balance'] = $value ? (int) $value->getAmount() : null;
	}
	
	/**
	 * @param Carbon $date
	 * @return Money
	 */
	public function getDebitBalanceOn(Carbon $date)
	{
		$balance = $this->transactions()->where('post_date', '<=', $date)->sum('debit') ?: 0;
		return new Money($balance, new Currency('USD'));

	}
	
	/**
	 * @param Carbon $date
	 * @return Money
	 */
	public function getCreditBalanceOn(Carbon $date)
	{
		$balance = $this->transactions()->where('post_date', '<=', $date)->sum('credit') ?: 0;
		return new Money($balance, new Currency($this->currency));
	}
	
	/**
	 * @param Carbon $date
	 * @return Money
	 */
	public function getBalanceOn(Carbon $date)
	{
		return $this->getCreditBalanceOn($date)->subtract($this->getDebitBalanceOn($date));
	}
	
	/**
	 * @return Money
	 */
	public function getCurrentBalance()
	{
		return $this->getBalanceOn(Carbon::now());
	}
	
	/**
	 * @return Money
	 */
	public function getBalance()
	{
		$balance = $this->transactions()->sum('credit') - $this->transactions()->sum('debit');
		return new Money($balance, new Currency($this->currency));
	}
	
	/**
	 * @return Money
	 */
	public function getCurrentBalanceInDollars()
	{
		return $this->getCurrentBalance()->getAmount() / 100;
	}
	
	/**
	 * @return Money
	 */
	public function getBalanceInDollars()
	{
		return $this->getBalance()->getAmount() / 100;
	}
	
	/**
	 * @param $value
	 * @param null $memo
	 * @param null $post_date
	 * @return JournalTransactions
	 */
	public function credit($value,$memo=null,$post_date=null)
	{
		$value = is_a($value,Money::class)
			? $value
			: new Money($value, new Currency($this->currency));
		return $this->post($value,null,$memo,$post_date);
	}
	
	/**
	 * @param $value
	 * @param null $memo
	 * @param null $post_date
	 * @return JournalTransactions
	 */
	public function creditDollars($value,$memo=null,$post_date=null)
	{
		$value = $value * 100;
		return $this->credit($value,$memo,$post_date);
	}
	
	/**
	 * @param $value
	 * @param null $memo
	 * @param null $post_date
	 * @return Journal
	 */
	public function debitDollars($value,$memo=null,$post_date=null)
	{
		$value = $value * 100;
		return $this->debit($value,$memo,$post_date);
	}
	
	/**
	 * @param $value
	 * @param null $memo
	 * @param null $post_date
	 * @return Journal
	 */
	public function debit($value,$memo=null,$post_date=null)
	{
		$value = is_a($value,Money::class)
			? $value
			: new Money($value, new Currency($this->currency));
		return $this->post(null,$value,$memo,$post_date);
	}
	
	/**
	 * @param $credit
	 * @param $debit
	 * @param $memo
	 * @param null $post_date
	 * @return JournalTransactions
	 */
	private function post($credit, $debit, $memo, $post_date = null) {
		$transaction = new JournalTransaction;
		$transaction->credit = $credit ? $credit->getAmount() : null;
		$transaction->debit = $debit ? $debit->getAmount() : null;
		$currency_code = $credit
			? $credit->getCurrency()->getCode()
			: $debit->getCurrency()->getCode();
		$transaction->memo = $memo;
		$transaction->currency = $currency_code;
		$transaction->post_date = $post_date ?: Carbon::now();
		$transaction;
		$this->transactions()->save($transaction);
		return $transaction;
	}
	
}