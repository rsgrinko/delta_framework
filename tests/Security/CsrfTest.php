<?php

    declare(strict_types=1);

    namespace Tests\Security;

    use Delta\Security\Csrf;
    use PHPUnit\Framework\TestCase;

    /**
     * Тесты защиты от подделки межсайтовых запросов
     */
    class CsrfTest extends TestCase
    {
        protected function setUp(): void
        {
            $_SESSION = [];
        }

        protected function tearDown(): void
        {
            $_SESSION = [];
        }

        public function testTokenIsGeneratedOnceAndReused(): void
        {
            $first  = Csrf::token();
            $second = Csrf::token();

            $this->assertSame($first, $second);
            $this->assertSame(64, strlen($first));
        }

        public function testValidTokenPasses(): void
        {
            $this->assertTrue(Csrf::isValid(Csrf::token()));
        }

        public function testForeignTokenIsRejected(): void
        {
            Csrf::token();

            $this->assertFalse(Csrf::isValid(str_repeat('a', 64)));
        }

        public function testEmptyTokenIsRejected(): void
        {
            Csrf::token();

            $this->assertFalse(Csrf::isValid(null));
            $this->assertFalse(Csrf::isValid(''));
        }

        public function testTokenIsRejectedWhenSessionHasNone(): void
        {
            $this->assertFalse(Csrf::isValid(str_repeat('b', 64)));
        }

        public function testResetIssuesNewToken(): void
        {
            $first = Csrf::token();
            Csrf::reset();

            $this->assertNotSame($first, Csrf::token());
        }

        public function testFieldContainsCurrentToken(): void
        {
            $field = Csrf::field();

            $this->assertStringContainsString('name="_token"', $field);
            $this->assertStringContainsString(Csrf::token(), $field);
        }
    }
