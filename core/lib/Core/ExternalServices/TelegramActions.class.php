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

    /**
     * Класс-обработчик команд Telegram
     * Поддерживает:
     * - работа с простыми командами
     * - выделение аргументов команды
     * - определение типа события (сообщение, локация, фото, докемент...)
     * - работа с инлайн и callback запросами
     * - работа с инлайн клавиатурами (под сообщением)
     * - работа с клавиатурами (меню)
     * - обработка неизвестной команды
     *
     * @version 1.0.1
     * @author  Roman Grinko <rsgrinko@gmail.com>
     */

    namespace Core\ExternalServices;

    class TelegramActions
    {
        /** @var string Текущая команда */
        private static string $cmd;

        /** @var ?string Аргументы команды */
        private static ?string $params = null;

        /** @var array|null $data Массив входных данных */
        private static ?array $data = null;

        /** @var string|null $eventType Тип события */
        private static ?string $eventType = null;

        /** @var array|null $event Массив собранных данных по событию */
        private static ?array $event = null;

        /** @var int|null $chatId Идентификатор текущего чата */
        private static ?int $chatId = null;

        /**
         * @var string $messageType Тип текущего сообщения
         */
        private static string $messageType = 'message';

        /** @var array $inlineKeyboard Массив для инлайн клавиатуры */
        private static array $inlineKeyboard = [];

        /**
         * Инициализация
         *
         * @param array|null $data Массив входных данных
         */
        public static function init(?array $data): void
        {
            self::$data = $data;
            /** Режим работы - инлайн или прямой */
            $inlineMode = false;

            if (isset($data['edited_message'])) {
                self::$messageType = 'edited_message';
            } elseif (isset($data['inline_query'])) {
                self::$messageType = 'inline_query';
                $inlineMode        = true;
            } elseif (isset($data['channel_post'])) {
                self::$messageType = 'channel_post';
                $inlineMode        = true;
            } else {
                self::$messageType = 'message';
            }

            if ($inlineMode === true) // инлайн режим работы
            {
                self::$eventType           = 'inline_query';
                self::$event['id']         = $data[self::$messageType]['id'];
                self::$event['query']      = $data[self::$messageType]['query'];
                self::$event['offset']     = $data[self::$messageType]['offset'];
                self::$event['user_id']    = $data[self::$messageType]['from']['id'];                      // идентификатор пользователя
                self::$event['username']   = $data[self::$messageType]['from']['username'];                // username пользователя
                self::$event['first_name'] = $data[self::$messageType]['from']['first_name'];              // имя собеседника
                self::$event['last_name']  = $data[self::$messageType]['from']['last_name'];               // фамилию собеседника
            } else {                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                  // прямой режим работы
                self::$event['chat_id']    = self::$chatId = (int)$data[self::$messageType]['chat']['id'];                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                              // идентификатор чата
                self::$event['user_id']    = $data[self::$messageType]['from']['id'];                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                   // идентификатор пользователя
                self::$event['username']   = $data[self::$messageType]['from']['username'];               // username пользователя
                self::$event['first_name'] = $data[self::$messageType]['chat']['first_name'];           // имя собеседника
                self::$event['last_name']  = $data[self::$messageType]['chat']['last_name'];             // фамилию собеседника
                self::$event['chat_time']  = $data[self::$messageType]['date'];                          // дата сообщения

                if (!empty($data[self::$messageType]['sticker'])) {
                    self::$eventType        = 'sticker';
                    self::$event['file_id'] = $data['message']['sticker']['file_id'];
                    self::$event['emoji']   = $data['message']['sticker']['emoji'] ?: null;
                } elseif (!empty($data[self::$messageType]['contact'])) {
                    self::$eventType             = 'contact';
                    self::$event['phone_number'] = $data[self::$messageType]['contact']['phone_number'];
                    self::$event['name']         = $data[self::$messageType]['contact']['first_name'] . ' '
                                                   . $data['message']['contact']['last_name'];
                } elseif (!empty($data[self::$messageType]['location'])) {
                    self::$eventType          = 'location';
                    self::$event['latitude']  = $data[self::$messageType]['location']['latitude'];
                    self::$event['longitude'] = $data[self::$messageType]['location']['longitude'];
                } elseif (!empty($data[self::$messageType]['photo'])) {
                    self::$eventType        = 'photo';
                    $photo                  = array_pop($data[self::$messageType]['photo']);
                    self::$event['caption'] = $data[self::$messageType]['caption'];                   // Выделим подпись к изображению
                    self::$event['file_id'] = $photo['file_id'];                                      // id файла
                } elseif (!empty($data[self::$messageType]['voice'])) {
                    self::$eventType        = 'voice';
                    self::$event['file_id'] = $data[self::$messageType]['voice']['file_id'];                             // id файла
                } elseif (!empty($data[self::$messageType]['document'])) {
                    self::$eventType        = 'document';
                    self::$event['caption'] = $data[self::$messageType]['caption'];                   // Выделим подпись к документу
                    self::$event['file_id'] = $data[self::$messageType]['document']['file_id'];       // id файла
                } elseif (!empty($data) && isset($data[self::$messageType]['chat']['id']) && $data[self::$messageType]['chat']['id'] !== '') {
                    self::$eventType        = 'message';
                    self::$event['message'] = $data[self::$messageType]['text'];                      // Выделим сообщение собеседника (регистр по умолчанию)
                    self::$event['msg']     = mb_strtolower(
                        $data[self::$messageType]['text'],
                        'utf8'
                    );                                                                                // Выделим сообщение собеседника (нижний регистр)

                    if (substr(self::$event['msg'], 0, 1) === '/') {
                        self::$cmd = substr(self::$event['msg'], 1);
                    } else {
                        self::$cmd = self::$event['msg'];
                    }

                    // разбиваем запрос на непосредственно команду и ее аргументы
                    $arParams  = explode(' ', self::$cmd);
                    self::$cmd = $arParams[0]; // основная команда
                    unset($arParams[0]);
                    if (!empty($arParams)) {
                        self::$params = implode(' ', $arParams);
                    }
                } elseif (!empty($data) && isset($data['callback_query']['from']['id']) and $data['callback_query']['from']['id'] !== '') {
                    self::$eventType            = 'callback';
                    self::$event['callback_id'] = self::$chatId = (int)$data['callback_query']['id'];                    // идентификатор callback
                    self::$event['chat_id']     = self::$chatId = (int)$data['callback_query']['message']['chat']['id']; // идентификатор чата
                    self::$event['user_id']     = $data['callback_query']['from']['id'];                                 // идентификатор пользователя
                    self::$event['username']    = $data['callback_query']['from']['username'];                           // username пользователя
                    self::$event['first_name']  = $data['callback_query']['from']['first_name'];                         // имя собеседника
                    self::$event['last_name']   = $data['callback_query']['from']['last_name'];                          // фамилию собеседника
                    self::$event['chat_time']   = $data['callback_query']['message']['date'];                            // дата сообщения
                    self::$event['data']        = json_decode(
                        $data['callback_query']['data'],
                        true
                    );                                                                                                   // Содержимое callback запроса
                    self::$cmd                  = self::$event['data']['method'];
                } else {
                    self::$eventType = null;
                }
            }
        }

        /**
         * Получение типа текущего события
         *
         * @return string|null
         */
        public static function getEventType(): ?string
        {
            return self::$eventType;
        }

        /**
         * Получить собранные данные по текущему событию
         *
         * @return array|null
         */
        public static function getEventData(): ?array
        {
            return self::$event;
        }

        /**
         * Получить id текущего чата
         *
         * @return int|null
         */
        public static function getChatId(): ?int
        {
            return self::$chatId;
        }

        /**
         * Получить текущую команду
         *
         * @return string
         */
        private static function getCommand(): string
        {
            return self::$cmd;
        }

        /**
         * Получить агрументы запроса
         *
         * @return ?string
         */
        private static function getParams(): ?string
        {
            return self::$params;
        }

        /**
         * Магический метод для неизвестного запроса
         *
         * @param string $name      Имя метода
         * @param ?array $arguments Массив аргументов метода
         *
         * @return void
         */
        public static function __callStatic(string $name, ?array $arguments): void
        {
            /*$message = 'Команда ' . self::$cmd . ' не найдена.' . PHP_EOL;
            if (!empty($arguments)) {
                $message .= 'Аргументы: ' . implode(', ', $arguments) . PHP_EOL;
            }
            if (self::getEventType() !== 'callback') {
                $message .= '<code>' . print_r(self::$data, true) . '</code>';
            }*/
            //ChatGPT connect...
            $gptObject = new ChatGPT();
            Telegram::sendChatAction(self::getChatId()); // печатает...
            //Telegram::execute(self::getChatId(), $message, '', self::getInlineKeyboard(), self::getKeyboard());
            Telegram::execute(self::getChatId(), htmlspecialchars($gptObject->ask($name)), '', self::getInlineKeyboard(), self::getKeyboard());
        }

        /**
         * Непосредственное выполнение команды
         *
         * @return void
         */
        public static function execute(): void
        {
            self::setInlineKeyboard(null);

            $methodName = 'command' . self::$cmd;
            self::$methodName();
        }

        /**
         * Непосредственное выполнение callback
         *
         * @return void
         */
        public static function executeCallback(): void
        {
            self::setInlineKeyboard(null);
            $methodName = 'callback' . self::getCommand();
            self::$methodName();
        }

        /**
         * Проверка прав на доступ к боту
         *
         * @return bool
         */
        public static function isHasAccess(): bool
        {
            if ((int)self::$event['chat_id'] === TELEGRAM_ADMIN_CHAT_ID) {
                return true;
            } else {
                return false;
            }
        }

        /**
         * Получить клавиатуру
         *
         * @return array
         */
        public static function getKeyboard(): array
        {
            return [
                ['/online', '/yastat'],
                ['/fail2ban', '/stat'],
                ['/getcam', '/clearcache'],
                ['/deploy'],
            ];
        }

        /**
         * Установка инлайн клавиатуры
         *
         * @param array|null $arKeyboard Массив инлайн клавиатуры
         */
        private static function setInlineKeyboard(?array $arKeyboard): void
        {
            if (empty($arKeyboard)) {
                self::$inlineKeyboard = [];
            } else {
                self::$inlineKeyboard = $arKeyboard;
            }
        }

        /**
         * Получение инлайн клавиатуры
         *
         * @return array
         */
        public static function getInlineKeyboard(): array
        {
            return self::$inlineKeyboard;
            /*return [
                [
                    ['text' => 'Кнопка 1', 'callback_data' => '/online'],
                    ['text' => 'Кнопка 2', 'callback_data' => json_encode($arrParams, JSON_UNESCAPED_UNICODE)]
                ],
                [
                    ['text' => 'Кнопка 3', 'url' => 'https://it-stories.ru']
                ]
            ];*/
        }

        /**
         * Возвращает подготовленные данные для подстановки в callback data
         *
         * @param string $method Метод
         * @param array  $params Масссив параметров
         *
         * @return string
         */
        private static function getPreparedCallbackData(string $method, array $params = []): string
        {
            return json_encode(['method' => $method, 'params' => $params], JSON_UNESCAPED_UNICODE);
        }

        /**
         * Команда /start
         *
         * @return void
         */
        public static function commandStart(): void
        {
            /*self::setInlineKeyboard(
                [
                    [
                        ['text' => 'Руководство', 'callback_data' => self::getPreparedCallbackData('help')],
                        ['text' => 'Панель управления', 'url' => 'https://it-stories.ru/login/']
                    ],
                    [
                        ['text' => 'test', 'callback_data' => self::getPreparedCallbackData('test')]
                    ]
                ]);*/
            Telegram::sendChatAction(self::getChatId()); // печатает...
            Telegram::execute(self::getChatId(), 'Бот успешно запущен', '', self::getInlineKeyboard(), self::getKeyboard());
        }

        /**
         * Команда /stop
         *
         * @return void
         */
        public static function commandStop(): void
        {
            Telegram::sendChatAction(self::getChatId()); // печатает...
            Telegram::execute(self::getChatId(), 'Бот Остановлен', '', self::getInlineKeyboard(), self::getKeyboard());
        }

        /**
         * Команда /test
         *
         * @return void
         */
        public static function commandTest(): void
        {
            Telegram::sendChatAction(self::getChatId()); // печатает...
            Telegram::execute(
                self::getChatId(),
                'Запущена команда test, переданы аргумент: ' . self::getParams(),
                '',
                self::getInlineKeyboard(),
                self::getKeyboard()
            );
        }
    }