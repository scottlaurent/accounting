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
use Illuminate\Support\Str;

class Transaction
{
    protected array $transactionsPending = [];

    public static function newDoubleEntryTransactionGroup(): self
    {
        return new self;
    }

    public function addTransaction(
        Journal $journal,
        string $method,
        Money $money,
        ?string $memo = null,
        mixed $referencedObject = null,
        ?Carbon $postdate = null
    ): void {
        if (!in_array($method, ['credit', 'debit'], true)) {
            throw new InvalidJournalMethod;
        }

        if ($money->getAmount() <= 0) {
            throw new InvalidJournalEntryValue();
        }

        $this->transactionsPending[] = [
            'journal' => $journal,
            'method' => $method,
            'money' => $money,
            'memo' => $memo,
            'referencedObject' => $referencedObject,
            'postdate' => $postdate
        ];
    }

    public function addDollarTransaction(
        Journal $journal,
        string $method,
        float|int|string $value,
        ?string $memo = null,
        mixed $referencedObject = null,
        ?Carbon $postdate = null
    ): void {
        $value = (int) ($value * 100);
        $money = new Money($value, new Currency('USD'));
        $this->addTransaction($journal, $method, $money, $memo, $referencedObject, $postdate);
    }

    public function getTransactionsPending(): array
    {
        return $this->transactionsPending;
    }

    public function commit(): string
    {
        $this->verifyTransactionCreditsEqualDebits();

        try {
            $transactionGroupUUID = Str::uuid()->toString();
            DB::beginTransaction();

            foreach ($this->transactionsPending as $transactionPending) {
                $transaction = $transactionPending['journal']->{$transactionPending['method']}(
                    $transactionPending['money'],
                    $transactionPending['memo'],
                    $transactionPending['postdate'],
                    $transactionGroupUUID
                );

                if ($object = $transactionPending['referencedObject']) {
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

        foreach ($this->transactionsPending as $transactionPending) {
            if ($transactionPending['method'] === 'credit') {
                $credits += $transactionPending['money']->getAmount();
            } else {
                $debits += $transactionPending['money']->getAmount();
            }
        }

        if ($credits !== $debits) {
            throw new DebitsAndCreditsDoNotEqual(
                'In this transaction, credits == ' . $credits . ' and debits == ' . $debits
            );
        }
    }
}
