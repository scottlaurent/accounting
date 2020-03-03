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
 * @property    Carbon                 $updated_at
 * @property    Carbon                 $post_date
 * @property    Carbon                 $created_at
 */
class Ledger extends Model
{

    /**
     * @var string
     */
	protected $table = 'accounting_ledgers';

    /**
	 *
	 */
	public function journals()
	{
		return $this->hasMany(Journal::class);
	}
	
	/**
     * Get all of the posts for the country.
     */
    public function journal_transactions()
    {
        return $this->hasManyThrough(JournalTransaction::class, Journal::class);
    }

    /**
     * @param $currency
     * @return Money
     */
	public function getCurrentBalance($currency)
	{
		if ($this->type == 'asset' || $this->type == 'expense') {
			$balance = $this->journal_transactions->sum('debit') - $this->journal_transactions->sum('credit');
		} else {
			$balance = $this->journal_transactions->sum('credit') - $this->journal_transactions->sum('debit');
		}
		
		return new Money($balance, new Currency($currency));
	}

    /**
     * @param $currency
     * @return float|int
     */
	public function getCurrentBalanceInDollars($currency)
	{
		return $this->getCurrentBalance($currency)->getAmount() / 100;
	}
	
	
}