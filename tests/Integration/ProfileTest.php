<?php

    declare(strict_types=1);

    namespace Tests\Integration;

    use Core\CoreException;
    use Core\Models\User;
    use Delta\Security\PasswordHasher;
    use PHPUnit\Framework\TestCase;
    use Tests\Support\TestDatabase;

    /**
     * Интеграционные тесты личного кабинета.
     *
     * Отдельное внимание — согласованности сессии при смене пароля: это место уже приводило
     * к тому, что пользователь молча разлогинивался на следующем запросе, а выглядело это
     * как «неверный текущий пароль» при второй попытке.
     */
    class ProfileTest extends TestCase
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
         * Создать пользователя и войти под ним
         *
         * @param string $login    Логин
         * @param string $password Пароль
         *
         * @return User Объект пользователя
         * @throws CoreException
         */
        private function loginAs(string $login, string $password): User
        {
            $id = TestDatabase::insertUser($login, (new PasswordHasher(self::LEGACY_SALT))->hash($password));
            User::securityAuthorize($login, $password);

            return new User($id);
        }

        public function testProfileDataIsReadable(): void
        {
            $user = $this->loginAs('ivan', 'секретный-пароль');

            $this->assertSame('ivan', $user->getLogin());
            $this->assertSame('ivan@example.test', $user->getEmail());
            $this->assertSame('Тестовый пользователь', $user->getName());
        }

        public function testSecureDataHidesPasswordAndToken(): void
        {
            $user = $this->loginAs('ivan', 'секретный-пароль');

            $data = $user->getAllUserData(true);

            $this->assertArrayNotHasKey('password', $data);
            $this->assertArrayNotHasKey('token', $data);
            $this->assertArrayNotHasKey('verification_code', $data);
            $this->assertArrayHasKey('login', $data);
        }

        public function testUpdateChangesStoredFields(): void
        {
            $user = $this->loginAs('ivan', 'секретный-пароль');

            $user->update(['name' => 'Новое имя']);

            $this->assertSame('Новое имя', TestDatabase::findUser($user->getId())['name']);
            $this->assertSame('Новое имя', $user->getName(), 'кэш данных обязан быть сброшен после записи');
        }

        public function testChangePasswordStoresNewHash(): void
        {
            $user = $this->loginAs('ivan', 'старый-пароль');

            $this->assertTrue($user->changePassword('старый-пароль', 'новый-пароль'));

            $stored = (string)TestDatabase::findUser($user->getId())['password'];

            $this->assertTrue((new PasswordHasher(self::LEGACY_SALT))->verify('новый-пароль', $stored));
            $this->assertFalse((new PasswordHasher(self::LEGACY_SALT))->verify('старый-пароль', $stored));
        }

        public function testChangePasswordRejectsWrongCurrentPassword(): void
        {
            $user = $this->loginAs('ivan', 'старый-пароль');

            $this->assertFalse($user->changePassword('не-тот-пароль', 'новый-пароль'));

            $stored = (string)TestDatabase::findUser($user->getId())['password'];
            $this->assertTrue((new PasswordHasher(self::LEGACY_SALT))->verify('старый-пароль', $stored));
        }

        public function testSessionSurvivesPasswordChange(): void
        {
            $user = $this->loginAs('ivan', 'старый-пароль');

            $user->changePassword('старый-пароль', 'новый-пароль');

            $this->assertTrue(
                User::isAuthorized(),
                'после смены пароля сессия обязана остаться действительной, иначе пользователя разлогинит молча'
            );
        }

        public function testUserCanChangePasswordTwiceInARow(): void
        {
            $user = $this->loginAs('ivan', 'первый');

            $this->assertTrue($user->changePassword('первый', 'второй'));
            $this->assertTrue(
                $user->changePassword('второй', 'третий'),
                'вторая смена подряд не должна упираться в устаревший отпечаток сессии'
            );
        }

        public function testPasswordChangedWithLegacyHashWorks(): void
        {
            $id = TestDatabase::insertUser('старый', md5(self::LEGACY_SALT . 'старый-пароль'));
            User::securityAuthorize('старый', 'старый-пароль');
            $user = new User($id);

            $this->assertTrue(
                $user->changePassword('старый-пароль', 'новый-пароль'),
                'смена пароля обязана работать и сразу после миграции хеша'
            );
        }

        public function testLoginWorksAfterPasswordChange(): void
        {
            $user = $this->loginAs('ivan', 'старый-пароль');
            $user->changePassword('старый-пароль', 'новый-пароль');

            TestDatabase::resetRuntimeState();

            $this->assertTrue(User::securityAuthorize('ivan', 'новый-пароль'));
            $this->assertFalse(User::securityAuthorize('ivan', 'старый-пароль'));
        }

        public function testRolesAreReadableForUser(): void
        {
            $user = $this->loginAs('ivan', 'секретный-пароль');
            $user->getRolesObject()->addRole(2);

            $this->assertContains(2, $user->getRolesObject()->getRoles());
            $this->assertFalse($user->isAdmin());
        }

        public function testAdminRoleIsRecognised(): void
        {
            $user = $this->loginAs('admin', 'секретный-пароль');
            $user->getRolesObject()->addRole(1);

            $this->assertTrue($user->isAdmin());
        }
    }
