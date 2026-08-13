<?php

    declare(strict_types=1);

    namespace Tests\Integration;

    use Core\Models\User;
    use Delta\Security\PasswordHasher;
    use PHPUnit\Framework\TestCase;
    use Tests\Support\TestDatabase;

    /**
     * Интеграционные тесты входа в систему.
     *
     * Работают с настоящей базой (таблицы с префиксом test_), потому что проверяемое поведение —
     * это как раз связка модели, базы, сессии и cookie. Именно этих сценариев не хватало, чтобы
     * безопасно разбирать User на сущность, репозиторий и сервисы.
     */
    class AuthTest extends TestCase
    {
        /** @var string Соль прежней схемы хеширования, совпадает с User::$cryptoSalt */
        private const LEGACY_SALT = 'BKH92FdiQvEW2aOy0giywXasYAMl0pFvIrlop8Sz';

        public static function setUpBeforeClass(): void
        {
            if (TestDatabase::isAvailable() === false) {
                self::markTestSkipped('Тестовая база недоступна: нужен config.local.php с реквизитами.');
            }

            TestDatabase::createSchema();
        }

        public static function tearDownAfterClass(): void
        {
            if (TestDatabase::isAvailable()) {
                TestDatabase::dropSchema();
            }
        }

        protected function setUp(): void
        {
            TestDatabase::truncate();
            TestDatabase::resetRuntimeState();
        }

        /**
         * Создать пользователя с паролем, захешированным текущей схемой
         *
         * @param string $login    Логин
         * @param string $password Пароль
         *
         * @return int Идентификатор
         */
        private function createUser(string $login, string $password): int
        {
            return TestDatabase::insertUser($login, (new PasswordHasher(self::LEGACY_SALT))->hash($password));
        }

        public function testSuccessfulLoginFillsSession(): void
        {
            $id = $this->createUser('ivan', 'секретный-пароль');

            $this->assertTrue(User::securityAuthorize('ivan', 'секретный-пароль'));

            $this->assertSame($id, (int)$_SESSION['id']);
            $this->assertSame('Y', $_SESSION['authorize']);
            $this->assertSame('ivan', $_SESSION['login']);
            $this->assertNotEmpty($_SESSION['password'], 'в сессии должен быть отпечаток пароля');
        }

        public function testLoginWithWrongPasswordIsRejected(): void
        {
            $this->createUser('ivan', 'секретный-пароль');

            $this->assertFalse(User::securityAuthorize('ivan', 'неверный'));
            $this->assertArrayNotHasKey('authorize', $_SESSION);
        }

        public function testLoginWithUnknownLoginIsRejected(): void
        {
            $this->createUser('ivan', 'секретный-пароль');

            $this->assertFalse(User::securityAuthorize('его-нет', 'секретный-пароль'));
        }

        public function testLegacyMd5PasswordStillWorks(): void
        {
            TestDatabase::insertUser('старый', md5(self::LEGACY_SALT . 'старый-пароль'));

            $this->assertTrue(
                User::securityAuthorize('старый', 'старый-пароль'),
                'пользователи со старыми хешами обязаны продолжать входить'
            );
        }

        public function testLegacyPasswordIsRehashedOnLogin(): void
        {
            $id = TestDatabase::insertUser('старый', md5(self::LEGACY_SALT . 'старый-пароль'));

            User::securityAuthorize('старый', 'старый-пароль');

            $stored = (string)TestDatabase::findUser($id)['password'];

            $this->assertStringStartsWith('$2y$', $stored, 'хеш должен мигрировать на bcrypt при входе');
            $this->assertTrue(
                (new PasswordHasher(self::LEGACY_SALT))->verify('старый-пароль', $stored),
                'после миграции прежний пароль обязан подходить'
            );
        }

        public function testMigratedUserCanLoginAgain(): void
        {
            TestDatabase::insertUser('старый', md5(self::LEGACY_SALT . 'старый-пароль'));

            User::securityAuthorize('старый', 'старый-пароль');
            TestDatabase::resetRuntimeState();

            $this->assertTrue(
                User::securityAuthorize('старый', 'старый-пароль'),
                'повторный вход после миграции должен работать'
            );
        }

        public function testIsAuthorizedConfirmsFreshSession(): void
        {
            $this->createUser('ivan', 'секретный-пароль');
            User::securityAuthorize('ivan', 'секретный-пароль');

            $this->assertTrue(User::isAuthorized());
        }

        public function testSessionIsInvalidatedWhenStoredPasswordChanges(): void
        {
            $id = $this->createUser('ivan', 'секретный-пароль');
            User::securityAuthorize('ivan', 'секретный-пароль');

            // пароль изменили в обход текущей сессии — она обязана перестать действовать
            TestDatabase::insertUser('другой', 'x');
            (new \PDO(
                'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME,
                DB_USER,
                DB_PASSWORD
            ))->exec('UPDATE `test_users` SET `password` = "другой-хеш" WHERE `id` = ' . $id);

            $this->assertFalse(User::isAuthorized());
        }

        public function testLogoutClearsSession(): void
        {
            $this->createUser('ivan', 'секретный-пароль');
            User::securityAuthorize('ivan', 'секретный-пароль');

            User::logout();

            $this->assertArrayNotHasKey('authorize', $_SESSION);
            $this->assertArrayNotHasKey('password', $_SESSION);
            $this->assertFalse(User::isAuthorized());
        }

        public function testGetCurrentUserIdReturnsLoggedInUser(): void
        {
            $id = $this->createUser('ivan', 'секретный-пароль');
            User::securityAuthorize('ivan', 'секретный-пароль');

            $this->assertSame($id, User::getCurrentUserId());
        }

        public function testLoginUpdatesLastActive(): void
        {
            $id = TestDatabase::insertUser(
                'ivan',
                (new PasswordHasher(self::LEGACY_SALT))->hash('секретный-пароль'),
                ['last_active' => '0'],
            );

            User::securityAuthorize('ivan', 'секретный-пароль');
            User::isAuthorized();

            $this->assertGreaterThan(0, (int)TestDatabase::findUser($id)['last_active']);
        }
    }
