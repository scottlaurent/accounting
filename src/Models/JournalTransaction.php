<?php

namespace Scottlaurent\Accounting\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Ledger
 *
 * @package Scottlaurent\Accounting
 * @property    int                 $journal_id
 * @property    int                 $debit
 * @property    int                 $credit
 * @property    string              $currency
 * @property    string              memo
 * @property    \Carbon\Carbon      $post_date
 * @property    \Carbon\Carbon      $updated_at
 * @property    \Carbon\Carbon      $created_at
 */
class JournalTransaction extends Model
{
	
	/**
	 * @var string
	 */
	protected $table = 'accounting_journal_transactions';
	
	/**
	 * @var string
	 */
	protected $currency = 'USD';
	
	/**
	 * @var array
	 */
	protected $dates = [
		'post_date',
		'deleted_at',
		'udpated_at'
	];
	
	/**
	 * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
	 */
	public function journal()
    {
        return $this->belongsTo(Journal::class);
    }

	/**
	 *
	 */
	protected static function boot()
	{
		static::saved(function ($transaction) {
			$transaction->journal->resetCurrentBalances();
		});
	}
	
	/**
	 * @param Model $object
	 * @return JournalTransaction
	 */
	public function referencesObject($object)
	{
		$this->ref_class = get_class($object);
		$this->ref_class_id = $object->id;
		$this->save();
		return $this;
	}
	
	
	/**
	 *
	 */
	public function getReferencedObject()
	{
		if ($classname = $this->ref_class) {
			$_class = new $this->ref_class;
			return $_class->find($this->ref_class_id);
		}
		return false;
	}
	
	/**
	 * @param string $currency
	 */
	public function setCurrency($currency)
	{
		$this->currency = $currency;
	}
	
}