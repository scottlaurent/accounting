<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Scottlaurent\Accounting\Enums\LedgerType;

class CreateAccountingLedgersTable extends Migration
{
    public function up(): void
    {
        Schema::create('accounting_ledgers', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->enum('type', LedgerType::values());
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_ledgers');
    }
}
