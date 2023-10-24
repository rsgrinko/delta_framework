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

    use Core\CoreException;
    use Core\Models\File;
    use Core\Models\MQ;
    use Core\Models\User;
    use Core\SystemConfig;

    /**
     * Класс API контроллера
     */
    class ApiController
    {

        /** @var User $userObject Объект пользователя */
        private User $userObject;

        /** @var Request $request Объект запроса */
        private Request $request;

        /** @var int $from Выборка ОТ */
        private int $from;

        /** @var int|null $to Выборка ДО */
        private ?int $to;

        /** @var int|null $count Общее количество записей */
        private ?int $count;

        /**
         * Конструктор
         *
         * @param User $userObject Объект пользователя
         */
        public function __construct(User $userObject)
        {
            $this->userObject = $userObject;
            $this->request   = new Request();

            // Задаем параметры пагинации при наличии
            $from = $this->request->getProperty('from');
            $to   = $this->request->getProperty('to');
            if ($from !== null) {
                $this->from = (int)$from;
            } else {
                $this->from = 0;
            }

            if ($to !== null) {
                $this->to = (int)$to;
            } else {
                $this->to = SystemConfig::getValue('PAGINATION_LIMIT');
            }
        }

        /**
         * Получение мета информации
         *
         * @return array
         */
        private function getMetaData(): array
        {
            return [
                'count' => $this->count,
                'from'  => $this->from,
                'to'    => $this->to,
            ];

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
         * Тестовый метод 1 (не требующий авторизации)
         *
         * @return void
         * @throws \JsonException
         */
        public static function test(): void
        {
            ApiView::output(
                [
                    'message'   => 'test completed',
                    'randomInt' =>  random_int(1000, 9999),
                    'hash'      =>  md5(time()),
                ]
            );
        }

        /**
         * Тестовый метод 2 (не требующий авторизации)
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

        /**
         * Отправка писем
         *
         * @return void
         * @throws CoreException
         * @throws \JsonException
         */
        public function sendMail(): void
        {
            $responseObject = (new MQ())->setPriority(7)
                             ->setAttempts(2)
                             ->createTask('Core\MQTasks',
                                          'sendMail',
                                          [
                                              SERVER_EMAIL,
                                              SERVER_EMAIL_NAME,
                                              $this->request->getProperty('to'),
                                              null,
                                              $this->request->getProperty('subject'),
                                              $this->request->getProperty('body'),
                                              SystemConfig::getValue('MAIL_TEMPLATE_DEFAULT'),
                                              ['TITLE' => $this->request->getProperty('subject')],
                                          ]
                             );


            ApiView::output(
                [
                    'status'   => $responseObject->getStatus(),
                    'response' => $responseObject->getResponse(),
                ]
            );
        }

        /**
         * Получение списка диалогов
         *
         * @return void
         * @throws CoreException
         * @throws \JsonException
         */
        public function getDialogs(): void
        {
            ApiView::output($this->userObject->getDialogs());
        }

        /**
         * Получение сообщений диалога
         *
         * @return void
         * @throws CoreException
         * @throws \JsonException
         */
        public function getDialog(): void
        {
            $dialogId = (int)trim($this->request->getProperty('dialogId'));
            if ($dialogId === 0) {
                throw new ApiException('Идентификатор диалога некорректен', ApiException::ERROR_INPUT_DATA);
            }
            $this->count = $this->userObject->getDialogObject()->getDialogMessagesCount($dialogId);
            ApiView::output(
                $this->userObject->getDialogObject()->getMessages($dialogId, true, $this->getMetaData()),
                $this->getMetaData()
            );
        }

        /**
         * Получение сообщений диалога
         *
         * @return void
         * @throws CoreException
         * @throws \JsonException
         */
        public function sendMessage(): void
        {
            $to = (int)trim($this->request->getProperty('toUserId'));
            if ($to === 0) {
                throw new ApiException('Идентификатор диалога некорректен', ApiException::ERROR_INPUT_DATA);
            }
            $message = trim($this->request->getProperty('message'));
            if (empty($message)) {
                throw new ApiException('Сообщение не может быть пустым', ApiException::ERROR_INPUT_DATA);
            }
            ApiView::output($this->userObject->getDialogObject()->sendMessage($to, $message));
        }

    }
