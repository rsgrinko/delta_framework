<?php

    namespace Core\ExternalServices;

    use Core\CoreException;

    class Telegram2
    {
        /** @var int|null $chatId Идентификатор чата для запросов */
        private ?int $chatId = null;

        /** @var string|null $method Метод */
        private ?string $method = null;

        /** @var string|null $url Путь запросов */
        private ?string $url = null;

        /** Базовый адрес для запросов */
        const BASE_URL = 'api.telegram.org';

        /** @var string Текущая команда */
        private string $cmd;

        /** @var ?string Аргументы команды */
        private ?string $params = null;

        /** @var array|null $data Массив входных данных */
        private ?array $data = null;

        /** @var string|null $eventType Тип события */
        private ?string $eventType = null;

        /** @var array|null $event Массив собранных данных по событию */
        private ?array $event = null;

        /** @var int|null $eventChatId Идентификатор текущего чата */
        private ?int $eventChatId = null;

        /** @var string $messageType Тип текущего сообщения */
        private string $messageType = 'message';

        /** @var array $inlineKeyboard Массив для инлайн клавиатуры */
        private array $inlineKeyboard = [];

        /**
         * Инициализация
         *
         * @param string $token Токен бота
         */
        public function __construct(string $token = TELEGRAM_BOT_TOKEN)
        {
            $this->url = 'https://' . self::BASE_URL . '/bot' . $token . '/';
            return $this;
        }

        /**
         * Получение информации о канале или группе
         *
         * @param string $channel Канал
         *
         * @return array
         */
        public function getChat(string $channel): array
        {
            $channel = str_replace('https://t.me/', '', str_replace('@', '', $channel));
            $request = [
                'chat_id' => '@' . $channel,
            ];
            return $this->setMethod('getChat')->sendRequest($request);
        }

        /**
         * Установка метода
         *
         * @param string $method Метод
         *
         * @return $this
         */
        private function setMethod(string $method): self
        {
            $this->method = $method;
            return $this;
        }

        /**
         * Установка идентификатора чата
         *
         * @param int $chatId Метод
         *
         * @return $this
         */
        public function setChat(int $chatId): self
        {
            $this->chatId = $chatId;
            return $this;
        }

        /**
         * Отправка запроса на API Telegram
         *
         * @param array $request Параметры
         *
         * @return array
         * @throws CoreException
         */
        private function sendRequest(array $request): array
        {
            $ch = curl_init($this->url . $this->method);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $request);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, false);
            $res      = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            $res = json_decode($res, true);
            if (empty($res) || $res['ok'] !== true) {
                throw new CoreException($res['description'], $res['error_code']);
            }
            return $res;
        }

        /**
         * Санитизация текста
         *
         * @param string|null $text      Текст
         * @param bool        $isCaption Является ли текст описанием изображения
         *
         * @return string|null
         */
        private function sanitize(?string $text, bool $isCaption = false): ?string
        {
            if (empty($text)) {
                return null;
            }

            $text = strip_tags($text, '<b><a><strong><i><em><u><ins><s><strike><del><s><code><pre>');
            $text = substr(trim($text), 0, $isCaption ? 1024 : 4096);
            //$text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');

            return $text;
        }

        /**
         * Изменяет уже отправленное сообщение на указанное
         *
         * @param int    $messageId
         * @param string $text
         *
         * @return array
         * @throws CoreException
         */
        public function updateMessage(int $messageId, string $text): array
        {
            $request = [
                'chat_id'    => $this->chatId,
                'message_id' => $messageId,
                'text'       => $text,
            ];
            return $this->setMethod('editMessageText')->sendRequest($request);
        }

        /**
         * Отправка изображения
         *
         * @param string      $imagePath Путь до изображения
         * @param string|null $caption   Подпись
         *
         * @return array
         * @throws CoreException
         */
        public function sendPhoto(string $imagePath, ?string $caption = null): array
        {
            $request = [
                'chat_id'    => $this->chatId,
                'photo'      => curl_file_create($imagePath),
                'parse_mode' => 'html',
            ];
            if (!empty($caption)) {
                $request['caption'] = $this->sanitize($caption, true);
            }
            return $this->setMethod('sendPhoto')->sendRequest($request);
        }

        /**
         * Отправка файла
         *
         * @param string $filePath Путь до файла
         *
         * @return array
         * @throws CoreException
         */
        public function sendDocument(string $filePath): array
        {
            $request = [
                'chat_id'  => $this->chatId,
                'document' => curl_file_create($filePath),
            ];
            return $this->setMethod('sendDocument')->sendRequest($request);
        }


        /**
         * Отправляет координаты с картой
         *
         * @param float $latitude  Широта
         * @param float $longitude Долгота
         *
         * @return array
         * @throws CoreException
         */
        public function sendLocation(float $latitude, float $longitude): array
        {
            $request = [
                'chat_id'   => $this->chatId,
                'latitude'  => $latitude,
                'longitude' => $longitude,
            ];
            return $this->setMethod('sendLocation')->sendRequest($request);
        }


        /**
         * Отправка сообщение пользователю в телеграм
         *
         * @param string|null $text Текст сообщения
         *
         * @return array
         * @throws CoreException
         */
        public function sendMessage(?string $text): array
        {
            $request = [
                'chat_id'    => $this->chatId,
                'parse_mode' => 'html',
                'text'       => $this->sanitize($text),
            ];

            return $this->setMethod('sendMessage')->sendRequest($request);
        }

        /**
         * Установка параметров клиентского запроса
         *
         * @param array|null $data Массив входных данных
         */
        public function setRemoteRequest(?array $data): self
        {
            $this->data = $data;
            /** Режим работы - инлайн или прямой */
            $inlineMode = false;


            if (isset($data['edited_message'])) {
                $this->messageType = 'edited_message';
            } elseif (isset($data['inline_query'])) {
                $this->messageType = 'inline_query';
                $inlineMode        = true;
            } elseif (isset($data['channel_post'])) {
                $this->messageType = 'channel_post';
                $inlineMode        = true;
            } else {
                $this->messageType = 'message';
            }


            if ($inlineMode === true) // инлайн режим работы
            {
                $this->eventType           = 'inline_query';
                $this->event['id']         = $data[$this->messageType]['id'];
                $this->event['query']      = $data[$this->messageType]['query'];
                $this->event['offset']     = $data[$this->messageType]['offset'];
                $this->event['user_id']    = $data[$this->messageType]['from']['id'];                   // идентификатор пользователя
                $this->event['username']   = $data[$this->messageType]['from']['username'];             // username пользователя
                $this->event['first_name'] = $data[$this->messageType]['from']['first_name'];           // имя собеседника
                $this->event['last_name']  = $data[$this->messageType]['from']['last_name'];            // фамилию собеседника
            } else {                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                              // прямой режим работы
                $this->event['chat_id']    = $this->eventChatId = (int)$data[$this->messageType]['chat']['id'];                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                  // идентификатор чата
                $this->event['user_id'] = $data[$this->messageType]['from']['id'];                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            // идентификатор пользователя
                $this->event['username'] = $data[$this->messageType]['from']['username'];                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                     // username пользователя
                $this->event['first_name'] = $data[$this->messageType]['chat']['first_name'];                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                 // имя собеседника
                $this->event['last_name']  = $data[$this->messageType]['chat']['last_name'];                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                  // фамилию собеседника
                $this->event['chat_time']  = $data[$this->messageType]['date'];                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                               // дата сообщения

                if (!empty($data[$this->messageType]['sticker'])) {
                    $this->eventType        = 'sticker';
                    $this->event['file_id'] = $data['message']['sticker']['file_id'];
                    $this->event['emoji']   = $data['message']['sticker']['emoji'] ?: null;
                } elseif (!empty($data[$this->messageType]['contact'])) {
                    $this->eventType             = 'contact';
                    $this->event['phone_number'] = $data[$this->messageType]['contact']['phone_number'];
                    $this->event['name']         = $data[$this->messageType]['contact']['first_name'] . ' '
                                                   . $data['message']['contact']['last_name'];
                } elseif (!empty($data[$this->messageType]['location'])) {
                    $this->eventType          = 'location';
                    $this->event['latitude'] = $data[$this->messageType]['location']['latitude'];
                    $this->event['longitude'] = $data[$this->messageType]['location']['longitude'];
                } elseif (!empty($data[$this->messageType]['photo'])) {
                    $this->eventType        = 'photo';
                    $photo           = array_pop($data[$this->messageType]['photo']);
                    $this->event['caption'] = $data[$this->messageType]['caption'];                   // Выделим подпись к изображению
                    $this->event['file_id'] = $photo['file_id'];                                      // id файла
                } elseif (!empty($data[$this->messageType]['voice'])) {
                    $this->eventType        = 'voice';
                    $this->event['file_id'] = $data[$this->messageType]['voice']['file_id'];         // id файла
                } elseif (!empty($data[$this->messageType]['document'])) {
                    $this->eventType        = 'document';
                    $this->event['caption'] = $data[$this->messageType]['caption'];                   // Выделим подпись к документу
                    $this->event['file_id'] = $data[$this->messageType]['document']['file_id'];       // id файла
                } elseif (!empty($data) && isset($data[$this->messageType]['chat']['id']) && $data[$this->messageType]['chat']['id'] !== '') {
                    $this->eventType        = 'message';
                    $this->event['message'] = $data[$this->messageType]['text'];                      // Выделим сообщение собеседника (регистр по умолчанию)
                    $this->event['msg']     = mb_strtolower(
                        $data[$this->messageType]['text'],
                        'utf8'
                    );                                                                                // Выделим сообщение собеседника (нижний регистр)

                    if (substr($this->event['msg'], 0, 1) === '/') {
                        $this->cmd = substr($this->event['msg'], 1);
                    } else {
                        $this->cmd = $this->event['msg'];
                    }

                    // разбиваем запрос на непосредственно команду и ее аргументы
                    $arParams  = explode(' ', $this->cmd);
                    $this->cmd = $arParams[0]; // основная команда
                    unset($arParams[0]);
                    if (!empty($arParams)) {
                        $this->params = implode(' ', $arParams);
                    }
                } elseif (!empty($data) && isset($data['callback_query']['from']['id']) and $data['callback_query']['from']['id'] !== '') {
                    $this->eventType            = 'callback';
                    $this->event['callback_id'] = $this->eventChatId = (int)$data['callback_query']['id'];                    // идентификатор callback
                    $this->event['chat_id']     = $this->eventChatId = (int)$data['callback_query']['message']['chat']['id']; // идентификатор чата
                    $this->event['user_id']     = $data['callback_query']['from']['id'];                                      // идентификатор пользователя
                    $this->event['username']    = $data['callback_query']['from']['username'];                                // username пользователя
                    $this->event['first_name']  = $data['callback_query']['from']['first_name'];                              // имя собеседника
                    $this->event['last_name']   = $data['callback_query']['from']['last_name'];                               // фамилию собеседника
                    $this->event['chat_time']   = $data['callback_query']['message']['date'];                                 // дата сообщения
                    $this->event['data']        = json_decode(
                        $data['callback_query']['data'],
                        true
                    );                                                                                                   // Содержимое callback запроса
                    $this->cmd                  = $this->event['data']['method'];
                } else {
                    $this->eventType = null;
                }
                $this->setChat($this->eventChatId);
            }
            return $this;
        }

        /**
         * Получение общего типа текущего события
         *
         * @return string|null
         */
        public function getEventType(): ?string
        {
            return $this->eventType;
        }



        /**
         * Получение конкретного типа
         *
         * @return string|null
         */
        public function getMessageType(): ?string
        {
            return $this->messageType;
        }

        /**
         * Получение сообщения, полученного от пользователя
         *
         * @return string|null
         */
        public function getMessage(): ?string
        {
            return $this->event['message'];
        }

        /**
         * Получить собранные данные по текущему событию
         *
         * @return array|null
         */
        public function getEventData(): ?array
        {
            return $this->event;
        }

        /**
         * Получить id чата, породившего событие
         *
         * @return int|null
         */
        public function getEventChatId(): ?int
        {
            return $this->eventChatId;
        }

        /**
         * Получить текущую команду
         *
         * @return string
         */
        public function getCommand(): string
        {
            return $this->cmd;
        }

        /**
         * Получить агрументы запроса
         *
         * @return ?string
         */
        public function getParams(): ?string
        {
            return $this->params;
        }
    }