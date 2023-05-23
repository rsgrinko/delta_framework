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

    /**
     * Класс для кэширования данных и работы с кэшэм
     */
    class Cache
    {
        /** @var string[] AVAILABLE_DRIVERS Поддерживаемые драйверы */
        public const AVAILABLE_DRIVERS = [
            'FileCacheDriver',
            'MemCacheDriver',
        ];

        /** @var string DEFAULT_DRIVER Драйвер по умолчанию */
        public const DEFAULT_DRIVER = 'FileCacheDriver';

        /** @var array|null $configParams Параметры */
        private static ?array $configParams = null;

        /** @var self|null $this|null $currentObject Объект текущего класса */
        private static ?self $currentObject = null;

        /** @var string|null $driver Драйвер */
        private ?string $driver = null;

        /**
         * Инициализация кеша
         *
         * @param array $configParams Параметры
         *
         * @return void
         */
        public static function init(array $configParams): void
        {
            self::$configParams = $configParams;
        }
        public static function getInstance(): self
        {
            if (self::$currentObject === null) {
                self::$currentObject = new static(self::$configParams);
            }

            return self::$currentObject;
        }

        /**
         * Конструктор
         *
         * @param array|null $configParams Параметры
         */
        private function __construct(?array $configParams)
        {

        }

    }
