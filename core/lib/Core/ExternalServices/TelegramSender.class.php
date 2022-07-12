<?php
    /**
     * Класс для работы с API телеграмм (не-статический)
     *
     * @version 2.6.1
     * @author  Roman Grinko <rsgrinko@gmail.com>
     * @package ITS
     */

    namespace Core\ExternalServices;

    class TelegramSender
    {
        /**
         * @var string $token Токен бота
         */
        private string $token;

        /**
         * @var string $url Адрес API запроса
         */
        private string $url;

        /**
         * @var string $method Метод
         */
        private string $method = '';

        /**
         * @var int $chatId Идентификатор чата
         */
        private int $chatId = 0;

        const BASE_URL = 'api.telegram.org';

        /**
         * Инициализация
         *
         * @param string $token Токен бота
         */
        public function __construct(string $token = TELEGRAM_BOT_TOKEN)
        {
            $this->token = $token;
            $this->url   = 'https://' . self::BASE_URL . '/bot' . $token . '/';
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
            $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');

            return $text;
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
         * @param array $response Параметры
         *
         * @return array
         */
        private function sendRequest(array $response): array
        {
            $ch = curl_init($this->url . $this->method);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $response);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, false);
            $res      = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            return json_decode($res, true);
        }

        /**
         * Отправка изображения
         *
         * @param string      $imagePath Путь до изображения
         * @param string|null $caption   Подпись
         *
         * @return array
         */
        public function sendPhoto(string $imagePath, ?string $caption = null): array
        {
            $response = [
                'chat_id'    => $this->chatId,
                'photo'      => curl_file_create($imagePath),
                'parse_mode' => 'html',
            ];
            if (!empty($caption)) {
                $response['caption'] = $this->sanitize($caption, true);
            }
            return $this->setMethod('sendPhoto')->sendRequest($response);
        }

        /**
         * Отправка файла
         *
         * @param string $filePath Путь до файла
         *
         * @return array
         */
        public function sendDocument(string $filePath): array
        {
            $response = [
                'chat_id'  => $this->chatId,
                'document' => curl_file_create($filePath),
            ];
            return $this->setMethod('sendDocument')->sendRequest($response);
        }


        /**
         * Отправляет координаты с картой
         *
         * @param float $latitude  Широта
         * @param float $longitude Долгота
         *
         * @return array
         */
        public function sendLocation(float $latitude, float $longitude): array
        {
            $response = [
                'chat_id'   => $this->chatId,
                'latitude'  => $latitude,
                'longitude' => $longitude,
            ];
            return $this->setMethod('sendLocation')->sendRequest($response);
        }


        /**
         * Отправка сообщение пользователю в телеграм
         *
         * @param string|null $text Текст сообщения
         *
         * @return array
         */
        public function sendMessage(?string $text): array
        {
            $params = [
                'chat_id'    => $this->chatId,
                'parse_mode' => 'html',
                'text'       => $this->sanitize($text),
            ];

            return $this->setMethod('sendMessage')->sendRequest($params);
        }
    }
