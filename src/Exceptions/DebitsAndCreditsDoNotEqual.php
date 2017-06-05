<?php

namespace Scottlaurent\Accounting\Exceptions;

class DebitsAndCreditsDoNotEqual extends BaseException {
	
    public function __construct($message = null) {
        parent::__construct('Double Entry requires that debits equal credits.' . $message);
    }
	
}