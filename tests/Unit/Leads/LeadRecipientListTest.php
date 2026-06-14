<?php

namespace Tests\Unit\Leads;

use App\Services\Leads\LeadRecipientList;
use PHPUnit\Framework\TestCase;

class LeadRecipientListTest extends TestCase
{
    public function test_normalizes_repeater_rows_and_deduplicates(): void
    {
        $emails = LeadRecipientList::normalize([
            ['email' => ' Manager@Example.com '],
            ['email' => 'manager@example.com'],
            ['email' => 'sales@example.com'],
            'invalid',
        ]);

        $this->assertSame([
            'Manager@Example.com',
            'sales@example.com',
        ], $emails);
    }
}
