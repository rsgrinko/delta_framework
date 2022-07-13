<?php

    namespace Core\Helpers;

    class Crypto
    {
        /**
         * @var string|null $key Ключ шифрования
         */
        private $key = null;

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
            $ivlen          = openssl_cipher_iv_length($this->method);
            $iv             = openssl_random_pseudo_bytes($ivlen);
            $ciphertext_raw = openssl_encrypt($string, $this->method, $this->key, OPENSSL_RAW_DATA, $iv);
            $hmac           = hash_hmac($this->algorithm, $ciphertext_raw, $this->key, true);
            $ciphertext     = base64_encode($iv . $hmac . $ciphertext_raw);
            return $ciphertext;
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
            $c              = base64_decode($string);
            $ivlen          = openssl_cipher_iv_length($this->method);
            $iv             = substr($c, 0, $ivlen);
            $hmac           = substr($c, $ivlen, $sha2len = 32);
            $ciphertext_raw = substr($c, $ivlen + $sha2len);
            $plaintext      = openssl_decrypt($ciphertext_raw, $this->method, $this->key, OPENSSL_RAW_DATA, $iv);
            $calcmac        = hash_hmac($this->algorithm, $ciphertext_raw, $this->key, true);
            if (hash_equals($hmac, $calcmac)) {
                return $plaintext;
            }
            return null;
        }

    }