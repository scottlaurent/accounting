<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use Tests\Unit\TestCase;
use Scottlaurent\Accounting\Enums\LedgerType;

class LedgerTypeTest extends TestCase
{
    public function test_ledger_type_enum_cases(): void
    {
        $this->assertEquals('asset', LedgerType::ASSET->value);
        $this->assertEquals('liability', LedgerType::LIABILITY->value);
        $this->assertEquals('equity', LedgerType::EQUITY->value);
        $this->assertEquals('revenue', LedgerType::REVENUE->value);
        $this->assertEquals('expense', LedgerType::EXPENSE->value);
        $this->assertEquals('gain', LedgerType::GAIN->value);
        $this->assertEquals('loss', LedgerType::LOSS->value);
    }

    public function test_ledger_type_values_method(): void
    {
        $values = LedgerType::values();
        
        $this->assertIsArray($values);
        $this->assertContains('asset', $values);
        $this->assertContains('liability', $values);
        $this->assertContains('equity', $values);
        $this->assertContains('revenue', $values);
        $this->assertContains('expense', $values);
        $this->assertContains('gain', $values);
        $this->assertContains('loss', $values);
        $this->assertCount(7, $values);
    }
    
    public function test_debit_normal_balance_types(): void
    {
        $this->assertTrue(LedgerType::ASSET->isDebitNormal());
        $this->assertTrue(LedgerType::EXPENSE->isDebitNormal());
        $this->assertTrue(LedgerType::LOSS->isDebitNormal());
        
        $this->assertFalse(LedgerType::LIABILITY->isDebitNormal());
        $this->assertFalse(LedgerType::EQUITY->isDebitNormal());
        $this->assertFalse(LedgerType::REVENUE->isDebitNormal());
        $this->assertFalse(LedgerType::GAIN->isDebitNormal());
    }
    
    public function test_credit_normal_balance_types(): void
    {
        $this->assertTrue(LedgerType::LIABILITY->isCreditNormal());
        $this->assertTrue(LedgerType::EQUITY->isCreditNormal());
        $this->assertTrue(LedgerType::REVENUE->isCreditNormal());
        $this->assertTrue(LedgerType::GAIN->isCreditNormal());
        
        $this->assertFalse(LedgerType::ASSET->isCreditNormal());
        $this->assertFalse(LedgerType::EXPENSE->isCreditNormal());
        $this->assertFalse(LedgerType::LOSS->isCreditNormal());
    }
}