<?php

    declare(strict_types=1);

    namespace Core\Services;

    use Core\CoreException;
    use Core\Database\DataBase;
    use Core\Helpers\Log;
    use Core\Helpers\Sanitize;
    use Core\Models\Roles;
    use Core\Models\User;
    use Core\Repositories\UserRepository;
    use Delta\Security\PasswordHasher;
    use Throwable;

    /**
     * Регистрация пользователя.
     *
     * Раньше это делал статический метод модели, совмещавший в себе валидацию уникальности,
     * запись в базу, назначение роли и две почтовые рассылки. Здесь те же шаги, но разнесённые
     * и с явной границей транзакции: в базу пользователь и его роль попадают либо вместе,
     * либо никак, а неудачная отправка письма уже не откатывает созданную учётную запись.
     */
    final class RegistrationService
    {
        /**
         * Конструктор
         *
         * @param DataBase       $db         Соединение с базой
         * @param UserRepository $repository Репозиторий пользователей
         * @param PasswordHasher $hasher     Хеширование паролей
         */
        public function __construct(
            private readonly DataBase $db,
            private readonly UserRepository $repository,
            private readonly PasswordHasher $hasher,
        ) {
        }

        /**
         * Зарегистрировать пользователя
         *
         * @param string $login    Логин
         * @param string $password Пароль
         * @param string $email    E-Mail
         * @param string $name     Имя
         *
         * @return int Идентификатор созданного пользователя
         * @throws CoreException
         */
        public function register(string $login, string $password, string $email, string $name = ''): int
        {
            $login = Sanitize::sanitizeString($login);
            $email = Sanitize::sanitizeEmail($email);
            $name  = Sanitize::sanitizeString($name);

            $this->assertUnique($login, $email);

            $userId = $this->createRecord($login, $password, $email, $name);

            // Почта отправляется за пределами транзакции: неудачная отправка не должна
            // отменять уже созданную учётную запись, но обязана быть заметна в журнале
            $this->notify($userId, $password);

            Log::logToFile('Пользователь успешно создан', 'User.log', ['userId' => $userId]);

            return $userId;
        }

        /**
         * Проверить, что логин и E-Mail свободны
         *
         * @param string $login Логин
         * @param string $email E-Mail
         *
         * @return void
         * @throws CoreException
         */
        private function assertUnique(string $login, string $email): void
        {
            if ($this->repository->exists(['login' => $login])) {
                throw new CoreException('Пользователь с данным логином уже существует', CoreException::ERROR_CREATE_USER);
            }

            if ($this->repository->exists(['email' => $email])) {
                throw new CoreException('Пользователь с данным E-Mail уже существует', CoreException::ERROR_CREATE_USER);
            }
        }

        /**
         * Создать запись пользователя вместе с ролью
         *
         * @param string $login    Логин
         * @param string $password Пароль
         * @param string $email    E-Mail
         * @param string $name     Имя
         *
         * @return int Идентификатор пользователя
         * @throws CoreException
         */
        private function createRecord(string $login, string $password, string $email, string $name): int
        {
            /**
             * Вставка пользователя и назначение роли — одна неделимая операция.
             * Без транзакции падение на втором шаге оставляло в базе пользователя без роли,
             * и откатить уже вставленную строку было некому.
             */
            $this->db->startTransaction();

            try {
                $userId = $this->repository->insert([
                    'login'             => $login,
                    'password'          => $this->hasher->hash($password),
                    'name'              => $name,
                    'image_id'          => 0,
                    'token'             => null,
                    'email'             => $email,
                    'email_confirmed'   => CODE_VALUE_N,
                    'verification_code' => bin2hex(random_bytes(16)),
                    'last_active'       => time(),
                ]);

                (new User($userId))->getRolesObject()->addRole(Roles::USER_ROLE_ID);

                $this->db->commitTransaction();
            } catch (Throwable $e) {
                $this->db->rollbackTransaction();
                Log::logToFile('Ошибка создания пользователя, транзакция откачена', 'User.log', [
                    'login' => $login,
                    'error' => $e->getMessage(),
                ]);

                throw new CoreException('Ошибка создания пользователя', CoreException::ERROR_CREATE_USER);
            }

            return (int)$userId;
        }

        /**
         * Отправить код подтверждения и реквизиты доступа
         *
         * @param int    $userId   Идентификатор пользователя
         * @param string $password Пароль в открытом виде
         *
         * @return void
         * @throws CoreException
         */
        private function notify(int $userId, string $password): void
        {
            $user = new User($userId);

            try {
                $user->sendVerificationCode();
            } catch (CoreException $e) {
                Log::logToFile('Ошибка отправки кода верификации пользователю', 'User.log', ['userId' => $userId]);

                throw new CoreException(
                    'Ошибка отправки кода верификации пользователю',
                    CoreException::ERROR_SEND_VERIFICATION_CODE
                );
            }

            $user->getMailObject()
                ->setSubject('Регистрация на сайте')
                ->setBody('<b>Логин:</b> ' . $user->getLogin() . PHP_EOL . '<b>Пароль:</b> ' . $password)
                ->setTemplateVars(['TITLE' => 'Создана учетная запись'])
                ->send();
        }
    }
