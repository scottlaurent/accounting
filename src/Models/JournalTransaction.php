<?php

namespace Scottlaurent\Accounting\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class JournalTransaction
 *
 * @package Scottlaurent\Accounting
 * @property    string $journal_id
 * @property    int $debit
 * @property    int $credit
 * @property    string $currency
 * @property    string memo
 * @property    \Carbon\Carbon $post_date
 * @property    \Carbon\Carbon $updated_at
 * @property    \Carbon\Carbon $created_at
 */
class JournalTransaction extends Model
{

    /**
     * @var string
     */
    protected $table = 'accounting_journal_transactions';

    /**
     * Currency.
     *
     * @var string $currency
     */
    protected $currency;

    /**
     * @var bool
     */
    public $incrementing = false;

    /**
     * @var array
     */
    protected $guarded=['id'];

    /**
     * @var array
     */
    protected $casts = [
        'post_date' => 'datetime',
        'tags' => 'array',
    ];

    /**
     * Boot.
     */
    protected static function boot()
    {
        parent::boot();
        static::creating(function ($transaction) {
            $transaction->id = \Ramsey\Uuid\Uuid::uuid4()->toString();
        });

//        static::saved(function ($transaction) {
//            $transaction->journal->resetCurrentBalances();
//        });

        static::deleted(function ($transaction) {
            $transaction->journal->resetCurrentBalances();
        });

        parent::boot();
    }

    /**
     * Journal relation.
     */
    public function journal()
    {
        return $this->belongsTo(Journal::class);
    }

    /**
     * Set reference object.
     *
     * @param Model $object
     * @return JournalTransaction
     */
    public function referencesObject($object)
    {
        $this->ref_class    = get_class($object);
        $this->ref_class_id = $object->id;
        $this->save();
        return $this;
    }

    /**
     * Get reference object.
     *
     * @return \Illuminate\Database\Eloquent\Collection|Model|Model[]|null
     */
    public function getReferencedObject()
    {
        /**
         * @var Model $_class
         */
        $_class = new $this->ref_class;
        return $_class->find($this->ref_class_id);
    }

    /**
     * Set currency.
     *
     * @param string $currency
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;
    }

}