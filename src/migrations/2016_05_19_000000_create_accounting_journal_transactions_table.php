<?php
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Class CreateUsersTable
 */
class CreateAccountingJournalTransactionsTable extends Migration
{
	/**
	 * @var array
	 */
	protected $guarded = ['id'];
	
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('accounting_journal_transactions', function (Blueprint $table) {
            $table->char('id',36)->unique();
            $table->char('transaction_group',36)->nullable();
            $table->integer('journal_id');
            $table->bigInteger('debit')->nullable();
            $table->bigInteger('credit')->nullable();
            $table->char('currency',5);
	        $table->text('memo')->nullable();
	        $table->text('tags')->nullable();
	        $table->char('ref_class',32)->nullable();
	        $table->integer('ref_class_id')->nullable();
            $table->timestamps();
            $table->dateTime('post_date');
            $table->softDeletes();
        });
    }
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('accounting_journal_transactions');
    }
}
