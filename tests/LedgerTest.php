<?php

// ensure we load our base file (PHPStorm Bug when using remote interpreter )
require_once ('BaseTest.php');

use Scottlaurent\Accounting\Services\Accounting as AccountingService;


/**
 * Class LedgerTest
 */
class LedgerTest extends BaseTest
{
	
	/**
	 *
	 */
	public function testLedgers() {
		
		// create some user and sell them some stuff on credit
		$number_of_users = mt_rand(5,10);
		$users = $this->createFakeUsers($number_of_users);
		
		foreach($users as $user) {
			$user_journal = $user->initJournal();
			$user_journal->assignToLedger($this->company_income_ledger);
			$user_journal->creditDollars(100);
			$this->company_ar_journal->debitDollars(100);
		}
		
		// Test if our AR Balance is correct
		$this->assertEquals($number_of_users * 100, (-1) * $this->company_ar_journal->getCurrentBalanceInDollars());
		
		// This is testing that the value on the LEFT side of the books (ASSETS) is the same as the RIGHT side (L + OE + nominals)
		$this->assertEquals($number_of_users * 100, $this->company_assets_ledger->getCurrentBalanceInDollars($this->currency));
		$this->assertEquals($number_of_users * 100, $this->company_income_ledger->getCurrentBalanceInDollars($this->currency));
		$this->assertEquals($this->company_assets_ledger->getCurrentBalanceInDollars($this->currency),$this->company_income_ledger->getCurrentBalanceInDollars($this->currency));
		
		// At this point we have no cash on hand
		$this->assertEquals($this->company_cash_journal->getCurrentBalanceInDollars(),0);
		
		// customer makes a payment (use double entry service)
		$user_making_payment = $users[0];
		$payment_1 = mt_rand(3,30) * 1.0129; // convert us using Faker dollar amounts
		
		$transaction_group = AccountingService::newDoubleEntryTransactionGroup();
		$transaction_group->addDollarTransaction($this->company_cash_journal,'debit',$payment_1,'Payment from User ' . $user_making_payment->id,$user_making_payment);
		$transaction_group->addDollarTransaction($this->company_ar_journal,'credit',$payment_1,'Payment from User ' . $user_making_payment->id,$user_making_payment);
		$transaction_group->commit();
		
		// customer makes a payment (use double entry service)
		$transaction_group = AccountingService::newDoubleEntryTransactionGroup();
		$payment_2 = mt_rand(3,30) * 1.075;
		$transaction_group->addDollarTransaction($this->company_cash_journal,'debit',$payment_2,'Payment from User ' . $user_making_payment->id,$user_making_payment);
		$transaction_group->addDollarTransaction($this->company_ar_journal,'credit',$payment_2,'Payment from User ' . $user_making_payment->id,$user_making_payment);
		$transaction_group->commit();
		
		// these are asset accounts, so their balances are reversed
		$total_payment_made = (((int) ($payment_1 * 100)) / 100) + (((int) ($payment_2 * 100)) / 100);
		$this->assertEquals(
			$this->company_cash_journal->getCurrentBalanceInDollars(),
			(-1) * $total_payment_made,
			'Company Cash Is Not Correcrt'
		);
		
		$this->assertEquals(
			$this->company_ar_journal->getCurrentBalanceInDollars(),
			(-1) * (($number_of_users * 100) - $total_payment_made),
			'AR Doesn Not Reflects Cash Payments Made')
		;
		
		// check the value of all the payments made by this user?
		$dollars_paid_by_user = $this->company_cash_journal->transactionsReferencingObjectQuery($user_making_payment)->get()->sum('debit') / 100;
		$this->assertEquals($dollars_paid_by_user,$total_payment_made,'User payments did not match what was recorded.');
		
		// check the "balance due" (the amount still owed by this user)
		$this->assertEquals(
			$user_journal->getCurrentBalanceInDollars() - $dollars_paid_by_user,
			100 - $total_payment_made,
			'User Current Balance does not reflect their payment amounts'
		);
		
		// still make sure our ledger balances match
		$this->assertEquals($this->company_assets_ledger->getCurrentBalanceInDollars($this->currency),$this->company_income_ledger->getCurrentBalanceInDollars($this->currency));
		
	}
	
}