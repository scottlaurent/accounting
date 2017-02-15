# Laravel (Eloquent) Accounting Package

I am an accountant and a Laravel developer.  I wrote this package to provide a simple drop-in trait to manage accruing balances for a given model.  It can also be used to create double entry based projects where you would want to credit one journal and debit another.

** This DOES allow you to keep line-item balances historical debits and credits on a per model object (user, account, whatever) basis

** This DOES allow you track per-line-item memos and actually reference a foreign class object directly.

** This DOES NOT enforce double-entry bookeeping but does create an environment in which you can build out a double-entry system.

** This DOES NOT replace any type of financial recording system which you may be using (ie if you are tracking things in Stripe for example) --


## Contents

- [Installation](#Installation)
- [How It Works](#How It Works)
- [Code Sample](#Code Sample)
- [Usage Examples](#Usage)
- [License](#license)

## Installation

1) composer require "scottlaurent/accounting"

2) copy the files in the migrations folder over to your migrations and run them.  This will install 2 new tables in your database.

3) add the trait to any model you want to keep a journal for.

4) ** most of the time you will want to add the $model->initJournal() into the static::created() method of your model so that a journal is created when you create the model object itself.


## Code Sample

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

## How it works

1) The trait includes functions to a) initialize a new journal for your model object and b) to return that journal.

2) Typically systems will have one journal per user or account and one general journal for the company if doing a double-entry system.

3) IMPORTANT: The accounting system uses the Money PHP class which deals with indivisible currency.  For example, the indivisible currency of USD is the penny.  So $1 is really Money 100 USD.  This prevents loss of currency by division/rounding errors.

### Usage Examples

1. Case Scenario A - You want to track API calls for a user and charge them a base ammount per call and you don't cate about double-entry.

    a. Add the model trait to your user class.
    
    b. Run a cron at the end of each night to count the API calls and do a $user->journal->debitDollars(1500 * 0.05) as an example.
    
    c. Any time the user makes a payment, post a $user->journal->creditDollars(25.00)

2. Case Scenario B - You want to track product purchases users and also do a double entry recording for the entire app.

    a. Add the model trait to your user class.  If you don't have one, create a model for the company itself (which you may only have a single entry which is the company/app).  Add the trait to that class as well.
    
    b. If you do simple product purchasing, just debit the user journal when the purchase is made, and credit the account journal.  You can optionally reference the products when you do the debit and the credits (see the test class).
     
   c. If you do more complex orders which have invoices or orders, you can still do the same thing here: debit a user model.  credit the invoice model.  then debit the invoice model and credit the account model.  This entirely depends on how you want to structure this, but the point here is that you are responsbible for doing the debits and the credits at the same time.  
   

## License

Free software distributed under the terms of the MIT license.