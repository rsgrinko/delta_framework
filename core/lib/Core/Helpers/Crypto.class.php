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

    namespace Core\Helpers;

    class Crypto
    {
        /**
         * @var string|null $key Ключ шифрования
         */
        private $key = CRYPTO_KEY;

        /**
         * @var string $method Метод шифрования
         */
        private $method = 'AES-192-CBC';

        /**
         * @var string $algorithm Алгоритм
         */
        private $algorithm = 'sha256';


        /**
         * Конструктор
         */
        public function __construct()
        {
        }

        /**
         * Деструктор
         */
        public function __destruct()
        {
            unset($this->key);
            unset($this->method);
        }

        /**
         * Генерация ключа шифрования
         *
         * @return string Ключ
         */
        public static function generateKey(): string
        {
            return bin2hex(openssl_random_pseudo_bytes(40));
        }

        /**
         * @param string $key
         *
         * @return Crypto
         */
        public function setKey(string $key): self
        {
            $this->key = $key;
            return $this;
        }

        /**
         * Шифрование данных
         *
         * @param string $string Строка
         *
         * @return string
         */
        public function encode(string $string): string
        {
            $ivLength      = openssl_cipher_iv_length($this->method);
            $iv            = openssl_random_pseudo_bytes($ivLength);
            $cipherTextRaw = openssl_encrypt($string, $this->method, $this->key, OPENSSL_RAW_DATA, $iv);
            $hmac          = hash_hmac($this->algorithm, $cipherTextRaw, $this->key, true);
            $cipherText    = base64_encode($iv . $hmac . $cipherTextRaw);
            return $cipherText;
        }

        /**
         * Расшифровка данных
         *
         * @param string $string Зашифрованная строка
         *
         * @return string|null
         */
        public function decode(string $string): ?string
        {
            $c             = base64_decode($string);
            $ivLength      = openssl_cipher_iv_length($this->method);
            $iv            = substr($c, 0, $ivLength);
            $hmac          = substr($c, $ivLength, $sha2len = 32);
            $cipherTextRaw = substr($c, $ivLength + $sha2len);
            $result        = openssl_decrypt($cipherTextRaw, $this->method, $this->key, OPENSSL_RAW_DATA, $iv);
            $calcMac       = hash_hmac($this->algorithm, $cipherTextRaw, $this->key, true);
            if (hash_equals($hmac, $calcMac)) {
                return $result;
            }
            return null;
        }

    }