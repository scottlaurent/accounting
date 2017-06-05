<?php

namespace Scottlaurent\Accounting\Exceptions;

use Exception;

class BaseException extends Exception
{
	public $message;
	
    public function __construct($message=null) {
        parent::__construct($message ?: $this->message, 0, null);
    }
}