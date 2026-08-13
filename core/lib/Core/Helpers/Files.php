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

    /**
     * Работа с файлами и директориями.
     */

    namespace Core\Helpers;

    class Files
    {

        /**
         * Конвертирование размера файла. Байты в килобайты, мегабайты
         *
         * @param int $size
         *
         * @return string
         */
        public static function convertBytes($size): string
        {
            $i = 0;
            while (floor($size / 1024) > 0) {
                ++$i;
                $size /= 1024;
            }

            $size = round($size, 2);
            switch ($i) {
                case 0:
                    return $size .= ' Б';
                case 1:
                    return $size .= ' КБ';
                case 2:
                    return $size .= ' МБ';
                case 3:
                    return $size .= ' ГБ';
                case 4:
                    return $size .= ' ТБ';
            }
            return 'Неизвестно';
        }

        /**
         * Получить расширение файла
         *
         * @param string $filename
         *
         * @return string
         */
        public static function getExt($filename)
        {
            return mb_strtolower(mb_substr(mb_strrchr($filename, '.'), 1));
        }

        /**
         * Получить список файлов директории в виде массива
         * То же самое делает функция scandir(), разница в том что у scandir() в массиве будут «.» и «..» и есть возможность сортировки..
         *
         * @param string $path
         *
         * @return string
         */
        public static function listFiles($path)
        {
            if ($path[mb_strlen($path) - 1] != '/') {
                $path .= '/';
            }

            $files = [];
            $dh    = opendir($path);
            while (false !== ($file = readdir($dh))) {
                if ($file != '.' && $file != '..' && !is_dir($path . $file) && $file[0] != '.') {
                    $files[] = $file;
                }
            }

            closedir($dh);
            return $files;
        }

        /**
         * Безопасное сохранение файла
         * Если дерикторя не существует - пытается её создать.
         * Если файл существует - к концу файла приписывает префикс.
         *
         * @param string $filename
         *
         * @return string
         */
        public static function safeFile($filename)
        {
            $dir = dirname($filename);
            if (!is_dir($dir)) {
                mkdir($dir, 0777, true);
            }

            $info   = pathinfo($filename);
            $name   = $dir . '/' . $info['filename'];
            $prefix = '';
            $ext    = (empty($info['extension'])) ? '' : '.' . $info['extension'];

            if (is_file($name . $ext)) {
                $i      = 1;
                $prefix = '_' . $i;
                while (is_file($name . $prefix . $ext)) {
                    $prefix = '_' . ++$i;
                }
            }

            return $name . $prefix . $ext;
        }

        /**
         * Удаление каталога со всем содержимым
         *
         * @param string $dir
         *
         * @return void
         */
        public static function removeDir($dir)
        {
            if ($objs = glob($dir . '/*')) {
                foreach ($objs as $obj) {
                    is_dir($obj) ? self::removeDir($obj) : unlink($obj);
                }
            }
            rmdir($dir);
        }

        /**
         * Удаление содержимого каталога
         *
         * @param string $dir
         *
         * @return void
         */
        public static function clearDir($dir)
        {
            if ($objs = glob($dir . '/*')) {
                foreach ($objs as $obj) {
                    is_dir($obj) ? self::removeDir($obj) : unlink($obj);
                }
            }
        }

        /**
         * Копирование директории с ее содержимым
         *
         * @param string $src
         * @param string $drc
         *
         * @return void
         */
        public static function copyDir($src, $drc)
        {
            $dir = opendir($src);

            if (!is_dir($drc)) {
                mkdir($drc, 0777, true);
            }

            while (false !== ($file = readdir($dir))) {
                if ($file != '.' && $file != '..') {
                    if (is_dir($src . '/' . $file)) {
                        self::copyDir($src . '/' . $file, $drc . '/' . $file);
                    } else {
                        copy($src . '/' . $file, $drc . '/' . $file);
                    }
                }
            }

            closedir($dir);
        }
    }