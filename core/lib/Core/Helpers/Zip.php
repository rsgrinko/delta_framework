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

    /**
     * Класс для работы с Zip архивами
     */
    class Zip
    {
        /**
         * Создание архива из файла или папки
         *
         * @param string $source      Архивируемый файл или папка
         * @param string $destination Расположение созданного архива
         *
         * @return bool
         */
        public static function createArchive(string $source, string $destination): bool
        {
            if (!extension_loaded('zip') || !file_exists($source)) {
                return false;
            }

            $zip = new \ZipArchive();
            if (!$zip->open($destination, \ZipArchive::CREATE)) {
                return false;
            }

            $source = str_replace('\\', DIRECTORY_SEPARATOR, realpath($source));
            $source = str_replace('/', DIRECTORY_SEPARATOR, $source);

            if (is_dir($source) === true) {
                $files = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($source), \RecursiveIteratorIterator::SELF_FIRST
                );

                foreach ($files as $file) {
                    $file = str_replace('\\', DIRECTORY_SEPARATOR, $file);
                    $file = str_replace('/', DIRECTORY_SEPARATOR, $file);

                    if ($file == '.' || $file == '..' || empty($file) || $file == DIRECTORY_SEPARATOR) {
                        continue;
                    }
                    // Пропускаем "." и ".."
                    if (in_array(substr($file, strrpos($file, DIRECTORY_SEPARATOR) + 1), ['.', '..'])) {
                        continue;
                    }

                    $file = realpath($file);
                    $file = str_replace('\\', DIRECTORY_SEPARATOR, $file);
                    $file = str_replace('/', DIRECTORY_SEPARATOR, $file);

                    if (is_dir($file) === true) {
                        $d = str_replace($source . DIRECTORY_SEPARATOR, '', $file);
                        if (empty($d)) {
                            continue;
                        }
                        $zip->addEmptyDir($d);
                    } elseif (is_file($file) === true) {
                        $zip->addFromString(
                            str_replace($source . DIRECTORY_SEPARATOR, '', $file),
                            file_get_contents($file)
                        );
                    }
                }
            } elseif (is_file($source) === true) {
                $zip->addFromString(basename($source), file_get_contents($source));
            }

            return $zip->close();
        }

        /**
         * Распаковка архива
         *
         * @param string $source      Путь к архиву
         * @param string $destination Путь распаковки
         *
         * @return bool
         */
        public static function extractArchive(string $source, string $destination): bool
        {
            $zip = new \ZipArchive;
            if ($zip->open($source)) {
                $zip->extractTo($destination);
                $zip->close();
                return true;
            } else {
                return false;
            }
        }
    }
