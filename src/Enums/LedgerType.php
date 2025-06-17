<?php

declare(strict_types=1);

namespace Scottlaurent\Accounting\Enums;

enum LedgerType: string
{
    case ASSET = 'asset';
    case LIABILITY = 'liability';
    case EQUITY = 'equity';
    case INCOME = 'income';
    case EXPENSE = 'expense';
    
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
