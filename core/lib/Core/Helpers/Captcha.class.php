<?php
    /**
     * Copyright (c) 2023 Roman Grinko <rsgrinko@gmail.com>
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

    namespace Core\Helpers;

    use mysql_xdevapi\Result;

    /**
     * Класс для работы с Captcha
     */
    class Captcha {
        /**
         * Генерация и отображение картинки-каптчи
         *
         * @param string|null $code Код
         *
         * @return void
         */
        public static function showCaptcha(?string $code = null): void
        {
            if ($code === null) {
                $code = SystemFunctions::generateCode(8, 8);
            }

            $_SESSION['captcha'] = [
                'captchaCode'   => $code,
                'verified'      => false,
                'generatedTime' => time(),
                'generatedDate' => date(DATETIME_FORMAT),
            ];

            $width  = 80;
            $height = 30;
            $img    = imagecreate($width, $height);

            // Задаем фон
            imagecolorallocate($img, 255, 255, 255);

            // Задаем цвета
            $lineColor = imagecolorallocate($img, 150, 150, 150);
            $pixelColor = imagecolorallocate($img, 150, 150, 150);
            $whiteColor = imagecolorallocate($img, 180, 255, 180);
            $blackColor = imagecolorallocate($img, 0, 0, 0);

            // Рисуем линии 1
            for ($i = 0; $i < 3; $i++) {
                $x1 = 0;
                $x2 = $width;
                $y1 = rand(0, $height);
                $y2 = rand(00, $height);
                imageline($img, $x1, $y1, $x2, $y2, $lineColor);
            }

            // Рисуем линии 2
            for ($i = 0; $i < 3; $i++) {
                $x1 = rand(0, $width);
                $x2 = rand(0, $width);
                $y1 = 0;
                $y2 = $height;
                imageline($img, $x1, $y1, $x2, $y2, $lineColor);
            }

            // Рисуем пиксели
            for ($i = 0; $i < 200; $i++) {
                imagesetpixel($img, rand() % $width, rand() % $height, $pixelColor);
            }

            // Рисуем тень для кода
            imagestring($img, 5, 4, 7, $code, $blackColor);
            imagestring($img, 5, 6, 7, $code, $blackColor);
            imagestring($img, 5, 5, 6, $code, $blackColor);
            imagestring($img, 5, 5, 8, $code, $blackColor);

            // Рисуем сам код
            imagestring($img, 5, 5, 7, $code, $whiteColor); //main

            header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
            header('Last-Modified: ' . gmdate('D, d M Y H:i:s', 10000) . ' GMT');
            header('Cache-Control: no-store, no-cache, must-revalidate');
            header('Cache-Control: post-check=0, pre-check=0', false);
            header('Pragma: no-cache');
            header('Content-Type: image/png');

            imagepng($img);
        }

        /**
         * Проверка кода каптчи
         *
         * @param int $code Код
         *
         * @return bool Результат валидации
         */
        public static function isValidCaptcha(string $code): bool
        {
            $result                              = (string)$_SESSION['captcha']['captchaCode'] === $code;
            $_SESSION['captcha']['verified']     = $result;
            $_SESSION['captcha']['verifiedTime'] = time();
            $_SESSION['captcha']['verifiedDate'] = date(DATETIME_FORMAT);

            return $result;
        }

        /**
         * Очистка данных каптчи
         *
         * @return void
         */
        public static function clearSession(): void
        {
            unset($_SESSION['captcha']);
        }
    }