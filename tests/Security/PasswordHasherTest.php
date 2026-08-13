<?php

    declare(strict_types=1);

    namespace Tests\Security;

    use Delta\Security\PasswordHasher;
    use PHPUnit\Framework\TestCase;

    /**
     * Тесты хеширования паролей и прозрачной миграции со старой схемы
     */
    class PasswordHasherTest extends TestCase
    {
        /** @var string Соль прежней схемы */
        private const LEGACY_SALT = 'тестовая-соль';

        /**
         * Создать хешер
         *
         * @return PasswordHasher Хешер
         */
        private function hasher(): PasswordHasher
        {
            return new PasswordHasher(self::LEGACY_SALT);
        }

        public function testHashIsBcryptAndDiffersEachTime(): void
        {
            $hasher = $this->hasher();

            $first  = $hasher->hash('пароль');
            $second = $hasher->hash('пароль');

            $this->assertStringStartsWith('$2y$', $first);
            $this->assertNotSame($first, $second, 'у каждой записи должна быть своя соль');
        }

        public function testVerifyAcceptsCorrectPassword(): void
        {
            $hasher = $this->hasher();
            $hash   = $hasher->hash('пароль');

            $this->assertTrue($hasher->verify('пароль', $hash));
            $this->assertFalse($hasher->verify('другой', $hash));
        }

        public function testVerifyAcceptsLegacyMd5Hash(): void
        {
            $hasher     = $this->hasher();
            $legacyHash = md5(self::LEGACY_SALT . 'пароль');

            $this->assertTrue($hasher->verify('пароль', $legacyHash));
            $this->assertFalse($hasher->verify('другой', $legacyHash));
        }

        public function testLegacyHashIsRecognised(): void
        {
            $hasher = $this->hasher();

            $this->assertTrue($hasher->isLegacy(md5('что угодно')));
            $this->assertFalse($hasher->isLegacy($hasher->hash('пароль')));
        }

        public function testLegacyHashNeedsRehash(): void
        {
            $hasher = $this->hasher();

            $this->assertTrue($hasher->needsRehash(md5(self::LEGACY_SALT . 'пароль')));
            $this->assertFalse($hasher->needsRehash($hasher->hash('пароль')));
        }

        public function testEmptyHashIsRejected(): void
        {
            $this->assertFalse($this->hasher()->verify('пароль', ''));
        }

        public function testMigrationScenario(): void
        {
            $hasher = $this->hasher();

            // в базе лежит старый хеш
            $stored = md5(self::LEGACY_SALT . 'пароль');

            // пользователь входит: пароль принят, хеш признан устаревшим
            $this->assertTrue($hasher->verify('пароль', $stored));
            $this->assertTrue($hasher->needsRehash($stored));

            // запись перехеширована, старый пароль продолжает подходить
            $stored = $hasher->hash('пароль');
            $this->assertTrue($hasher->verify('пароль', $stored));
            $this->assertFalse($hasher->needsRehash($stored));
        }
    }
