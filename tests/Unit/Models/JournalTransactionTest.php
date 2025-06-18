<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use Tests\Unit\TestCase;
use Scottlaurent\Accounting\Models\Journal;
use Scottlaurent\Accounting\Models\Ledger;
use Scottlaurent\Accounting\Enums\LedgerType;
use Scottlaurent\Accounting\Models\JournalTransaction;

class JournalTransactionTest extends TestCase
{
    public function test_it_can_be_created_with_required_fields(): void
    {
        $journal = Journal::create([
            'currency' => 'USD',
            'morphed_type' => 'test',
            'morphed_id' => 1,
        ]);

        $transaction = JournalTransaction::create([
            'journal_id' => $journal->id,
            'debit' => 1000,
            'credit' => 0,
            'currency' => 'USD',
            'memo' => 'Test transaction',
            'post_date' => now(),
        ]);

        $this->assertInstanceOf(JournalTransaction::class, $transaction);
        $this->assertEquals(1000, $transaction->debit);
        $this->assertEquals(0, $transaction->credit);
        $this->assertEquals('USD', $transaction->currency);
        $this->assertEquals('Test transaction', $transaction->memo);
        $this->assertNotNull($transaction->post_date);
    }

    public function test_it_has_journal_relationship(): void
    {
        $journal = Journal::create([
            'currency' => 'USD',
            'morphed_type' => 'test',
            'morphed_id' => 1,
        ]);

        $transaction = $journal->transactions()->create([
            'debit' => 1000,
            'credit' => 0,
            'currency' => 'USD',
            'memo' => 'Test transaction',
            'post_date' => now(),
        ]);

        $this->assertTrue($transaction->journal->is($journal));
    }

    public function test_it_handles_reference_objects(): void
    {
        // Create a ledger to use as a reference object
        $ledger = Ledger::create([
            'name' => 'Test Ledger',
            'type' => LedgerType::ASSET->value,
        ]);

        $journal = Journal::create([
            'currency' => 'USD',
            'morphed_type' => 'test',
            'morphed_id' => 1,
        ]);

        $transaction = $journal->transactions()->create([
            'debit' => 1000,
            'credit' => 0,
            'currency' => 'USD',
            'memo' => 'Test transaction',
            'post_date' => now(),
        ]);

        // Test setting a reference object
        $transaction->referencesObject($ledger);
        $transaction->refresh();

        $this->assertEquals(get_class($ledger), $transaction->ref_class);
        $this->assertEquals($ledger->id, $transaction->ref_class_id);

        // Test getting the referenced object
        $referencedObject = $transaction->getReferencedObject();
        $this->assertInstanceOf(Ledger::class, $referencedObject);
        $this->assertTrue($referencedObject->is($ledger));
    }

    public function test_it_handles_transaction_groups(): void
    {
        $journal = Journal::create([
            'currency' => 'USD',
            'morphed_type' => 'test',
            'morphed_id' => 1,
        ]);

        $group = 'test-group-' . uniqid();

        $transactions = [
            $journal->transactions()->create([
                'debit' => 1000,
                'credit' => 0,
                'currency' => 'USD',
                'memo' => 'Transaction 1',
                'post_date' => now(),
                'transaction_group' => $group,
            ]),
            $journal->transactions()->create([
                'debit' => 0,
                'credit' => 1000,
                'currency' => 'USD',
                'memo' => 'Transaction 2',
                'post_date' => now(),
                'transaction_group' => $group,
            ])
        ];

        $this->assertCount(2, $journal->transactions()->where('transaction_group', $group)->get());
        $this->assertEquals($group, $transactions[0]->transaction_group);
        $this->assertEquals($group, $transactions[1]->transaction_group);
    }

    public function test_it_handles_tags(): void
    {
        $journal = Journal::create([
            'currency' => 'USD',
            'morphed_type' => 'test',
            'morphed_id' => 1,
        ]);

        $transaction = $journal->transactions()->create([
            'debit' => 1000,
            'credit' => 0,
            'currency' => 'USD',
            'memo' => 'Test transaction',
            'post_date' => now(),
            'tags' => ['test', 'deposit'],
        ]);

        $this->assertIsArray($transaction->tags);
        $this->assertContains('test', $transaction->tags);
        $this->assertContains('deposit', $transaction->tags);
    }
}
