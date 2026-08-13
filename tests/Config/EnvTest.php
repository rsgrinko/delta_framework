<?php

    declare(strict_types=1);

    namespace Tests\Config;

    use Delta\Config\Env;
    use PHPUnit\Framework\TestCase;

    /**
     * Тесты доступа к значениям окружения
     */
    class EnvTest extends TestCase
    {
        protected function tearDown(): void
        {
            unset($_ENV['DELTA_TEST_KEY'], $_SERVER['DELTA_TEST_KEY']);
        }

        public function testReturnsDefaultWhenNothingIsSet(): void
        {
            $this->assertSame('дефолт', Env::get('DELTA_TEST_ABSENT_KEY', 'дефолт'));
        }

        public function testEnvironmentVariableWins(): void
        {
            $_ENV['DELTA_TEST_KEY'] = 'из-окружения';

            $this->assertSame('из-окружения', Env::get('DELTA_TEST_KEY', 'дефолт'));
        }

        public function testFallsBackToDefinedConstant(): void
        {
            define('DELTA_TEST_CONSTANT', 'из-константы');

            $this->assertSame('из-константы', Env::get('DELTA_TEST_CONSTANT', 'дефолт'));
        }

        /**
         * @dataProvider castProvider
         */
        public function testCastsStringLiterals(string $raw, mixed $expected): void
        {
            $_ENV['DELTA_TEST_KEY'] = $raw;

            $this->assertSame($expected, Env::get('DELTA_TEST_KEY'));
        }

        public static function castProvider(): array
        {
            return [
                'true'         => ['true', true],
                'false'        => ['false', false],
                'null'         => ['null', null],
                'обычная строка' => ['localhost', 'localhost'],
            ];
        }

        public function testEmptyValueFallsThroughToDefault(): void
        {
            $_ENV['DELTA_TEST_KEY'] = '';

            $this->assertSame('дефолт', Env::get('DELTA_TEST_KEY', 'дефолт'));
        }
    }
