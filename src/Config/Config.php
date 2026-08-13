<?php

    declare(strict_types=1);

    namespace Delta\Config;

    use RuntimeException;

    /**
     * Репозиторий конфигурации приложения.
     *
     * Значения читаются из PHP-файлов каталога config/: имя файла становится корневым
     * ключом, а доступ идёт по точечной нотации — get('database.password').
     *
     * В отличие от глобальных констант, объект можно подменить в тестах через setInstance(),
     * а значения — переопределить через set() без правки исходников.
     */
    final class Config
    {
        /** @var self|null Текущий экземпляр */
        private static ?self $instance = null;

        /** @var array Значения конфигурации */
        private array $items = [];

        /**
         * Конструктор
         *
         * @param array $items Значения конфигурации
         */
        public function __construct(array $items = [])
        {
            $this->items = $items;
        }

        /**
         * Собрать конфигурацию из каталога и сделать её текущей
         *
         * @param string $configDir Путь к каталогу с файлами конфигурации
         *
         * @return self Экземпляр конфигурации
         */
        public static function boot(string $configDir): self
        {
            if (is_dir($configDir) === false) {
                throw new RuntimeException('Каталог конфигурации не найден: ' . $configDir);
            }

            $items = [];
            foreach (glob(rtrim($configDir, '/\\') . '/*.php') ?: [] as $file) {
                $items[basename($file, '.php')] = require $file;
            }

            return self::$instance = new self($items);
        }

        /**
         * Получить текущий экземпляр
         *
         * @return self Экземпляр конфигурации
         */
        public static function getInstance(): self
        {
            if (self::$instance === null) {
                throw new RuntimeException('Конфигурация не инициализирована: вызовите Config::boot() до первого обращения.');
            }

            return self::$instance;
        }

        /**
         * Подменить текущий экземпляр (нужно для тестов)
         *
         * @param self|null $config Экземпляр конфигурации
         *
         * @return void
         */
        public static function setInstance(?self $config): void
        {
            self::$instance = $config;
        }

        /**
         * Получить значение по точечному ключу
         *
         * @param string $key     Ключ вида 'database.host'
         * @param mixed  $default Значение по умолчанию
         *
         * @return mixed Значение
         */
        public function get(string $key, mixed $default = null): mixed
        {
            $value = $this->items;

            foreach (explode('.', $key) as $segment) {
                if (is_array($value) === false || array_key_exists($segment, $value) === false) {
                    return $default;
                }
                $value = $value[$segment];
            }

            return $value;
        }

        /**
         * Проверить наличие ключа
         *
         * @param string $key Ключ вида 'database.host'
         *
         * @return bool Признак наличия
         */
        public function has(string $key): bool
        {
            $marker = "\0delta-missing\0";

            return $this->get($key, $marker) !== $marker;
        }

        /**
         * Установить значение по точечному ключу
         *
         * @param string $key   Ключ вида 'database.host'
         * @param mixed  $value Значение
         *
         * @return void
         */
        public function set(string $key, mixed $value): void
        {
            $segments = explode('.', $key);
            $target    = &$this->items;

            foreach ($segments as $segment) {
                if (isset($target[$segment]) === false || is_array($target[$segment]) === false) {
                    $target[$segment] = [];
                }
                $target = &$target[$segment];
            }

            $target = $value;
        }

        /**
         * Получить всю конфигурацию
         *
         * @return array Значения конфигурации
         */
        public function all(): array
        {
            return $this->items;
        }
    }
