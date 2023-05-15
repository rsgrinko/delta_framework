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

    namespace Core\Api;

    use Core\CoreException;
    use Throwable;

    /**
     * Класс представления API
     */
    class ApiView
    {

        /**
         * Подготовка результата
         *
         * @param mixed $data Данные
         *
         * @return mixed
         */
        private static function prepare($data)
        {
            if (is_string($data) && $data === strip_tags($data)) {
                $data = htmlentities($data, ENT_QUOTES);
            }
            if (is_int($data)) {
                $data = (int)$data;
            }
            return $data;
        }

        /**
         * Вывод результата в API
         *
         * @param mixed $data Данные
         *
         * @return void
         * @throws \JsonException
         */
        public static function output($data): void
        {
            header('Content-Type: application/json');
            header('Access-Control-Allow-Origin: *');
            header('Access-Control-Allow-Credentials: true');
            header('Access-Control-Allow-Methods: *');
            header('Access-Control-Allow-Headers: *');
            echo json_encode(
                [
                    'success' => true,
                    'time'    => time(),
                    'data'    => self::prepare($data),
                ],
                JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            );
        }

        /**
         * Вывод ошибки в API
         * @param Throwable $exception Исключение
         *
         * @return void
         * @throws \JsonException
         */
        public static function outputError(Throwable $exception): void
        {
            header('Content-Type: application/json');
            header('Access-Control-Allow-Origin: *');
            header('Access-Control-Allow-Credentials: true');
            header('Access-Control-Allow-Methods: *');
            header('Access-Control-Allow-Headers: *');
            echo json_encode(
                [
                    'success' => false,
                    'time'    => time(),
                    'error'   => [
                        'code'    => $exception->getCode(),
                        'message' => $exception->getMessage(),
                    ],
                ],
                JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            );
        }
    }

