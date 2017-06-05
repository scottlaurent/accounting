# Laravel (Eloquent) Accounting Package

I am an accountant and a Laravel developer.  I wrote this package to provide a simple drop-in trait to manage accruing balances for a given model.  It can also be used to create double entry based projects where you would want to credit one journal and debit another.

** This DOES allow you to keep line-item balances historical debits and credits on a per model object (user, account, whatever) basis

** This DOES allow you track per-line-item memos and actually reference a foreign class object directly.

** This DOES (starting with v0.2.0) allow you utilize a "ledger" system that makes it possible to run queries against expense, income.

** This DOES NOT force double-entry bookeeping but does create an environment in which you can build out a double-entry system.

** This DOES NOT replace any type of financial recording system which you may be using (ie if you are tracking things in Stripe for example).


## Contents

- [Installation](#installation)
- [How It Works](#how-it-works)
- [Code Sample](#code-sample)
- [Usage Examples](#usage)
- [License](#license)


## <a name="installation"></a>Installation

1) composer require "scottlaurent/accounting"

2) copy the files in the migrations folder over to your migrations and run them.  This will install 3 new tables in your database.  The ledger migration is optional and you should look at SCENARIO C below to determine if you will even use this.

3) add the trait to any model you want to keep a journal for.

4) ** most of the time you will want to add the $model->initJournal() into the static::created() method of your model so that a journal is created when you create the model object itself.

5) If using double entry, add Scottlaurent\Accounting\Services\Accounting::class to your service providers


## <a name="code-sample"></a>Code Sample

```php

    // locate a user (or ANY MODEL that implementes the AccountingJournal trait)
    $user = User::find(1);
    
    // locate a product (optional)
    $product = Product::find(1)
    
    // init a journal for this user (do this only once)
    $user->initJournal();
    
    // credit the user and reference the product
    $transaction_1 = $user->journal->creditDollars(100);
    $transaction_1->referencesObject($product);
    
    // check our balance (should be 100)
    $current_balance = $user->journal->getCurrentBalanceInDollars();
    
    // debit the user 
    $transaction_2 = $user->journal->debitDollars(75);
    
    // check our balance (should be 25)
    $current_balance = $user->journal->getCurrentBalanceInDollars();
    
    //get the product referenced in the journal (optional)
    $product_copy = $transaction_1->getReferencedObject()
    
```

##### see /tests for more examples.

## <a name="how-it-works"></a>How it works

1) The trait includes functions to a) initialize a new journal for your model object and b) to return that journal.

2) Typically systems will have one journal per user or account and one general journal for the company if doing a double-entry system.

3) IMPORTANT: The accounting system uses the Money PHP class which deals with indivisible currency.  For example, the indivisible currency of USD is the penny.  So $1 is really Money 100 USD.  This prevents loss of currency by division/rounding errors.


### <a name="usage-examples"></a>Usage Examples

1. SCENARIO A - VERY SIMPLE CASE - You are providing an API Service. Each API hit from a user costs 5 cents. You don't care about double-entry accounting.

    a. Add the model trait to your user class.
    
    b. Run a cron at the end of each month to count the API calls and do a $user->journal->debitDollars(1500 * 0.05) as an example where 1500 is the number of API calls from that user at the end of the month.
    
    c. Any time the user makes a payment, post a $user->journal->creditDollars(25.00)
    
    From this point, you can can a balance for this user by doing $balance_due = $user->journal->getBalanceInDollars() (or getBalance() for a Money object);

2. SCENARIO B - You want to track product purchases users and also do a VERY BASIC INCOME ONLY double entry recording for the entire app where users each have an income based jounral (as in SCENARIO A).

    a. Add the model trait to your user class.  If you don't have one, create a model for the company itself (which you may only have a single entry which is the company/app).  Add the trait to that class as well.  So, to recap, you will have ONE journal PER USER and then one additional model object overall for the company which has a single journal entry.
    
    b. If you do simple product purchasing, just debit the user journal when the purchase is made, and credit the account journal.  You can optionally reference the products when you do the debit and the credits (see the test class).
     
   c. If you do more complex orders which have invoices or orders, you can still do the same thing here: debit a user model.  credit the invoice model.  then debit the invoice model and credit the account model.  This entirely depends on how you want to structure this, but the point here is that you are responsbible for doing the debits and the credits at the same time, and this can be a very simplistic and/or manual way to build out a mini-accounting system.
   
3. SCENARIO C - You want to assign journals to a ledger type system and enforce a double entry system
   
   a. Run the migrations.  Then look in the tests/BaseTest setUpCompanyLedgersAndJournals() code.  Notice where 5 basic ledgers are created.  Using this as an example, create the ledgers you will be using.  You can stick with those 5 or you can make a full blown chart of accounts, just make sure that each legder entry is assigned to one of the 5 enums (income, expense, asset, liability, equity)
   
   b. You will need multiple company jounrals at this point.  If you look at the test migration create_company_journals_table, it is a simple table that allows you to add journals for no other purpose than to record transactions.
     
   c. Each journal that is created, whether it's a user journal, or a cash journal you crete in your journals table, you will want to assign the journal to a ledger.  $user->journal->assignToLedger($this->company_income_ledger);
   
   d. To process a double entry transaction, do something like this:
   
    ```
            // this represents some kind of sale to a customer for $500 based on an invoiced ammount of 500.
            $transaction_group = AccountingService::newDoubleEntryTransactionGroup();
            $transaction_group->addDollarTransaction($user->journal,'credit',500);  // your user journal probably is an income ledger
            $transaction_group->addDollarTransaction($this->company_accounts_receivable_journal,'debit',500); // this is an asset ledder
            $transaction_group->commit();
    
    ```
    
    ```
            // this represents payment in cash to satisy that AR entry
            $transaction_group = AccountingService::newDoubleEntryTransactionGroup();
            $transaction_group->addDollarTransaction($this->company_accounts_receivable_journal,'debit',500);
            $transaction_group->addDollarTransaction($this->company_cash_journal,'credit',500);
            $transaction_group->commit();
            
            // at this point, our assets are 500 still and our income is 500.  If you review the code you will notice that assets and expenses are on the 'left' side of a balance sheet rollup and the liabilities and owners equity (and income) are rolled up on the right.  In that way, the left and right always stay in sync.  You could do an adjustment transaction of course to zero out expenses/income and transfer that to equity or however you do year-end or period-end clearances on your income/expense ledgers.
    
    ```
    
    e. Finally note that add up all of your $ledger model objects of type asset/expense then that will always be 100% equal to the sum of the $ledger liability/equity/income objects.
    
    f. Note that the $transaction_group->addDollarTransaction() allows you to add as many transactions as you want, into the batch, but the sum of ledger-type journals for the assets/expenses must equal that of the income/liability/equity types.  This is a fundamental requirement of accounting and is enforced here.  But again, remember that you don't have to use ledgers in the first place if you don't want to.  
    
    g. the unit tests really play out a couple complex scenarios.  They simulate about 1000 transactions, each simulating a $1-$10million purchase, split between cash and AR, and then checks the fundamental accounting equation at the end of all of this.

It's been my experience, in practice, that keeping the 5 basic ledger types, some initial company journals, and then adding a journal for users and sometimes vendor journals assigned to the expense ledger keeps things pretty simple.  Anyting more complex, usually winds up beng migrated eventually into a financial system, or in some cases, just synced.  

   
 
## <a name="license"></a>License

Free software distributed under the terms of the MIT license.