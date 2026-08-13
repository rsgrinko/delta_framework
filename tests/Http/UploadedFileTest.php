<?php

    declare(strict_types=1);

    namespace Tests\Http;

    use Delta\Http\UploadedFile;
    use PHPUnit\Framework\TestCase;

    /**
     * Тесты загруженного файла.
     *
     * Проверяют главное свойство: расширение выводится из реального содержимого, а не из
     * имени, присланного клиентом. Именно на этом строилась возможность залить .php.
     */
    class UploadedFileTest extends TestCase
    {
        /** @var string[] Созданные временные файлы */
        private array $tmpFiles = [];

        protected function tearDown(): void
        {
            foreach ($this->tmpFiles as $file) {
                @unlink($file);
            }
            $this->tmpFiles = [];
        }

        /**
         * Создать временный файл с заданным содержимым
         *
         * @param string $content Содержимое
         *
         * @return string Путь к файлу
         */
        private function tmpFile(string $content): string
        {
            $path = tempnam(sys_get_temp_dir(), 'delta');
            file_put_contents($path, $content);
            $this->tmpFiles[] = $path;

            return $path;
        }

        /**
         * Содержимое настоящего однопиксельного PNG
         *
         * @return string Двоичное содержимое
         */
        private function pngContent(): string
        {
            return (string)base64_decode(
                'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='
            );
        }

        public function testFromArrayReturnsNullWhenNoFileUploaded(): void
        {
            $this->assertNull(UploadedFile::fromArray([]));
            $this->assertNull(UploadedFile::fromArray(['tmp_name' => '', 'error' => UPLOAD_ERR_NO_FILE]));
        }

        public function testDetectsRealMimeTypeIgnoringClientName(): void
        {
            $file = new UploadedFile('шелл.php', $this->tmpFile($this->pngContent()), 100);

            $this->assertSame('image/png', $file->mimeType());
            $this->assertTrue($file->isImage());
        }

        public function testSafeExtensionComesFromContentNotFromName(): void
        {
            $file = new UploadedFile('шелл.php', $this->tmpFile($this->pngContent()), 100);

            $this->assertSame('png', $file->safeExtension(UploadedFile::IMAGE_TYPES));
        }

        public function testSafeExtensionKeepsClientExtensionWhenItAgreesWithContent(): void
        {
            $file = new UploadedFile('фото.jpeg', $this->tmpFile($this->pngContent()), 100);

            // содержимое PNG, поэтому расширение клиента не принимается
            $this->assertSame('png', $file->safeExtension(UploadedFile::IMAGE_TYPES));
        }

        public function testSafeExtensionIsNullForDisallowedType(): void
        {
            $file = new UploadedFile('скрипт.php', $this->tmpFile('<?php echo 1;'), 100);

            $this->assertNull($file->safeExtension(UploadedFile::IMAGE_TYPES));
        }

        public function testValidateRejectsUploadErrors(): void
        {
            $file = new UploadedFile('файл.png', '', 0, UPLOAD_ERR_INI_SIZE);

            $this->assertSame('Файл превышает допустимый размер.', $file->validate(UploadedFile::IMAGE_TYPES));
        }

        public function testValidateRejectsFileThatWasNotUploaded(): void
        {
            // is_uploaded_file() вне HTTP-запроса всегда ложно — проверка обязана это ловить
            $file = new UploadedFile('фото.png', $this->tmpFile($this->pngContent()), 100);

            $this->assertSame('Файл не является загруженным.', $file->validate(UploadedFile::IMAGE_TYPES));
        }

        public function testDocumentTypesAllowPdf(): void
        {
            $file = new UploadedFile('документ.pdf', $this->tmpFile("%PDF-1.4\n%\xE2\xE3\xCF\xD3\n"), 100);

            $this->assertSame('pdf', $file->safeExtension(UploadedFile::DOCUMENT_TYPES));
        }
    }
