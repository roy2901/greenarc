<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class ContactValidationTest extends TestCase
{
    // ---- ga_clean ----------------------------------------------------------
    public function testCleanStripsNewlines(): void
    {
        $this->assertSame('a b', ga_clean("a\r\nb"));
        $this->assertSame('a b', ga_clean("a%0Ab"));
        $this->assertSame('trimmed', ga_clean("  trimmed  "));
    }

    // ---- ga_validate_contact ----------------------------------------------
    public function testValidSubmissionHasNoErrors(): void
    {
        $errors = ga_validate_contact([
            'name'    => 'Asha Menon',
            'email'   => 'asha@example.com',
            'company' => 'Bright Foods',
            'message' => 'We need a monthly close set up in QuickBooks.',
        ]);
        $this->assertSame([], $errors);
    }

    public function testShortNameEmailAndMessageAreCaught(): void
    {
        $errors = ga_validate_contact(['name' => 'A', 'email' => 'nope', 'message' => 'short']);
        $this->assertContains('a valid name', $errors);
        $this->assertContains('a valid email address', $errors);
        $this->assertContains('a short message', $errors);
    }

    public function testOverlongFieldsAreCaught(): void
    {
        $errors = ga_validate_contact([
            'name'    => str_repeat('x', 101),
            'email'   => str_repeat('x', 190) . '@example.com',
            'company' => str_repeat('y', 151),
            'message' => str_repeat('z', 5001),
        ]);
        $this->assertContains('a name under 100 characters', $errors);
        $this->assertContains('an email under 200 characters', $errors);
        $this->assertContains('a company name under 150 characters', $errors);
        $this->assertContains('a message under 5000 characters', $errors);
    }

    public function testCompanyIsOptional(): void
    {
        $errors = ga_validate_contact([
            'name'    => 'David Kim',
            'email'   => 'david@example.co',
            'company' => '',
            'message' => 'Looking for reconciliation help across channels.',
        ]);
        $this->assertSame([], $errors);
    }

    // ---- ga_origin_allowed -------------------------------------------------
    public function testOriginAbsentIsAllowed(): void
    {
        $this->assertTrue(ga_origin_allowed(null, null, ['greenarc.solutions']));
    }

    public function testSameOriginAllowed(): void
    {
        $this->assertTrue(ga_origin_allowed('https://greenarc.solutions', null, ['greenarc.solutions']));
        $this->assertTrue(ga_origin_allowed(null, 'https://greenarc.solutions/contact.html', ['greenarc.solutions']));
    }

    public function testForeignOriginRejected(): void
    {
        $this->assertFalse(ga_origin_allowed('https://evil.example', null, ['greenarc.solutions']));
    }

    // ---- ga_rate_ok --------------------------------------------------------
    public function testRateAllowsUnderLimit(): void
    {
        $now = 1_000_000;
        $times = [$now - 10, $now - 20, $now - 30]; // 3 within window
        $this->assertTrue(ga_rate_ok($times, $now, 900, 5));
    }

    public function testRateBlocksAtLimit(): void
    {
        $now = 1_000_000;
        $times = [$now - 1, $now - 2, $now - 3, $now - 4, $now - 5]; // 5 within window, max 5
        $this->assertFalse(ga_rate_ok($times, $now, 900, 5));
    }

    public function testRateIgnoresExpiredTimestamps(): void
    {
        $now = 1_000_000;
        $times = [$now - 2000, $now - 3000, $now - 5]; // only 1 within a 900s window
        $this->assertTrue(ga_rate_ok($times, $now, 900, 5));
    }
}
