<?php

    namespace Core\Models;

    use Core\CoreException;
    use Core\Database\DataBase;
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
            $DB       = DataBase::getInstance();
            $dialogId = $DB->query(
                'SELECT `id` FROM ' . self::TABLE_DIALOGS
                    . ' WHERE (`send`=:userOne1 and `receive`=:userTwo1) OR (`send`=:userTwo2 and `receive`=:userOne2)',
                ['userOne1' => $userOne, 'userTwo1' => $userTwo, 'userTwo2' => $userTwo, 'userOne2' => $userOne]
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
            /** @var $DB DataBase Объект базы данных */
            $DB = DataBase::getInstance();
            $DB->update(self::TABLE_DIALOGS, ['id' => $dialogId], ['viewed' => CODE_VALUE_Y]);
            $DB->update(self::TABLE_MESSAGES, ['dialog_id' => $dialogId, 'user_to' => $this->user->getId(), 'viewed' => CODE_VALUE_N], ['viewed' => CODE_VALUE_Y]);
        }

        public function createDialog(int $userId): ?int
        {
            /** @var $DB DataBase Объект базы данных */
            $DB       = DataBase::getInstance();
            return $DB->add(self::TABLE_DIALOGS, ['viewed' => CODE_VALUE_N, 'send' => $this->user->getId(), 'receive' => $userId]);
        }
        /**
         * Получение диалогов
         *
         * @return array
         * @throws CoreException
         */
        public function getDialogs(): array
        {
            /** @var $DB DataBase Объект базы данных */
            $DB = DataBase::getInstance();
            $dialogs = $DB->query(
                'SELECT * FROM ' . self::TABLE_DIALOGS . ' WHERE `send`=:userId1 OR `receive`=:userId2 ORDER BY `date_updated` DESC',
                ['userId1' => $this->user->getId(), 'userId2' => $this->user->getId()]
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
            /** @var $DB DataBase Объект базы данных */
            $DB     = DataBase::getInstance();
            $dialog = $DB->query('SELECT * FROM ' . self::TABLE_DIALOGS . ' WHERE `id`=:dialogId', ['dialogId' => $dialogId]);
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
        public function getMessages(int $dialogId, bool $markDialogViewed = false, ?array $paginationData = null): array
        {
            /** @var $DB DataBase Объект базы данных */
            $DB = DataBase::getInstance();
            $paginationSqlString = '';
            if ($paginationData !== null) {
                // LIMIT/OFFSET не могут быть переданы через именованный плейсхолдер подготовленного
                // выражения (PDO::ATTR_EMULATE_PREPARES выключен для MySQL), поэтому приводим к int и
                // подставляем в текст запроса напрямую — приведение типа исключает инъекцию.
                $paginationSqlString = ' LIMIT ' . (int)$paginationData['from'] . ', ' . (int)$paginationData['to'];
            }
            $messages = $DB->query(
                'SELECT * FROM ' . self::TABLE_MESSAGES . ' WHERE `dialog_id`=:dialogId ORDER BY id ASC' . $paginationSqlString,
                ['dialogId' => $dialogId]
            );
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
            /** @var $DB DataBase Объект базы данных */
            $DB = DataBase::getInstance();
            $dialogId = self::getDialogId($this->user->getId(), $to);
            if (empty($dialogId)) {
                $dialogId = $this->createDialog($to);
            } else {
                $DB->update(self::TABLE_DIALOGS, ['id' => $dialogId], ['viewed'=> CODE_VALUE_N, 'send' => $this->user->getId(), 'receive' => $to]);
            }

            $result = $DB->add(self::TABLE_MESSAGES, ['dialog_id' => $dialogId, 'type' => self::MESSAGE_TYPE_TEXT, 'user_from' => $this->user->getId(), 'user_to' => $to, 'text' => $message]);
            return (int)$result > 0;
        }

        /**
         * Отправка файлов
         *
         * @param int  $to
         * @param int  $fileId
         * @param bool $sendAsImage
         *
         * @return bool
         * @throws CoreException
         */
        public function sendFile(int $to, int $fileId, bool $sendAsImage = false): bool
        {
            if (!User::isUserExistsByParams(['id' => $to])) {
                throw new CoreException('Пользователь с идентификатором ' . $to . ' отсутствует в базе', CoreException::ERROR_USER_NOT_FOUND);
            }
            /** @var $DB DataBase Объект базы данных */
            $DB = DataBase::getInstance();
            $dialogId = self::getDialogId($this->user->getId(), $to);
            if (empty($dialogId)) {
                $dialogId = $this->createDialog($to);
            } else {
                $DB->update(self::TABLE_DIALOGS, ['id' => $dialogId], ['viewed'=> CODE_VALUE_N, 'send' => $this->user->getId(), 'receive' => $to]);
            }

            $result = $DB->add(self::TABLE_MESSAGES, ['dialog_id' => $dialogId, 'type' => $sendAsImage ? self::MESSAGE_TYPE_IMAGE : self::MESSAGE_TYPE_FILE, 'user_from' => $this->user->getId(), 'user_to' => $to, 'text' => $fileId]);
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
            /** @var $DB DataBase Объект базы данных */
            $DB = DataBase::getInstance();
            return $DB->getCount(self::TABLE_MESSAGES, ['dialog_id' => $dialogId, 'viewed' => CODE_VALUE_N, 'user_to' => $this->user->getId()]);
        }

        /**
         * Получение количества непрочитанных сообщений
         *
         * @return int Количество
         */
        public function getUnviewedMessagesCount(): int
        {
            /** @var $DB DataBase Объект базы данных */
            $DB = DataBase::getInstance();
            return $DB->getCount(self::TABLE_MESSAGES, ['viewed' => CODE_VALUE_N, 'user_to' => $this->user->getId()]);
        }

        /**
         * Получение количества сообщений в диалоге
         *
         * @param int $dialogId Идентификатор диалога
         *
         * @return int Количество
         */
        public function getDialogMessagesCount(int $dialogId): int
        {
            /** @var $DB DataBase Объект базы данных */
            $DB = DataBase::getInstance();
            return $DB->getCount(self::TABLE_MESSAGES, ['dialog_id' => $dialogId]);
        }

        /**
         * Получение последнего сообщения диалога (для превью в списке диалогов)
         *
         * @param int $dialogId Идентификатор диалога
         *
         * @return array|null
         */
        public function getLastMessage(int $dialogId): ?array
        {
            /** @var $DB DataBase Объект базы данных */
            $DB = DataBase::getInstance();
            $res = $DB->query(
                'SELECT * FROM ' . self::TABLE_MESSAGES . ' WHERE `dialog_id`=:dialogId ORDER BY id DESC LIMIT 1',
                ['dialogId' => $dialogId]
            );
            return $res ? $res[0] : null;
        }

    }
