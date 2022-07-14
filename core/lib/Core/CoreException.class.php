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

    class CoreException extends \Exception
    {
        /**
         * Некорректный идентификатор пользователя
         */
        const ERROR_INCORRECT_USER_ID = 100;

        /**
         * Пользователь не найден
         */
        const ERROR_USER_NOT_FOUND = 101;

        /**
         * Ошибка создания пользователя
         */
        const ERROR_CREATE_USER = 200;

        /**
         * Ошибка проверки класса или метода на существование
         */
        const ERROR_CLASS_OR_METHOD_NOT_FOUND = 300;

        /**
         * Ошибка проверки класса или метода на существование
         */
        const ERROR_DIPLICATE_TASK = 400;

        /**
         * Ошибка файл не найден
         */
        const ERROR_FILE_NOT_FOUND = 500;

        /**
         * Ошибка не удалось скопировать файл
         */
        const ERROR_FILE_COPY = 501;

        /**
         * Ошибка SQL запроса
         */
        const ERROR_SQL_QUERY = 600;

        /**
         * Возвращает обработанный callTrace текущего исключения
         *
         * @return array
         */
        public function generateCallTrace(): array
        {
            $trace = explode(PHP_EOL, $this->getTraceAsString());
            $trace = array_reverse($trace);
            array_shift($trace);
            $length = count($trace);
            $result = [];
            for ($i = 0; $i < $length; $i++) {
                $result[] = ($i + 1) . ')' . substr($trace[$i], strpos($trace[$i], ' '));
            }
            return $result;
        }

        /**
         * Отображение исключения пользователю
         *
         * @return void
         */
        public function showTrace(): void
        {
            echo '<div style="color: #b30000;background: #ffe2e2;padding: 10px;border: 1px solid #ffa0a0;margin: 10px;display: inline-block;">';
            echo '<span style="font-weight:bold; font-size: 1.1em;">' . $this->getMessage() . '</span><br>';
            echo $this->getFile() . ': ' . $this->getLine() . '<br>';
            $arTrace = $this->generateCallTrace();
            foreach ($arTrace as $key => $value) {
                echo '<span>' . $value . '</span><br>';
            }
            echo '</div>';
        }
    }
