<?php

    namespace Core\Helpers;

    use Core\Models\DB;

    class Migration
    {
        /**
         * Таблица с историей миграций
         */
        const TABLE = 'migrations';

        /**
         * @var DB|null $DB Объект базы
         */
        private $DB = null;

        /**
         * @var null $sqlFolder Папка с миграциями
         */
        private $sqlFolder = null;

        /**
         * Конструктор
         */
        public function __construct()
        {
            $this->DB        = DB::getInstance();
            $this->sqlFolder = ROOT_PATH . '/core/migrations';
        }

        /**
         * Получение файлов миграции
         *
         * @return array|false
         */
        private function getMigrationFiles()
        {
            $allFiles = glob($this->sqlFolder . '/*.sql');

            $res = $this->DB->query('SHOW TABLES LIKE "' . self::TABLE . '"');
            if (empty($res)) {
                return $allFiles;
            }

            $versionsFiles = [];
            $res           = $this->DB->query('SELECT `name` FROM ' . self::TABLE);
            foreach ($res as $row) {
                $versionsFiles[] = $this->sqlFolder . '/' . $row['name'];
            }
            return array_diff($allFiles, $versionsFiles);
        }


        /**
         * Миграция
         *
         * @param string $file Путь до файла миграции
         *
         * @return void
         */
        public function migrate(string $file)
        {
            shell_exec('mysql -u' . DB_USER . ' -p' . DB_PASSWORD . ' -h ' . DB_HOST . ' -D ' . DB_NAME . ' < ' . $file);
            $baseName = basename($file);
            $this->DB->query('INSERT INTO ' . self::TABLE . ' (`name`) values("' . $baseName . '")');
        }

        /**
         * Запуск процесса миграции
         *
         * @return array|null
         */
        public function run(): ?array
        {
            $result = [];
            $files  = $this->getMigrationFiles();
            if (empty($files)) {
                return null;
            } else {
                foreach ($files as $file) {
                    $this->migrate($file);
                    $result[] = basename($file);
                }
            }
            return $result;
        }


    }