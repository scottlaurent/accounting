<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAccountingJournalTransactionsTable extends Migration
{
    public function up(): void
    {
        Schema::create('accounting_journal_transactions', function (Blueprint $table) {
            // Primary key - critical for performance
            $table->char('id', 36)->primary();

            $table->char('transaction_group', 36)->nullable();
            $table->unsignedInteger('journal_id');
            $table->bigInteger('debit')->nullable()->default(0);
            $table->bigInteger('credit')->nullable()->default(0);
            $table->char('currency', 3);
            $table->string('memo', 500)->nullable(); // Limit memo size for performance
            $table->json('tags')->nullable(); // JSON for better querying
            $table->string('ref_class', 64)->nullable(); // Increased for namespaced classes
            $table->unsignedInteger('ref_class_id')->nullable();
            $table->timestamps();
            $table->dateTime('post_date')->index('idx_transactions_post_date');
            $table->softDeletes();

            // Foreign key constraints - commented out for flexibility in testing
            // Uncomment in production for referential integrity
            // $table->foreign('journal_id', 'fk_transactions_journal_id')
            //       ->references('id')
            //       ->on('accounting_journals')
            //       ->onDelete('cascade');

            // Critical indexes for 1B+ transactions performance
            $table->index('journal_id', 'idx_transactions_journal_id');
            $table->index('transaction_group', 'idx_transactions_group');
            $table->index('currency', 'idx_transactions_currency');
            $table->index(['ref_class', 'ref_class_id'], 'idx_transactions_ref');
            $table->index(['journal_id', 'post_date'], 'idx_transactions_journal_date');
            $table->index(['post_date', 'journal_id'], 'idx_transactions_date_journal');
            $table->index('deleted_at', 'idx_transactions_deleted_at');

            // Composite indexes for common query patterns
            $table->index(['journal_id', 'currency', 'post_date'], 'idx_transactions_journal_currency_date');
            $table->index(['transaction_group', 'post_date'], 'idx_transactions_group_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_journal_transactions');
    }
}
