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

    namespace Core\Helpers\Cache;

    use Core\Helpers\Кэшированные;

    /**
     * Класс для кэширования данных и работы с кэшэм
     */
    class Cache
    {
        /**
         * @var string $cacheDir Директория хранения файлов кэша
         */
        private static string $cacheDir;

        /**
         * @var int $quantity Количество обращений к кэшу
         */
        public static int $quantity = 0;

        /**
         * @var int $quantityRead Количество обращений к кэшу на чтение
         */
        public static int $quantityRead = 0;

        /**
         * @var int $quantityWrite Количество обращений к кэшу на запись
         */
        public static int $quantityWrite = 0;

        /**
         * @var bool $cacheEnabled Включает и выключает работу кэша
         */
        private static bool $cacheEnabled = true;

        /**
         * Инициализация кэша
         *
         * @param string $dir     Директория хранения файлов кэша
         * @param bool   $enabled Флаг включения кэширования
         */
        public static function init(string $dir, $enabled = true): void
        {
            self::$cacheDir     = $dir . '/';
            self::$cacheEnabled = $enabled;
        }

        /**
         * Получить количество обращений к кэшу
         *
         * @return int
         */
        public function getCountAll(): int
        {
            return self::$quantity;
        }

        /**
         * Получить количество чтений из кэша
         *
         * @return int
         */
        public function getCountRead(): int
        {
            return self::$quantityRead;
        }

        /**
         * Получить количество записи в кэш
         *
         * @return int
         */
        public function getCountWrite(): int
        {
            return self::$quantityWrite;
        }

        /**
         * Получение информации о кэше
         *
         * @return array
         */
        public static function getCacheInfo(): array
        {
            return [
                'countAll'    => self::$quantity,
                'countWrite'  => self::$quantityWrite,
                'countRead'   => self::$quantityRead,
                'cacheDir'    => self::$cacheDir,
                'cachedCount' => self::getCachedCount(),
                'size'        => self::getCacheSize(),
            ];
        }

        /**
         * Проверка наличия элемента в кэше
         *
         * @param string $name Имя элемента кэша
         *
         * @return bool Флаг наличия или отсутствия кэша
         */
        public static function check(string $name): bool
        {
            if (!self::$cacheEnabled
                || self::getAge($name) > CACHE_TTL
                || !file_exists(self::$cacheDir . md5($name) . '.tmp')) {
                return false;
            }

            return true;
        }

        /**
         * Получение кэшированных данных из кэша
         *
         * @param string $name Имя элемента кэша
         *
         * @return Кэшированные данные
         */
        public static function get(string $name)
        {
            self::$quantity++;
            self::$quantityRead++;
            return unserialize(base64_decode(file_get_contents(self::$cacheDir . md5($name) . '.tmp')));
        }

        /**
         * Получить количество кэшированных элементов
         *
         * @return int
         */
        public static function getCachedCount()
        {
            return count(scandir(self::$cacheDir)) - 2;
        }

        /**
         * Запись значения в кэш
         *
         * @param string            $name    Имя элемента кэша
         * @param string|array|null $arValue Значение элемента кэша
         *
         * @return bool Флаг успешной или неудачной записи данных
         */
        public static function set(string $name, $arValue): bool
        {
            if (!self::$cacheEnabled) {
                return false;
            }
            self::$quantity++;
            self::$quantityWrite++;
            if (file_put_contents(self::$cacheDir . md5($name) . '.tmp', base64_encode(serialize($arValue)))) {
                return true;
            }

            return false;
        }

        /**
         * Полная очистка кэша
         *
         * @return bool Флаг успеха
         */
        public static function flush(): bool
        {
            $di = new \RecursiveDirectoryIterator(self::$cacheDir, \FilesystemIterator::SKIP_DOTS);
            $ri = new \RecursiveIteratorIterator($di, \RecursiveIteratorIterator::CHILD_FIRST);
            foreach ($ri as $file) {
                $file->isDir() ? rmdir($file) : unlink($file);
            }
            return true;
        }

        /**
         * Удаление элемента из кэша
         *
         * @param string $name Имя элемента кэша
         *
         * @return bool Флаг успеха
         */
        public static function delete(string $name): bool
        {
            if (self::check($name)) {
                if (!unlink(self::$cacheDir . md5($name) . '.tmp')) {
                    self::$quantity++;
                    self::$quantityWrite++;
                    return false;
                }
            }
            return true;
        }

        /**
         * Получение размера элемента кэша в байтах
         *
         * @param string $name Имя элемента кэша
         *
         * @return int|null Размер элемента в байтах или null
         */
        public static function getSize(string $name): ?int
        {
            if (self::check($name)) {
                return filesize(self::$cacheDir . md5($name) . '.tmp');
            }
            return null;
        }

        /**
         * Получение общего размера кэша в байтах
         *
         * @return int Размер кэша в байтах или false
         */
        public static function getCacheSize(): int
        {
            $return_size = 0;
            foreach (scandir(self::$cacheDir) as $file) {
                if ($file === '.' || $file === '..') {
                    continue;
                }
                $return_size = $return_size + filesize(self::$cacheDir . $file);
            }
            return $return_size;
        }

        /**
         * Получение времени существованя кэша в секундах
         *
         * @param string $name Имя элемента кэша
         *
         * @return int Время в секундах или false
         */
        public static function getAge(string $name): int
        {
            return (time() - @filectime(self::$cacheDir . md5($name) . '.tmp'));
        }
    }
