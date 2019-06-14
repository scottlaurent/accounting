<?php

namespace Scottlaurent\Accounting\ModelTraits;

use Scottlaurent\Accounting\Models\Journal;

/**
 * Class AccountingJournal
 * @package Scottlaurent\Accounting\ModelTraits
 */
trait AccountingJournal {
	
	
	/**
	 * Morph to Journal.
	 *
	 * @return mixed
	 */
	public function journal()
    {
        return $this->morphOne(Journal::class,'morphed');
    }

    /**
     * Initialize a journal for a given model object
     *
     * @param null|string $currency_code
     * @param null|string $ledger_id
     * @return mixed
     * @throws \Exception
     */
	public function initJournal(?string $currency_code = 'USD', ?string $ledger_id = null) {
    	if (!$this->journal) {
	        $journal = new Journal();
	        $journal->ledger_id = $ledger_id;
	        $journal->currency = $currency_code;
	        $journal->balance = 0;
	        return $this->journal()->save($journal);
	    }
		throw new \Exception('Journal already exists.');
    }
	
}