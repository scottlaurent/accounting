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
            $table->increments('id');
            $table->integer('journal_id');
            $table->bigInteger('debit')->nullable();
            $table->bigInteger('credit')->nullable();
            $table->char('currency',5);
	        $table->text('memo')->nullable();
	        $table->char('ref_class',32)->nullable();
	        $table->integer('ref_class_id')->nullable();
	        $table->timestamp('post_date');
            $table->timestamps();
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
        Schema::dropIfExists('accounting_journals');
    }
}