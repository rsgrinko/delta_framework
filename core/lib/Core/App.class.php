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

    namespace Core;

    use Core\Helpers\Pagination;
    use Core\Helpers\Registry;
    use Core\Helpers\SystemFunctions;
    use Core\Models\{File, Posts, User};

    class App
    {
        /**
         * Получение параметров для шаблона в целом
         *
         * @return array
         * @throws CoreException
         */
        private static function getLayoutParams(): array
        {
            /** @var User $USER */
            global $USER;

            $finish_time = microtime(true);
            $delta       = round($finish_time - START_TIME, 3);
            if ($delta < 0.001) {
                $delta = 0.001;
            }

            $currentPage = explode('::', Registry::get('currentPage'))[1];
            $memoryUse   = memory_get_usage() - START_MEMORY;

            return [
                'salt'          => md5(random_int(1, 999999) . random_int(1, 999999) . random_int(1, 999999) . random_int(1, 999999)),
                'currentPage'   => $currentPage,
                'memoryUse'     => SystemFunctions::convertBytes($memoryUse),
                'executionTime' => $delta . ' cek.',
                'isAuthorized'  => User::isAuthorized(),
                'isAdmin'       => User::isAuthorized() && $USER->isAdmin(),
                'userData'      => User::isAuthorized() ? $USER->getAllUserData(true) : [],
                'currentYear'   => date('Y'),
                'newMessages'   => User::isAuthorized() ? $USER->getDialogObject()->getUnviewedMessagesCount() : 0,
            ];
        }

        /**
         * Отдача на рендер
         *
         * @param string $template Шаблон
         * @param array  $params   Параметры
         *
         * @return void
         * @throws CoreException
         */
        private static function render(string $template, array $params = []): void
        {
            global $twig;
            $params = array_merge(self::getLayoutParams(), $params);
            echo $twig->render($template, $params);
        }

        public static function index()
        {
            self::render('index.twig');
        }

        public static function info()
        {
            $buildInfo = [
                'Версия ядра'               => CORE_VERSION,
                'Лимит пагинации'           => PAGINATION_LIMIT,
                'Время для расчета онлайна' => USER_ONLINE_TIME,
                'Время жизни кеша'          => CACHE_TTL,
                'E-Mail сайта'              => SERVER_EMAIL,
                'Имя отправителя сайта'     => SERVER_EMAIL_NAME,
            ];
            self::render('info.twig', ['data' => $buildInfo]);
        }

        public static function dialogs()
        {
            /** @var User $USER */
            global $USER;
            self::render('dialogs.twig', ['dialogs' => User::isAuthorized() ? $USER->getDialogs() : []]);
        }

        public static function dialog(int $id)
        {
            /** @var User $USER */
            global $USER;
            self::render(
                'dialog.twig',
                [
                    'dialog_id'     => $id,
                    'messages'      => $USER->getMessages($id, true),
                    'userId'        => $USER->getId(),
                    'companionId'   => $USER->getDialogObject()->getDialogCompanionId($id),
                    'companionName' => (new User($USER->getDialogObject()->getDialogCompanionId($id)))->getName(),
                ]
            );
        }

        public static function sendMessage(int $userId)
        {
            /** @var User $USER */
            global $USER;
            if (empty($_FILES['file']['tmp_name'])) {
                $USER->getDialogObject()->sendMessage($userId, $_REQUEST['message']);
            } else {
                $fileObject = new File();
                $fileObject->saveFile($_FILES['file']['tmp_name'], $_FILES['file']['name'], true);

                $USER->getDialogObject()->sendFile($userId, $fileObject->getId(), in_array($_FILES['file']['type'], ['image/jpeg', 'image/png', 'image/gif', 'image/webp'], true));
            }

            header('Location: /dialog/' . (int)$_REQUEST['dialogId']);
        }

        public static function users()
        {
            Pagination::execute($_REQUEST['page'], User::getAllCount(), PAGINATION_LIMIT);
            $limit = Pagination::getLimit();
            $limit= '0, 10';
            $arUsers = User::getUsers($limit);
            foreach($arUsers as $key => $user) {
                unset($arUsers[$key]['password']);
            }
            self::render('users.twig', ['users' => $arUsers]);
        }


        public static function test($a = null, $b = null, $c = null, $d = null)
        {
            print_r([$a, $b, $c, $d]);
            self::render('test.twig');
        }

        public static function logout()
        {
            User::logout();
            header('Location: /');
        }

        public static function loginAuthorize()
        {
            if (User::securityAuthorize($_REQUEST['login'], $_REQUEST['password'], false)) {
                header('Location: /');
            } else {
                header('Location: /login/failed');
            }
        }

        public static function login()
        {
            self::render('login.twig', ['failed' => false]);
        }

        public static function loginFailed()
        {
            self::render('login.twig', ['failed' => true]);
        }

        public static function userProfile(int $id)
        {
            $userData = (new User($id))->getAllUserData(true);
            self::render('userProfile.twig', ['userData' => $userData]);
        }

        public static function goToDialog(int $userId)
        {
            /** @var User $USER */
            global $USER;
            $dialogId = $USER->getDialogObject()->getDialogId($USER->getId(), $userId);
            if (empty($dialogId)) {
                $dialogId = $USER->getDialogObject()->createDialog($userId);
            }
            header('Location: /dialog/' . $dialogId);
        }
    }