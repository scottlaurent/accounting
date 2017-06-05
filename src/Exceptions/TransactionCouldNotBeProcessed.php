<?php

namespace Scottlaurent\Accounting\Exceptions;

class TransactionCouldNotBeProcessed extends BaseException {
	
    public function __construct($message = null) {
        parent::__construct('Double Entry Transaction could not be processed. ' . $message);
    }
	
}