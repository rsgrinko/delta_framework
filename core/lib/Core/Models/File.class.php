<?php

    namespace Core\Models;

    use Core\Models\DB;
    use Core\CoreException;

    class File
    {
        /**
         * Таблица заданий
         */
        const TABLE = 'files';

        /**
         * Папка с файлами
         */
        const FOLDER = '/uploads';

        /**
         * Полный путь до папки с файлами
         */
        private $folder = null;

        /**
         * @var DB|null $DB Объект базы
         */
        private $DB = null;

        /**
         * @var null $id Идентификатор файла
         */
        private $id = null;

        /**
         * @var null $name Имя файла
         */
        private $name = null;

        /**
         * @var null $path Расположение файла
         */
        private $path = null;

        /**
         * Конструктор
         *
         * @throws CoreException
         */
        public function __construct(?int $id = null)
        {
            $this->folder = $_SERVER['DOCUMENT_ROOT'] . self::FOLDER;
            $this->DB = DB::getInstance();
            if (!empty($id)) {
                $this->id = $id;
                $this->loadFileProps();
            }
        }


        /**
         * Загрузка данных файла в объект
         *
         * @throws CoreException
         */
        private function loadFileProps(): self
        {
            $res = $this->DB->query('SELECT * FROM ' . self::TABLE . ' WHERE id="' . $this->id . '"')[0];
            if (empty($res)) {
                throw new CoreException('Файл с идентификатором ' . $this->id . ' не найден', CoreException::ERROR_FILE_NOT_FOUND);
            }

            $this->name = $res['name'];
            $this->path = $res['path'];
            return $this;
        }

        /**
         * Получение идентификатора файла
         *
         * @return int|null
         */
        public function getId(): ?int
        {
            return $this->id;
        }

        /**
         * Получение имени файла
         *
         * @return string|null
         */
        public function getName(): ?string
        {
            return $this->name;
        }

        /**
         * Получение пути до файла
         *
         * @return string|null
         */
        public function getPath(): ?string
        {
            return $this->path;
        }

        /**
         * Установка имени
         *
         * @param string|null $name Имя файла
         *
         * @return $this
         */
        public function setName(?string $name): self
        {
            $this->name = $name;
            return $this;
        }

        /**
         * Установка пути до файла
         *
         * @param string|null $path Расположение файла
         *
         * @return $this
         */
        public function setPath(?string $path): self
        {
            $this->path = $path;
            return $this;
        }


        /**
         * Получение файла по идентификатору
         *
         * @param int $id Идентификатор
         *
         * @return array|null
         */
        public function getById(int $id): ?array
        {
            return $this->DB->query('SELECT * FROM ' . self::TABLE . ' WHERE id="' . $id . '"');
        }


        /**
         * Сохранение изменений в базу
         *
         * @return $this
         */
        public function save(): self
        {
            $this->DB->update(self::TABLE, ['id' => $this->id], ['name' => $this->name, 'path' => $this->path]);
            return $this;

        }

        /**
         * Получить расширение файла
         *
         * @return false|string
         */
        public static function getExt(string $filename)
        {
            return end(explode('.', $filename));
        }

        public function saveFile(string $filePath, ?string $fileName = null): self
        {
            $storageFileName = md5(time() . $fileName . rand(1, 9999)) . '.' . static::getExt($filePath);
            $storageFullPath = $this->folder . '/' . $storageFileName;
            if (empty($fileName)) {
                $fileName = $storageFileName;
            }

            if (!copy($filePath, $storageFullPath)) {
                throw new CoreException('Не удалось скопировать файл', CoreException::ERROR_FILE_COPY);
            }
            $this->name = $fileName;
            $this->path = $storageFullPath;

            $this->id = $this->DB->addItem(self::TABLE, ['name' => $this->name, 'path' => $this->path]);

            return $this;
        }


    }