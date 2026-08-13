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

    use Core\Helpers\Captcha;
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
                'SITE_URL'      => SITE_URL,
                'USE_CAPTCHA'   => USE_CAPTCHA,
                'salt'          => md5(random_int(1, 999999) . random_int(1, 999999) . random_int(1, 999999) . random_int(1, 999999)),
                'currentPage'   => $currentPage,
                'memoryUse'     => SystemFunctions::convertBytes($memoryUse),
                'executionTime' => $delta . ' cek.',
                'isAuthorized'  => User::isAuthorized(),
                'isAdmin'       => User::isAuthorized() && $USER->isAdmin(),
                'userData'      => User::isAuthorized() ? $USER->getAllUserData(true) : [],
                'userAvatar'    => User::isAuthorized() ? $USER->getImage() : null,
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
            self::render('index.twig', [
                'stats' => [
                    'usersTotal'    => User::getUsersCount(),
                    'usersOnline'   => User::getOnlineCount(),
                    'usersVerified' => User::getUsersCount(true),
                    'coreVersion'   => CORE_VERSION,
                ],
            ]);
        }

        public static function info()
        {
            $buildInfo = [
                'Версия ядра'                => CORE_VERSION,
                'Версия PHP'                 => PHP_VERSION,
                'Лимит пагинации'            => SystemConfig::getValue('PAGINATION_LIMIT'),
                'Время для расчета онлайна'  => USER_ONLINE_TIME . ' сек.',
                'Кеширование'                => USE_CACHE ? 'Включено' : 'Выключено',
                'Время жизни кеша'           => CACHE_TTL . ' сек.',
                'Captcha'                    => USE_CAPTCHA ? 'Включена' : 'Выключена',
                'Защита от DDoS'             => USE_DDOS_PROTECTION ? 'Включена' : 'Выключена',
                'Префикс таблиц БД'          => DB_TABLE_PREFIX,
                'E-Mail сайта'               => SERVER_EMAIL,
                'Имя отправителя сайта'      => SERVER_EMAIL_NAME,
                'Часовой пояс'               => date_default_timezone_get(),
            ];
            self::render('info.twig', ['data' => $buildInfo]);
        }

        public static function dialogs()
        {
            /** @var User $USER */
            global $USER;
            $dialogs = [];
            if (User::isAuthorized()) {
                $dialogs = $USER->getDialogs();
                foreach ($dialogs as $key => $dialog) {
                    $dialogs[$key]['companionOnline'] = User::isOnline((int)$dialog['companionId']);
                    $dialogs[$key]['messagesCount']    = $USER->getDialogObject()->getDialogMessagesCount((int)$dialog['id']);
                    $dialogs[$key]['lastMessage']      = $USER->getDialogObject()->getLastMessage((int)$dialog['id']);
                    $dialogs[$key]['companionAvatar']  = self::getAvatarPath($dialog['companionData']['image_id'] ?? null);
                }
            }
            self::render('dialogs.twig', [
                'dialogs' => $dialogs,
                'userId'  => User::isAuthorized() ? $USER->getId() : null,
            ]);
        }

        public static function dialog(int $id)
        {
            /** @var User $USER */
            global $USER;
            $companionId     = $USER->getDialogObject()->getDialogCompanionId($id);
            $companionObject = new User($companionId);
            self::render(
                'dialog.twig',
                [
                    'dialog_id'       => $id,
                    'messages'        => $USER->getMessages($id, true),
                    'userId'          => $USER->getId(),
                    'companionId'     => $companionId,
                    'companionName'   => $companionObject->getName(),
                    'companionAvatar' => self::getAvatarPath($companionObject->getAllUserData(true)['image_id'] ?? null),
                    'companionOnline' => User::isOnline($companionId),
                    'messagesCount'   => $USER->getDialogObject()->getDialogMessagesCount($id),
                ]
            );
        }

        /**
         * Получение пути к файлу аватара по идентификатору изображения
         *
         * @param int|null $imageId Идентификатор файла
         *
         * @return string|null
         */
        private static function getAvatarPath(?int $imageId): ?string
        {
            if (empty($imageId)) {
                return null;
            }
            try {
                $image = (new File((int)$imageId))->getAllProps();
            } catch (CoreException $e) {
                return null;
            }
            return $image['path'] ?? null;
        }

        public static function sendMessage(int $userId)
        {
            /** @var User $USER */
            global $USER;

            $isAjax  = ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';
            $message = trim((string)($_REQUEST['message'] ?? ''));

            if (empty($_FILES['file']['tmp_name']) && $message === '') {
                if ($isAjax) {
                    self::outputJson(['success' => false, 'error' => 'Сообщение пустое']);
                }
                header('Location: /dialog/' . (int)$_REQUEST['dialogId']);
                return;
            }

            if (empty($_FILES['file']['tmp_name'])) {
                $USER->getDialogObject()->sendMessage($userId, $message);
            } else {
                $fileObject = new File();
                $fileObject->saveFile($_FILES['file']['tmp_name'], $_FILES['file']['name'], true);

                $USER->getDialogObject()->sendFile($userId, $fileObject->getId(), in_array($_FILES['file']['type'], ['image/jpeg', 'image/png', 'image/gif', 'image/webp'], true));
            }

            $dialogId = (int)$_REQUEST['dialogId'];

            if ($isAjax) {
                self::outputDialogJson($dialogId);
            }

            header('Location: /dialog/' . $dialogId);
        }

        public static function dialogMessages(int $id)
        {
            self::outputDialogJson($id);
        }

        /**
         * Отдаёт JSON со свежим списком сообщений диалога (для AJAX-отправки и опроса новых сообщений)
         *
         * @param int $dialogId Идентификатор диалога
         *
         * @return void
         */
        private static function outputDialogJson(int $dialogId): void
        {
            /** @var User $USER */
            global $USER, $twig;

            if (empty($USER)) {
                self::outputJson(['success' => false, 'error' => 'Не авторизован']);
            }

            $companionId = $USER->getDialogObject()->getDialogCompanionId($dialogId);
            if ($companionId === null) {
                self::outputJson(['success' => false, 'error' => 'Диалог не найден']);
            }

            $html = $twig->render('_messageList.twig', [
                'messages' => $USER->getMessages($dialogId, true),
                'userId'   => $USER->getId(),
            ]);

            self::outputJson([
                'success'         => true,
                'html'            => $html,
                'messagesCount'   => $USER->getDialogObject()->getDialogMessagesCount($dialogId),
                'companionOnline' => User::isOnline($companionId),
            ]);
        }

        /**
         * Отдаёт массив данных в виде JSON и завершает работу скрипта
         *
         * @param array $data Данные
         *
         * @return void
         */
        private static function outputJson(array $data): void
        {
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode($data);
            die();
        }

        public static function users()
        {
            Pagination::execute((int)($_REQUEST['page'] ?? 1), User::getAllCount(), (string)SystemConfig::getValue('PAGINATION_LIMIT'));
            $limit = Pagination::getLimit();
            $arUsers = User::getUsers($limit);
            foreach($arUsers as $key => $user) {
                unset($arUsers[$key]['password']);
                $arUsers[$key]['online'] = User::isOnline((int)$user['id']);
            }
            self::render('users.twig', [
                'users'      => $arUsers,
                'page'       => Pagination::getPage(),
                'totalPages' => Pagination::getTotalPages(),
            ]);
        }

        public static function logout()
        {
            Captcha::clearSession();
            User::logout();
            header('Location: /');
        }

        public static function loginAuthorize()
        {
            $captchaCorrect = true;
            if (USE_CAPTCHA) {
                if (empty($_REQUEST['captchaCode'])) {
                    $_SESSION['authErrorMessage'] = 'Не введен код с картинки';
                    $captchaCorrect = false;
                } else {
                    $captchaCorrect = Captcha::isValidCaptcha($_REQUEST['captchaCode']);
                    if ($captchaCorrect === false) {
                        $_SESSION['authErrorMessage'] = 'Неверный код с картинки';
                    }
                }
            }

            if ($captchaCorrect) {
                if (User::securityAuthorize($_REQUEST['login'], $_REQUEST['password'], false)) {
                    unset($_SESSION['authErrorMessage']);
                    header('Location: /');
                } else {
                    $_SESSION['authErrorMessage'] = 'Неверный логин или пароль';
                    header('Location: /login/failed');
                }
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
            self::render('login.twig', ['failed' => true, 'errorMessage' => $_SESSION['authErrorMessage']]);
        }

        public static function userProfile(int $id)
        {
            $userObject = new User($id);
            self::render('userProfile.twig', [
                'userData' => $userObject->getAllUserData(true),
                'roles'    => $userObject->getRolesObject()->getFullRoles(),
                'online'   => User::isOnline($id),
                'avatar'   => $userObject->getImage(),
            ]);
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

        public static function profile()
        {
            /** @var User $USER */
            global $USER;
            if (empty($USER)) {
                header('Location: /login');
                return;
            }

            $message = $_SESSION['profileMessage'] ?? null;
            unset($_SESSION['profileMessage']);

            self::render('profile.twig', [
                'profileData'    => $USER->getAllUserData(true),
                'roles'          => $USER->getRolesObject()->getFullRoles(),
                'avatar'         => $USER->getImage(),
                'emailConfirmed' => $USER->isEmailConfirmed(),
                'message'        => $message,
            ]);
        }

        public static function profileUpdatePersonal()
        {
            /** @var User $USER */
            global $USER;
            if (empty($USER)) {
                header('Location: /login');
                return;
            }

            $name  = trim((string)($_REQUEST['name'] ?? ''));
            $email = trim((string)($_REQUEST['email'] ?? ''));

            try {
                if ($name !== '') {
                    $USER->update(['name' => $name]);
                }
                if ($email !== '' && $email !== $USER->getEmail()) {
                    $USER->changeEmail($email);
                    self::setProfileMessage('success', 'Данные обновлены. На новый E-Mail отправлен код подтверждения.');
                } else {
                    self::setProfileMessage('success', 'Данные успешно обновлены.');
                }
            } catch (CoreException $e) {
                self::setProfileMessage('error', $e->getMessage());
            }

            header('Location: /profile');
        }

        public static function profileUpdatePassword()
        {
            /** @var User $USER */
            global $USER;
            if (empty($USER)) {
                header('Location: /login');
                return;
            }

            $currentPassword = (string)($_REQUEST['currentPassword'] ?? '');
            $newPassword     = (string)($_REQUEST['newPassword'] ?? '');
            $confirmPassword = (string)($_REQUEST['confirmPassword'] ?? '');

            if ($newPassword === '' || $newPassword !== $confirmPassword) {
                self::setProfileMessage('error', 'Новый пароль пуст или не совпадает с подтверждением.');
            } elseif (!$USER->changePassword($currentPassword, $newPassword)) {
                self::setProfileMessage('error', 'Текущий пароль указан неверно.');
            } else {
                self::setProfileMessage('success', 'Пароль успешно изменён.');
            }

            header('Location: /profile');
        }

        public static function profileUpdateAvatar()
        {
            /** @var User $USER */
            global $USER;
            if (empty($USER)) {
                header('Location: /login');
                return;
            }

            if (empty($_FILES['avatar']['tmp_name'])) {
                self::setProfileMessage('error', 'Файл не выбран.');
                header('Location: /profile');
                return;
            }

            if (!in_array($_FILES['avatar']['type'], ['image/jpeg', 'image/png', 'image/gif', 'image/webp'], true)) {
                self::setProfileMessage('error', 'Аватар должен быть изображением (JPEG, PNG, GIF или WEBP).');
                header('Location: /profile');
                return;
            }

            try {
                $fileObject = new File();
                $fileObject->saveFile($_FILES['avatar']['tmp_name'], $_FILES['avatar']['name'], true);
                $USER->update(['image_id' => $fileObject->getId()]);
                self::setProfileMessage('success', 'Аватар обновлён.');
            } catch (CoreException $e) {
                self::setProfileMessage('error', 'Не удалось загрузить файл.');
            }

            header('Location: /profile');
        }

        public static function profileResendVerification()
        {
            /** @var User $USER */
            global $USER;
            if (empty($USER)) {
                header('Location: /login');
                return;
            }

            try {
                $USER->sendVerificationCode();
                self::setProfileMessage('success', 'Письмо с кодом подтверждения отправлено повторно.');
            } catch (CoreException $e) {
                self::setProfileMessage('error', 'Не удалось отправить письмо подтверждения.');
            }

            header('Location: /profile');
        }

        /**
         * Сохранение сообщения для отображения на странице личного кабинета
         *
         * @param string $type Тип сообщения (success|error)
         * @param string $text Текст сообщения
         *
         * @return void
         */
        private static function setProfileMessage(string $type, string $text): void
        {
            $_SESSION['profileMessage'] = ['type' => $type, 'text' => $text];
        }
    }