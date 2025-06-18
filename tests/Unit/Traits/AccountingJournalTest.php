<?php

declare(strict_types=1);

namespace Tests\Unit\Traits;

use Tests\Unit\TestCase;
use Scottlaurent\Accounting\ModelTraits\AccountingJournal;
use Scottlaurent\Accounting\Models\Journal;
use Scottlaurent\Accounting\Models\Ledger;
use Scottlaurent\Accounting\Enums\LedgerType;
use Scottlaurent\Accounting\Exceptions\JournalAlreadyExists;
use Illuminate\Database\Eloquent\Model;

class AccountingJournalTest extends TestCase
{
    public function test_journal_relationship(): void
    {
        $model = new TestModel();
        
        $relationship = $model->journal();
        
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\MorphOne::class, $relationship);
    }

    public function test_init_journal_creates_new_journal(): void
    {
        $model = new TestModel();
        $model->id = 1;
        $model->save();
        
        $ledger = Ledger::create([
            'name' => 'Test Ledger',
            'type' => LedgerType::ASSET->value,
        ]);
        
        $journal = $model->initJournal('USD', (string)$ledger->id);
        
        $this->assertInstanceOf(Journal::class, $journal);
        $this->assertEquals('USD', $journal->currency);
        $this->assertTrue($journal->ledger->is($ledger));
        $this->assertEquals(TestModel::class, $journal->morphed_type);
        $this->assertEquals($model->id, $journal->morphed_id);
    }

    public function test_init_journal_throws_exception_when_journal_already_exists(): void
    {
        $this->expectException(JournalAlreadyExists::class);
        
        $model = new TestModel();
        $model->id = 2;
        $model->save();
        
        $ledger = Ledger::create([
            'name' => 'Test Ledger',
            'type' => LedgerType::ASSET->value,
        ]);
        
        // Create journal first time
        $model->initJournal('USD', (string)$ledger->id);
        
        // Load the relationship to trigger the !$this->journal check
        $model->load('journal');
        
        // Try to create again - should throw exception
        $model->initJournal('USD', (string)$ledger->id);
    }

    public function test_init_journal_with_minimal_parameters(): void
    {
        $model = new TestModel();
        $model->id = 3;
        $model->save();
        
        $journal = $model->initJournal('EUR');
        
        $this->assertInstanceOf(Journal::class, $journal);
        $this->assertEquals('EUR', $journal->currency);
        $this->assertNull($journal->ledger_id);
        $this->assertEquals(TestModel::class, $journal->morphed_type);
        $this->assertEquals($model->id, $journal->morphed_id);
    }
}

/**
 * Test model that uses the AccountingJournal trait
 */
class TestModel extends Model
{
    use AccountingJournal;
    
    protected $table = 'test_models';
    protected $fillable = ['name'];
    
    // Override the table creation for testing
    public static function boot()
    {
        parent::boot();
        
        // Create the test table if it doesn't exist
        if (!app('db')->getSchemaBuilder()->hasTable('test_models')) {
            app('db')->getSchemaBuilder()->create('test_models', function ($table) {
                $table->id();
                $table->string('name')->nullable();
                $table->timestamps();
            });
        }
    }
}