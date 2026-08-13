<?php

    declare(strict_types=1);

    namespace Tests\Config;

    use Delta\Config\Config;
    use PHPUnit\Framework\TestCase;

    /**
     * Тесты репозитория конфигурации
     */
    class ConfigTest extends TestCase
    {
        /**
         * Путь к каталогу конфигурации проекта
         *
         * @return string Путь
         */
        private function configDir(): string
        {
            return dirname(__DIR__, 2) . '/config';
        }

        protected function setUp(): void
        {
            $_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__, 2);
            Config::setInstance(null);
        }

        protected function tearDown(): void
        {
            Config::setInstance(null);
        }

        public function testGetReturnsValueByDottedKey(): void
        {
            $config = new Config(['database' => ['host' => 'localhost', 'port' => 3306]]);

            $this->assertSame('localhost', $config->get('database.host'));
            $this->assertSame(3306, $config->get('database.port'));
        }

        public function testGetReturnsDefaultForMissingKey(): void
        {
            $config = new Config(['app' => ['env' => 'prod']]);

            $this->assertSame('дефолт', $config->get('app.нет', 'дефолт'));
            $this->assertNull($config->get('совсем.нет.ключа'));
        }

        public function testHasDistinguishesMissingKeyFromNullValue(): void
        {
            $config = new Config(['app' => ['env' => null]]);

            $this->assertTrue($config->has('app.env'));
            $this->assertFalse($config->has('app.missing'));
        }

        public function testSetCreatesNestedKeys(): void
        {
            $config = new Config();
            $config->set('runtime.deep.value', 42);

            $this->assertSame(42, $config->get('runtime.deep.value'));
        }

        public function testBootLoadsConfigDirectory(): void
        {
            $config = Config::boot($this->configDir());

            $this->assertSame($config, Config::getInstance());
            $this->assertTrue($config->has('app.env'));
            $this->assertTrue($config->has('database.host'));
            $this->assertTrue($config->has('cache.ttl'));
        }

        public function testBootFailsOnMissingDirectory(): void
        {
            $this->expectException(\RuntimeException::class);

            Config::boot($this->configDir() . '/несуществующий');
        }

        public function testGetInstanceFailsBeforeBoot(): void
        {
            $this->expectException(\RuntimeException::class);

            Config::getInstance();
        }

        public function testSetInstanceReplacesConfigForTests(): void
        {
            Config::setInstance(new Config(['app' => ['env' => 'testing']]));

            $this->assertSame('testing', Config::getInstance()->get('app.env'));
        }
    }
