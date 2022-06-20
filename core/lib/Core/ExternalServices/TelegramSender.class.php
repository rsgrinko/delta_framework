<?php
    /**
     * Класс для работы с API телеграмм (не-статический)
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
            $this->url = 'https://' . self::BASE_URL . '/bot' . $token . '/';
        }

        /**
         * Установка метода
         *
         * @param string $method Метод
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
            $res = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            return json_decode($res, true);
        }

        /**
         * Отправка изображения
         *
         * @param string $imagePath Путь до изображения
         *
         * @return array
         */
        public function sendPhoto(string $imagePath): array
        {
            $response = [
                'chat_id' => $this->chatId,
                'photo' => curl_file_create($imagePath),
            ];
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
                'chat_id' => $this->chatId,
                'document' => curl_file_create($filePath),
            ];
            return $this->setMethod('sendDocument')->sendRequest($response);
        }


        /**
         * Отправляет координаты с картой
         *
         * @param float $latitude Широта
         * @param float $longitude Долгота
         *
         * @return array
         */
        public function sendLocation(float $latitude, float $longitude): array
        {
            $response = [
                'chat_id' => $this->chatId,
                'latitude' => $latitude,
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
            $text = substr(trim($text), 0, 4096);
            $text = strip_tags($text, '<b><a><strong><i><em><u><ins><s><strike><del><s><code><pre>');
            $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');
            $params = [
                'chat_id' => $this->chatId,
                'parse_mode' => 'html',
                'text' => $text
            ];

            return $this->setMethod('sendMessage')->sendRequest($params);
        }
    }
