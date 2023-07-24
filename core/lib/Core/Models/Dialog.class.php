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
        private static function getDialogId(int $userOne, int $userTwo): ?int
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
        private static function markDialogViewed(int $dialogId): void
        {
            $DB       = DB::getInstance();
            $dialogId = $DB->update(self::TABLE_DIALOGS, ['id' => $dialogId], ['viewed' => CODE_VALUE_Y]);
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
                'SELECT * FROM ' . self::TABLE_DIALOGS . ' WHERE `send`="' . $this->user->getId() . '" OR `receive`="' . $this->user->getId() . '"'
            );
            if (empty($dialogs)) {
                return [];
            }
            return $dialogs;
        }

        /**
         * Получение сообщений диалога
         *
         * @param int $dialogId
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
                self::markDialogViewed($dialogId);
            }
            if (empty($messages)) {
                return [];
            }
            return $messages;
        }

        /**
         * Отправка сообщения
         *
         * @throws CoreException
         */
        public function sendMessage(int $to, string $message)
        {
            if (!User::isUserExistsByParams(['id' => $to])) {
                throw new CoreException('Пользователь с идентификатором ' . $to . ' отсутствует в базе', CoreException::ERROR_USER_NOT_FOUND);
            }
            /** @var $DB DB Объект базы данных */
            $DB = DB::getInstance();
            $dialogId = self::getDialogId($this->user->getId(), $to);
            if (empty($dialogId)) {
                $dialogId = $DB->addItem(self::TABLE_DIALOGS, ['viewed'=> CODE_VALUE_N, 'send' => $this->user->getId(), 'receive' => $to]);
            } else {
                $DB->update(self::TABLE_DIALOGS, ['id' => $dialogId], ['viewed'=> CODE_VALUE_N, 'send' => $this->user->getId(), 'receive' => $to]);
            }

            $DB->addItem(self::TABLE_MESSAGES, ['dialog_id' => $dialogId, 'user_from' => $this->user->getId(), 'user_to' => $to, 'text' => $message]);

        }

    }
