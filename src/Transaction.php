<?php

declare(strict_types=1);

namespace Scottlaurent\Accounting;

use Carbon\Carbon;
use Scottlaurent\Accounting\Models\Journal;
use Money\Money;
use Money\Currency;
use Scottlaurent\Accounting\Exceptions\{
    InvalidJournalEntryValue,
    InvalidJournalMethod,
    DebitsAndCreditsDoNotEqual,
    TransactionCouldNotBeProcessed
};
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;

class Transaction
{
    protected array $transactions_pending = [];

    public static function newDoubleEntryTransactionGroup(): self
    {
        return new self;
    }

    public function addTransaction(
        Journal $journal,
        string $method,
        Money $money,
        ?string $memo = null,
        mixed $referenced_object = null,
        ?Carbon $postdate = null
    ): void {
        if (!in_array($method, ['credit', 'debit'], true)) {
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

    public function addDollarTransaction(
        Journal $journal,
        string $method,
        float|int|string $value,
        ?string $memo = null,
        mixed $referenced_object = null,
        ?Carbon $postdate = null
    ): void {
        $value = (int)($value * 100);
        $money = new Money($value, new Currency('USD'));
        $this->addTransaction($journal, $method, $money, $memo, $referenced_object, $postdate);
    }

    public function getTransactionsPending(): array
    {
        return $this->transactions_pending;
    }

    public function commit(): string
    {
        $this->verifyTransactionCreditsEqualDebits();
        
        try {
            $transactionGroupUUID = Uuid::uuid4()->toString();
            DB::beginTransaction();

            foreach ($this->transactions_pending as $transaction_pending) {
                $transaction = $transaction_pending['journal']->{$transaction_pending['method']}(
                    $transaction_pending['money'],
                    $transaction_pending['memo'],
                    $transaction_pending['postdate'],
                    $transactionGroupUUID
                );
                
                if ($object = $transaction_pending['referenced_object']) {
                    $transaction->referencesObject($object);
                }
            }

            DB::commit();
            return $transactionGroupUUID;

        } catch (\Exception $e) {
            DB::rollBack();
            throw new TransactionCouldNotBeProcessed(
                'Rolling Back Database. Message: ' . $e->getMessage()
            );
        }
    }

    private function verifyTransactionCreditsEqualDebits(): void
    {
        $credits = 0;
        $debits = 0;

        foreach ($this->transactions_pending as $transaction_pending) {
            if ($transaction_pending['method'] === 'credit') {
                $credits += $transaction_pending['money']->getAmount();
            } else {
                $debits += $transaction_pending['money']->getAmount();
            }
        }

        if ($credits !== $debits) {
            throw new DebitsAndCreditsDoNotEqual(
                'In this transaction, credits == ' . $credits . ' and debits == ' . $debits
            );
        }
    }
}
