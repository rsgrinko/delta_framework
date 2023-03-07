<?php
    /**
     * Copyright (c) 2022 Roman Grinko <rsgrinko@gmail.com>
     * Permission is hereby granted, free of charge, to any person obtaining
     * a copy of this software and associated documentation files (the
     * "Software"), to deal in the Software without restriction, including
     * without limitation the rights to use, copy, modify, merge, publish,
     * distribute, sublicense, and/or sell copies of the Software, and to
     * permit persons to whom the Software is furnished to do so, subject to
     * the following conditions:
     * The above copyright notice and this permission notice shall be included
     * in all copies or substantial portions of the Software.
     * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
     * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
     * MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.
     * IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY
     * CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT,
     * TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE
     * SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
     */

    namespace Core\Api;

    use Composer\Util\RemoteFilesystem;
    use Core\CoreException;
    use Core\Models\DB;
    use Core\Models\File;
    use Core\Models\MQ;
    use Core\Models\User;

    /**
     * Класс API контроллера
     */
    class ApiController
    {

        /** @var User $userObject Объект пользователя */
        private $userObject;

        /** @var Request $request Объект запроса */
        private $request;

        /**
         * Конструктор
         *
         * @param User $userObject Объект пользователя
         */
        public function __construct(User $userObject)
        {
            $this->userObject = $userObject;
            $this->request   = new Request();
        }

        /**
         * Получение информации о текущем пользователе
         *
         * @return void
         * @throws CoreException
         * @throws \JsonException
         */
        public function getUserInfo(): void
        {
            $result = $this->userObject->getAllUserData();
            $result['image'] = (new File((int)$result['image_id']))->getAllProps();
            $result['email_confirmed'] = $result['email_confirmed'] === CODE_VALUE_Y;
            unset($result['password'], $result['token'], $result['image_id']);
            ApiView::output($result);
        }

        public static function createUser(): void
        {
            $request         = new Request();
            $login           = $request->getProperty('login');
            $password        = $request->getProperty('password');
            $confirmPassword = $request->getProperty('confirmPassword');
            $email           = $request->getProperty('email');
            $name            = $request->getProperty('name') ?: '';

            if (empty($login)) {
                throw new ApiException('Не задан логин', ApiException::ERROR_INPUT_DATA);
            }
            if (empty($password)) {
                throw new ApiException('Не задан пароль', ApiException::ERROR_INPUT_DATA);
            }
            if (empty($email)) {
                throw new ApiException('Не задан E-Mail', ApiException::ERROR_INPUT_DATA);
            }
            if ($password !== $confirmPassword) {
                throw new ApiException('Пароль и подтверждение пароля не совпадают', ApiException::ERROR_INPUT_DATA);
            }
            $userId = User::create($login, $password, $email, $name);
            ApiView::output(['userId' => $userId]);
        }

        /**
         * Авторизация пользователя и получение токена доступа
         *
         * @return void
         * @throws ApiException
         * @throws CoreException
         * @throws \JsonException
         */
        public static function getToken(): void
        {
            $request         = new Request();
            $login           = $request->getProperty('login');
            $password        = $request->getProperty('password');
            if (empty($login)) {
                throw new ApiException('Не задан логин', ApiException::ERROR_INPUT_DATA);
            }
            if (empty($password)) {
                throw new ApiException('Не задан пароль', ApiException::ERROR_INPUT_DATA);
            }

            $userObject = User::getByParams(['login' => $login, 'password' => User::passwordEncryption($password)]);
            if ($userObject === null) {
                throw new ApiException('Авторизация не удалась', ApiException::ERROR_AUTHORIZE);
            }
            $token = $userObject->getToken();

            // Если токен отсутствует - сгенерируем его
            if (empty($token)) {
                $token = $userObject->createToken();
            }

            ApiView::output(['id' => $userObject->getId(), 'login' => $login, 'token' => $token]);
        }

        /**
         * Получение информации о ролях текущего пользователя
         *
         * @return void
         * @throws CoreException
         * @throws \JsonException
         */
        public function getUserRoles(): void
        {
            $result = $this->userObject->getRolesObject()->getFullRoles();
            ApiView::output($result);
        }

        /**
         * Отправка кода верификации
         *
         * @return void
         * @throws CoreException
         * @throws \JsonException
         */
        public function sendVerificationCode(): void
        {
            $result = $this->userObject->sendVerificationCode();
            ApiView::output($result);
        }

        /**
         * Смена имени пользователя
         *
         * @return void
         * @throws CoreException
         * @throws \JsonException
         * @throws ApiException
         */
        public function changeName(): void
        {
            $name = trim($this->request->getProperty('name'));
            if (empty($name)) {
                throw new ApiException('Новое имя не может быть пустым', ApiException::ERROR_INPUT_DATA);
            }
            $this->userObject->update(['name' => $name]);
            ApiView::output(true);
        }

        /**
         * Получить статистику диспетчера очереди
         *
         * @return void
         * @throws CoreException
         * @throws \JsonException
         * @throws ApiException
         */
        public function getMQStat(): void
        {
            $MQ              = new MQ();
            $allCount        = $MQ->getCountTasks();
            $countWorkers    = $MQ->getCountWorkers();
            $limitWorkers    = MQ::WORKERS_LIMIT;
            $activeCount     = $MQ->getCountTasks(['active' => 'Y']);
            $inProgressCount = $MQ->getCountTasks(['active' => 'Y', 'in_progress' => 'Y']);
            $errorCount      = $MQ->getCountTasks(['in_progress' => 'N', 'status' => MQ::STATUS_ERROR]);
            ApiView::output(
                [
                    'limitWorkers'    => $limitWorkers,
                    'countWorkers'    => $countWorkers,
                    'allCount'        => $allCount,
                    'activeCount'     => $activeCount,
                    'inProgressCount' => $inProgressCount,
                    'errorCount'      => $errorCount,
                ]
            );
        }


        /**
         * Тестовый метод (не требующий авторизации)
         *
         * @return void
         * @throws \JsonException
         */
        public static function testNoAuth(): void
        {
            ApiView::output(
                [
                    'message'   => 'test completed',
                    'randomInt' =>  random_int(1000, 9999),
                ]
            );
        }


    }
