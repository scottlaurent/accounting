<?php

declare(strict_types=1);

namespace Scottlaurent\Accounting\Exceptions;

class JournalAlreadyExists extends BaseException
{
    public $message = 'Journal already exists.';
}
