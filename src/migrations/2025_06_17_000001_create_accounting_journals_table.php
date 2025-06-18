<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAccountingJournalsTable extends Migration
{
    public function up(): void
    {
        Schema::create('accounting_journals', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('ledger_id')->nullable();
            $table->bigInteger('balance')->default(0);
            $table->char('currency', 3);
            $table->string('morphed_type', 32);
            $table->unsignedInteger('morphed_id');
            $table->timestamps();

            $table->index('ledger_id', 'idx_journals_ledger_id');
            $table->index('currency', 'idx_journals_currency');
            $table->index(['morphed_type', 'morphed_id'], 'idx_journals_morphed');
            $table->index('balance', 'idx_journals_balance');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_journals');
    }
}
