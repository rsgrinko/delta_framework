<?php

    namespace Core\Models;

    use Core\CoreException;
    use Core\DataBases\DB;
    use Core\Helpers\Cache;

    class Dialog
    {
        /** @var string Таблица с диалогами */
        const TABLE_DIALOGS = DB_TABLE_PREFIX . 'dialogs';

        /** @var string Таблица с сообщениями */
        const TABLE_MESSAGES = DB_TABLE_PREFIX . 'messages';

        /** @var string MESSAGE_TYPE_TEXT Тип сообщения: текст */
        const MESSAGE_TYPE_TEXT = 'text';

        /** @var string MESSAGE_TYPE_FILE Тип сообщения: файл */
        const MESSAGE_TYPE_FILE = 'file';

        /** @var string MESSAGE_TYPE_IMAGE Тип сообщения: изображение */
        const MESSAGE_TYPE_IMAGE = 'image';

        /**
         * @var User $user Объект пользователя
         */
        private User $user;

        public function __construct(User $user)
        {
            $this->user = $user;
        }

        /**
         * Получение идентификатора диалога
         *
         * @param int $userOne Пользователь 1
         * @param int $userTwo Пользователь 2
         *
         * @return int|null
         * @throws CoreException
         */
        public static function getDialogId(int $userOne, int $userTwo): ?int
        {
            $DB       = DB::getInstance();
            $dialogId = $DB->query(
                'SELECT `id` FROM ' . self::TABLE_DIALOGS
                    . ' WHERE (`send`="' . $userOne . '" and `receive`="' . $userTwo
                . '") OR (`send`="' . $userTwo . '" and `receive`="' . $userOne . '")'
            );
            if (!$dialogId) {
                return null;
            }
            return (int)$dialogId[0]['id'];
        }

        /**
         * Пометить диалог прочитанным
         *
         * @return void
         * @throws CoreException
         */
        private function markDialogViewed(int $dialogId): void
        {
            /** @var $DB DB Объект базы данных */
            $DB       = DB::getInstance();
            $dialogData = $DB->getItem(self::TABLE_DIALOGS, ['id' => $dialogId]);
            if ($this->user->getId() === (int)$dialogData['receive']) {
                $DB->update(self::TABLE_DIALOGS, ['id' => $dialogId], ['viewed' => CODE_VALUE_Y]);
                $DB->update(self::TABLE_MESSAGES, ['dialog_id' => $dialogId, 'user_to' => $this->user->getId(), 'viewed' => CODE_VALUE_N], ['viewed' => CODE_VALUE_Y]);
            }
        }

        public function createDialog(int $userId): ?int
        {
            /** @var $DB DB Объект базы данных */
            $DB       = DB::getInstance();
            return $DB->addItem(self::TABLE_DIALOGS, ['viewed' => CODE_VALUE_N, 'send' => $this->user->getId(), 'receive' => $userId]);
        }
        /**
         * Получение диалогов
         *
         * @return array
         * @throws CoreException
         */
        public function getDialogs(): array
        {
            /** @var $DB DB Объект базы данных */
            $DB = DB::getInstance();
            $dialogs = $DB->query(
                'SELECT * FROM ' . self::TABLE_DIALOGS . ' WHERE `send`="' . $this->user->getId() . '" OR `receive`="' . $this->user->getId() . '" ORDER BY `date_updated` DESC'
            );
            if (empty($dialogs)) {
                return [];
            }
            return $dialogs;
        }

        /**
         * Получения ID собеседника диалога
         *
         * @param int $dialogId Идентификатор диалога
         *
         * @return int|null Идентификатор собеседника
         * @throws CoreException
         */
        public function getDialogCompanionId(int $dialogId): ?int
        {
            /** @var $DB DB Объект базы данных */
            $DB     = DB::getInstance();
            $dialog = $DB->query('SELECT * FROM ' . self::TABLE_DIALOGS . ' WHERE `id`="' . $dialogId . '"');
            if (empty($dialog)) {
                return null;
            }
            $dialog = array_shift($dialog);
            if ((int)$dialog['send'] === $this->user->getId()) {
                return (int)$dialog['receive'];
            } elseif ((int)$dialog['receive'] === $this->user->getId()) {
                return (int)$dialog['send'];
            }

            return null;
        }

        /**
         * Получение сообщений диалога
         *
         * @param int  $dialogId         Идентификатор диаолога
         * @param bool $markDialogViewed Пометить диалог прочитанным
         *
         * @return array
         * @throws CoreException
         */
        public function getMessages(int $dialogId, bool $markDialogViewed = false): array
        {
            /** @var $DB DB Объект базы данных */
            $DB = DB::getInstance();
            $messages = $DB->query('SELECT * FROM ' . self::TABLE_MESSAGES . ' WHERE `dialog_id`="' . $dialogId . '"');
            if ($markDialogViewed) {
                $this->markDialogViewed($dialogId);
            }
            if (empty($messages)) {
                return [];
            }
            return $messages;
        }

        /**
         * Отправка тестовых сообщения
         *
         * @throws CoreException
         */
        public function sendMessage(int $to, string $message): bool
        {
            if (!User::isUserExistsByParams(['id' => $to])) {
                throw new CoreException('Пользователь с идентификатором ' . $to . ' отсутствует в базе', CoreException::ERROR_USER_NOT_FOUND);
            }
            /** @var $DB DB Объект базы данных */
            $DB = DB::getInstance();
            $dialogId = self::getDialogId($this->user->getId(), $to);
            if (empty($dialogId)) {
                $dialogId = $this->createDialog($to);
            } else {
                $DB->update(self::TABLE_DIALOGS, ['id' => $dialogId], ['viewed'=> CODE_VALUE_N, 'send' => $this->user->getId(), 'receive' => $to]);
            }

            $result = $DB->addItem(self::TABLE_MESSAGES, ['dialog_id' => $dialogId, 'type' => self::MESSAGE_TYPE_TEXT, 'user_from' => $this->user->getId(), 'user_to' => $to, 'text' => $message]);
            return (int)$result > 0;
        }

        /**
         * Отправка файлов
         *
         * @param int $fileId
         *
         *
         * @throws CoreException
         */
        public function sendFile(int $to, int $fileId): bool
        {
            if (!User::isUserExistsByParams(['id' => $to])) {
                throw new CoreException('Пользователь с идентификатором ' . $to . ' отсутствует в базе', CoreException::ERROR_USER_NOT_FOUND);
            }
            /** @var $DB DB Объект базы данных */
            $DB = DB::getInstance();
            $dialogId = self::getDialogId($this->user->getId(), $to);
            if (empty($dialogId)) {
                $dialogId = $this->createDialog($to);
            } else {
                $DB->update(self::TABLE_DIALOGS, ['id' => $dialogId], ['viewed'=> CODE_VALUE_N, 'send' => $this->user->getId(), 'receive' => $to]);
            }

            $result = $DB->addItem(self::TABLE_MESSAGES, ['dialog_id' => $dialogId, 'type' => self::MESSAGE_TYPE_FILE, 'user_from' => $this->user->getId(), 'user_to' => $to, 'text' => $fileId]);
            return (int)$result > 0;
        }

        /**
         * Получение количества непрочитанных сообщений в диалоге
         *
         * @param int $dialogId Идентификатор диалога
         *
         * @return int Количество
         */
        public function getDialogUnviewedMessagesCount(int $dialogId): int
        {
            /** @var $DB DB Объект базы данных */
            $DB = DB::getInstance();
            return $DB->getCountItems(self::TABLE_MESSAGES, ['dialog_id' => $dialogId, 'viewed' => CODE_VALUE_N, 'user_to' => $this->user->getId()]);
        }


    }
