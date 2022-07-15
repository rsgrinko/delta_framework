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
     * Класс для работы с API телеграмм
     * Поддерживает:
     * - отправка сообщений с разметкой html
     * - редактирование отправленных сообщений
     * - работу с inline класиатурами (в сообщении)
     * - работу с быстрыми клавиатурами (меню бота)
     * - отправка сообщений с изображением с разметкой html
     * - отправка файлов
     * - получение информации о группе или канале
     * - получение файлов по идентификатору
     * - имитация действий (набирает сообщение, записывает голосовое и т.д.)
     * - отправка местоположений
     * - обработка callback запросов
     * - обработка inline событий
     *
     * @version 2.5.1
     * @author  Roman Grinko <rsgrinko@gmail.com>
     */

    namespace Core\ExternalServices;

    class Telegram
    {
        /**
         * @var string $token Токен бота
         */
        private static string $token;

        /**
         * @var string $url Адрес API запроса
         */
        private static string $url;

        const BASE_URL = 'api.telegram.org';

        /**
         * @var string $uploadDir Директория для загрузки и хранения файлов
         */
        private static string $uploadDir;

        /**
         * Инициализация
         *
         * @param string $token     Токен бота
         * @param string $uploadDir Директория для загрузки и хранения файлов
         */
        public static function init(string $token, string $uploadDir): void
        {
            self::$token     = $token;
            self::$url       = 'https://' . self::BASE_URL . '/bot' . $token . '/';
            self::$uploadDir = $uploadDir;
        }

        /**
         * Отправка запроса на API Telegram
         *
         * @param string $method   Используемый метод
         * @param array  $response Параметры
         *
         * @return array
         */
        private static function sendRequest(string $method, array $response): array
        {
            $ch = curl_init(self::$url . $method);
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
         * Получение информации о канале или группе
         *
         * @param string $channel Канал
         *
         * @return array
         */
        public static function getChat(string $channel): array
        {
            $channel  = str_replace('https://t.me/', '', str_replace('@', '', $channel));
            $response = [
                'chat_id' => '@' . $channel,
            ];
            return self::sendRequest('getChat', $response);
        }

        /**
         * Отправка изображения
         *
         * @param int    $chatId    Идентификатор чата
         * @param string $imagePath Путь до изображения
         *
         * @return array
         */
        public static function sendPhoto(int $chatId, string $imagePath): array
        {
            $response = [
                'chat_id' => $chatId,
                'photo'   => curl_file_create($imagePath),
            ];
            return self::sendRequest('sendPhoto', $response);
        }

        /**
         * Отправка файла
         *
         * @param int    $chatId   Идентификатор чата
         * @param string $filePath Путь до файла
         *
         * @return array
         */
        public static function sendDocument(int $chatId, string $filePath): array
        {
            $response = [
                'chat_id'  => $chatId,
                'document' => curl_file_create($filePath),
            ];
            return self::sendRequest('sendDocument', $response);
        }

        /**
         * Получить присланный файл
         *
         * @param string $fileId Идентификатор файла
         *
         * @return string|null
         */
        public static function getFile(string $fileId): ?string
        {
            $response = [
                'file_id' => $fileId,
            ];
            $res      = self::sendRequest('getFile', $response);
            if ($res['ok']) {
                $src  = 'https://' . self::BASE_URL . '/file/bot' . self::$token . '/' . $res['result']['file_path'];
                $dest = self::$uploadDir . '/' . time() . '-' . basename($src);

                if (copy($src, $dest)) {
                    return $dest;
                } else {
                    return null;
                }
            }
        }

        /**
         * Изменяет уже отправленное сообщение на указанное
         *
         * @param int    $chatId
         * @param int    $messageId
         * @param string $text
         *
         * @return array
         */
        public static function updateMessage(int $chatId, int $messageId, string $text): array
        {
            $response = [
                'chat_id'    => $chatId,
                'message_id' => $messageId,
                'text'       => $text,
            ];
            return self::sendRequest('editMessageText', $response);
        }

        /**
         * Отправка в чат имитаций событий
         * <pre>
         * typing для набора текста
         * upload_photo для загрузки фото
         * record_video или upload_video для видео
         * record_voice или upload_voice для голосовых
         * upload_document здля загрузки документов
         * choose_sticker для выбора стикера
         * find_location для местоположения
         * record_video_note или upload_video_note для видеосообщений
         * </pre>
         *
         * @param int    $chatId ID чата
         * @param string $action Событие
         *
         * @return array
         */

        public static function sendChatAction(int $chatId, string $action = 'typing'): array
        {
            $response = [
                'chat_id' => $chatId,
                'action'  => $action,
            ];
            return self::sendRequest('sendChatAction', $response);
        }

        /**
         * Отправляет координаты с картой
         *
         * @param int   $chatId    ID чата
         * @param float $latitude  Широта
         * @param float $longitude Долгота
         *
         * @return array
         */
        public static function sendLocation(int $chatId, float $latitude, float $longitude): array
        {
            $response = [
                'chat_id'   => $chatId,
                'latitude'  => $latitude,
                'longitude' => $longitude,
            ];
            return self::sendRequest('sendLocation', $response);
        }

        /**
         * Метод для обработки событий callback'ов
         *
         * @param int|null    $callbackId Идентификатор callback
         * @param string|null $text       Текст
         * @param bool        $showAlert  Показывать ли уведомление
         * @param string|null $url        Адрес
         *
         * @return array
         */
        public static function answerCallbackQuery(?int $callbackId, ?string $text = null, bool $showAlert = false, ?string $url = null): array
        {
            $response = [
                'callback_query_id' => $callbackId,
                'text'              => $text,
                'show_alert'        => $showAlert,
                'url'               => $url,
            ];
            return self::sendRequest('answerCallbackQuery', $response);
        }

        /**
         * Метод для обработки событий Inline режима
         *
         * @param int|null $inline_query_id ID запроса
         * @param string   $results         Массив результатов запроса
         * @param int      $cache_time      Время кэширования
         * @param bool     $is_personal     Персонально для каждого пользователя
         *
         * @return array
         */
        public static function answerInlineQuery(?int $inline_query_id, string $results, int $cache_time = 300, bool $is_personal = true): array
        {
            $response = [
                'inline_query_id' => $inline_query_id,
                'results'         => $results,
                'cache_time'      => $cache_time,
                'is_personal'     => $is_personal,
            ];
            return self::sendRequest('answerInlineQuery', $response);
        }

        /**
         * Создание json с результатами для отдачи в inline
         * <pre>
         * $arData = [
         *              [
         *                'title' => 'Title1',
         *                'description' => 'description1',
         *                'message_text' => 'Message1',
         *                'thumb_url' => 'http://example.com/1.jpg'
         *              ],
         *           ]
         * </pre>
         *
         * @param string $inlineType Тип результата
         * @param array  $arData     Массив элементов+
         *
         * @return string
         */
        public static function createInlineQueryResult(string $inlineType, array $arData): string
        {
            $result = [];
            foreach ($arData as $key => $element) {
                $tmpResult = [
                    'type' => $inlineType,
                    'id'   => 'id_' . $key,
                ];
                $result[]  = array_merge($tmpResult, $element);
            }
            $result = json_encode($result);
            return $result;
        }

        /**
         * Отправка сообщение пользователю в телеграм
         *
         * @param int         $chatId          Идентификатор чата
         * @param string|null $text            Текст сообщения
         * @param string      $image           Путь к картинке
         * @param array       $inline_keyboard Массив встроенной клавиатуры
         * @param array       $keyboard        Массив клавиатуры
         * @param array       $keyboard_opt    Массив клавиатуры
         *
         * @return array
         */
        public static function execute(
            int $chatId,
            ?string $text,
            string $image = '',
            array $inline_keyboard = [],
            array $keyboard = [],
            array $keyboard_opt = []
        ): array {
            $params            = [];
            $params['chat_id'] = $chatId;
            $text              = substr(trim($text), 0, 4096);
            $text              = strip_tags($text, '<b><a><strong><i><em><u><ins><s><strike><del><s><code><pre>');
            $text              = mb_convert_encoding($text, 'UTF-8', 'UTF-8');

            if (!empty($image)) { //text + image
                $params['photo']   = curl_file_create($image);
                $params['caption'] = $text;
                $method            = 'sendPhoto';
            } else { //only text
                $params['parse_mode'] = 'html';
                $params['text']       = $text;
                $method               = 'sendMessage';
            }

            /* клавиатура */
            if (!empty($inline_keyboard)) { // инлайн клавиатура если есть
                $inlineKeyboardMarkup   = ['inline_keyboard' => $inline_keyboard];
                $params['reply_markup'] = json_encode($inlineKeyboardMarkup);
            } else { // ставим обычную клавиатуру если есть
                if (empty($keyboard_opt)) {
                    $keyboard_opt[0] = 'keyboard';
                    $keyboard_opt[1] = false;
                    $keyboard_opt[2] = true;
                }
                $options      = [
                    $keyboard_opt[0]    => $keyboard,
                    'one_time_keyboard' => $keyboard_opt[1],
                    'resize_keyboard'   => $keyboard_opt[2],
                ];
                $replyMarkups = json_encode($options);

                $removeMarkups = json_encode(['remove_keyboard' => true]);
                if ($keyboard == [0]) {
                    $params['reply_markup'] = $removeMarkups;
                } else {
                    $params['reply_markup'] = $replyMarkups;
                }
            }

            return self::sendRequest($method, $params);
        }
    }
