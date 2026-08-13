<?php

    declare(strict_types=1);

    namespace Delta\Http;

    use finfo;

    /**
     * Загруженный файл.
     *
     * Единственная точка валидации загрузок в проекте. Расширение определяется не по имени,
     * присланному клиентом, а по реальному MIME-типу содержимого (finfo): имя файла клиент
     * контролирует полностью, содержимое — нет.
     *
     * Защита веб-сервером здесь не рассматривается как рабочая: на боевом окружении nginx,
     * а .htaccess из каталога uploads не читается вообще.
     */
    final class UploadedFile
    {
        /** @var array<string, string[]> Разрешённые MIME-типы и допустимые для них расширения */
        public const IMAGE_TYPES = [
            'image/jpeg' => ['jpg', 'jpeg'],
            'image/png'  => ['png'],
            'image/gif'  => ['gif'],
            'image/webp' => ['webp'],
        ];

        /** @var array<string, string[]> Типы, разрешённые для вложений в сообщения */
        public const DOCUMENT_TYPES = [
            'application/pdf'    => ['pdf'],
            'text/plain'         => ['txt', 'log', 'md'],
            'application/zip'    => ['zip'],
            'application/msword' => ['doc'],
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => ['docx'],
            'application/vnd.ms-excel' => ['xls'],
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => ['xlsx'],
        ];

        /** @var int Ограничение размера по умолчанию, байт */
        public const DEFAULT_MAX_SIZE = 10 * 1024 * 1024;

        /**
         * Конструктор
         *
         * @param string $clientName Имя файла, присланное клиентом
         * @param string $tmpPath    Путь к временному файлу
         * @param int    $size       Размер, байт
         * @param int    $error      Код ошибки загрузки
         * @param string $clientMime MIME-тип, присланный клиентом (доверять нельзя)
         */
        public function __construct(
            private readonly string $clientName,
            private readonly string $tmpPath,
            private readonly int $size,
            private readonly int $error = UPLOAD_ERR_OK,
            private readonly string $clientMime = '',
        ) {
        }

        /**
         * Создать объект из элемента массива $_FILES
         *
         * @param array $file Элемент $_FILES
         *
         * @return self|null Объект файла либо null, если файл не передан
         */
        public static function fromArray(array $file): ?self
        {
            if (empty($file['tmp_name']) && ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                return null;
            }

            return new self(
                (string)($file['name'] ?? ''),
                (string)($file['tmp_name'] ?? ''),
                (int)($file['size'] ?? 0),
                (int)($file['error'] ?? UPLOAD_ERR_OK),
                (string)($file['type'] ?? ''),
            );
        }

        /**
         * Имя файла, присланное клиентом
         *
         * @return string Имя файла
         */
        public function clientName(): string
        {
            return $this->clientName;
        }

        /**
         * Путь к временному файлу
         *
         * @return string Путь
         */
        public function tmpPath(): string
        {
            return $this->tmpPath;
        }

        /**
         * Размер файла
         *
         * @return int Размер, байт
         */
        public function size(): int
        {
            return $this->size;
        }

        /**
         * Код ошибки загрузки
         *
         * @return int Код ошибки
         */
        public function error(): int
        {
            return $this->error;
        }

        /**
         * Реальный MIME-тип содержимого
         *
         * @return string MIME-тип
         */
        public function mimeType(): string
        {
            if (is_file($this->tmpPath) === false) {
                return '';
            }

            $mime = (new finfo(FILEINFO_MIME_TYPE))->file($this->tmpPath);

            return $mime === false ? '' : $mime;
        }

        /**
         * Признак того, что файл является изображением разрешённого типа
         *
         * @return bool Признак изображения
         */
        public function isImage(): bool
        {
            return isset(self::IMAGE_TYPES[$this->mimeType()]);
        }

        /**
         * Безопасное расширение файла, выведенное из реального MIME-типа
         *
         * @param array<string, string[]> $allowed Карта разрешённых типов
         *
         * @return string|null Расширение либо null, если тип не разрешён
         */
        public function safeExtension(array $allowed): ?string
        {
            $mime = $this->mimeType();
            if (isset($allowed[$mime]) === false) {
                return null;
            }

            $clientExtension = strtolower((string)pathinfo($this->clientName, PATHINFO_EXTENSION));

            // Расширение клиента сохраняем, только если оно согласуется с реальным типом
            if (in_array($clientExtension, $allowed[$mime], true)) {
                return $clientExtension;
            }

            return $allowed[$mime][0];
        }

        /**
         * Проверить файл на пригодность к сохранению
         *
         * @param array<string, string[]> $allowed Карта разрешённых MIME-типов
         * @param int                     $maxSize Предельный размер, байт
         *
         * @return string|null Текст ошибки либо null, если файл корректен
         */
        public function validate(array $allowed, int $maxSize = self::DEFAULT_MAX_SIZE): ?string
        {
            if ($this->error !== UPLOAD_ERR_OK) {
                return match ($this->error) {
                    UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Файл превышает допустимый размер.',
                    UPLOAD_ERR_PARTIAL                        => 'Файл загружен не полностью.',
                    UPLOAD_ERR_NO_FILE                        => 'Файл не выбран.',
                    default                                   => 'Не удалось загрузить файл.',
                };
            }

            if (is_uploaded_file($this->tmpPath) === false) {
                return 'Файл не является загруженным.';
            }

            if ($this->size <= 0) {
                return 'Файл пуст.';
            }

            if ($this->size > $maxSize) {
                return 'Файл превышает допустимый размер ' . (int)round($maxSize / 1024 / 1024) . ' МБ.';
            }

            if ($this->safeExtension($allowed) === null) {
                return 'Недопустимый тип файла: ' . ($this->mimeType() ?: 'не определён') . '.';
            }

            return null;
        }
    }
