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

    namespace Core\Models;

    use Core\Models\DB;
    use Core\CoreException;

    class File
    {
        /**
         * Таблица заданий
         */
        const TABLE = DB_TABLE_PREFIX . 'files';

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
            $this->DB     = DB::getInstance();
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
         * @return array|null
         * @throws CoreException
         */
        public function getAllProps(): ?array
        {
            return $this->DB->query('SELECT * FROM ' . self::TABLE . ' WHERE id="' . $this->id . '"')[0];
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

        public function saveFile(string $filePath, ?string $fileName = null, bool $useMove = false): self
        {
            $storageFileName = md5(time() . $fileName . rand(1, 9999));
            if (empty($fileName)) {
                $fileName        = $storageFileName . '.' . static::getExt($filePath);
                $storageFileName .= '.' . static::getExt($filePath);
            } else {
                $storageFileName .= '.' . static::getExt($fileName);
            }
            $storageFullPath = $this->folder . '/' . $storageFileName;

            if ($useMove) {
                if (!move_uploaded_file($filePath, $storageFullPath)) {
                    throw new CoreException('Не удалось скопировать загруженный файл', CoreException::ERROR_FILE_COPY);
                }
            } else {
                if (!copy($filePath, $storageFullPath)) {
                    throw new CoreException('Не удалось скопировать файл', CoreException::ERROR_FILE_COPY);
                }
            }
            $this->name = $fileName;
            $this->path = self::FOLDER . '/' . $storageFileName;

            $this->id = $this->DB->addItem(self::TABLE, ['name' => $this->name, 'path' => $this->path]);

            return $this;
        }


    }