<?php

namespace Scottlaurent\Accounting\Services;

use Carbon\Carbon;
use Scottlaurent\Accounting\Models\Journal;
use Money\Money;
use Money\Currency;

use Scottlaurent\Accounting\Exceptions\InvalidJournalEntryValue;
use Scottlaurent\Accounting\Exceptions\InvalidJournalMethod;
use Scottlaurent\Accounting\Exceptions\DebitsAndCreditsDoNotEqual;
use Scottlaurent\Accounting\Exceptions\TransactionCouldNotBeProcessed;

use DB;

/**
 * Class Accounting
 * @package Scottlaurent\Accounting\Services
 */
class Accounting
{

    /**
     * @var array
     */
    protected $transactions_pending = [];

    /**
     * @return Accounting
     */
    public static function newDoubleEntryTransactionGroup()
    {
        return new self;
    }

    /**
     * @param Journal $journal
     * @param string $method
     * @param Money $money
     * @param string|null $memo
     * @param null $referenced_object
     * @param Carbon|null $postdate
     * @throws InvalidJournalEntryValue
     * @throws InvalidJournalMethod
     * @internal param int $value
     */
    function addTransaction(Journal $journal, string $method, Money $money, string $memo = null, $referenced_object = null, Carbon $postdate = null)
    {

        if (!in_array($method, ['credit', 'debit'])) {
            throw new InvalidJournalMethod;
        }

        if ($money->getAmount() <= 0) {
            throw new InvalidJournalEntryValue();
        }

        $this->transactions_pending[] = [
            'journal' => $journal,
            'method' => $method,
            'money' => $money,
            'memo' => $memo,
            'referenced_object' => $referenced_object,
            'postdate' => $postdate
        ];

    }

    /**
     * @param Journal $journal
     * @param string $method
     * @param $value
     * @param string|null $memo
     * @param null $referenced_object
     * @param Carbon|null $postdate
     * @throws InvalidJournalEntryValue
     * @throws InvalidJournalMethod
     */
    function addDollarTransaction(Journal $journal, string $method, $value, string $memo = null, $referenced_object = null, Carbon $postdate = null)
    {
        $value = (int)($value * 100);
        $money = new Money($value, new Currency('USD'));
        $this->addTransaction($journal, $method, $money, $memo, $referenced_object, $postdate);
    }

    /**
     * @return array
     */
    function getTransactionsPending()
    {
        return $this->transactions_pending;
    }

    /**
     *
     */
    public function commit()
    {
        $this->verifyTransactionCreditsEqualDebits();

        try {

            $transaction_group = \Ramsey\Uuid\Uuid::uuid4()->toString();

            DB::beginTransaction();

            foreach ($this->transactions_pending as $transaction_pending) {
                $transaction = $transaction_pending['journal']->{$transaction_pending['method']}($transaction_pending['money'], $transaction_pending['memo'], $transaction_pending['postdate'], $transaction_group);
                if ($object = $transaction_pending['referenced_object']) {
                    $transaction->referencesObject($object);
                }
            }

            DB::commit();

            return $transaction_group;

        } catch (\Exception $e) {

            DB::rollBack();

            throw new TransactionCouldNotBeProcessed('Rolling Back Database. Message: ' . $e->getMessage());
        }
    }


    /**
     *
     */
    private function verifyTransactionCreditsEqualDebits()
    {
        $credits = 0;
        $debits = 0;

        foreach ($this->transactions_pending as $transaction_pending) {
            if ($transaction_pending['method'] == 'credit') {
                $credits += $transaction_pending['money']->getAmount();
            } else {
                $debits += $transaction_pending['money']->getAmount();
            }
        }

        if ($credits !== $debits) {
            throw new DebitsAndCreditsDoNotEqual('In this transaction, credits == ' . $credits . ' and debits == ' . $debits);
        }
    }


}
