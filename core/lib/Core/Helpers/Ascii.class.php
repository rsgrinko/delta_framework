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

    use Core\CoreException;

    class Ascii
    {
        /**
         * @var \GdImage Изображение
         */
        private $image;

        /**
         * @var string $file Полный путь до изображения
         */
        private string $file;

        /**
         * @var int $pixelSize Размер пикселя
         */
        private int $pixelSize = 1;

        /**
         * @var int $quality Качество
         */
        private int $quality = 1;

        /**
         * @var bool $isUseColor Флаг использования цвета
         */
        public bool $isUseColor = false;

        /**
         * @var array|string[] $chars Массив символов для рисования
         */
        public array $chars = ['@', '#', '+', '\'', ';', ':', ',', '.', '`', ' '];

        /**
         * @var string $colorChar Символ для цветного рисования
         */
        public string $colorChar = '#';

        /**
         * Конструктор
         *
         * @param string $file Полный путь к изображению
         *
         * @throws CoreException
         */
        public function __construct(string $file)
        {
            $this->file = $file;

            if (!file_exists($file)) {
                throw new CoreException('Изображение ' . $this->file . ' недоступно.');
            }

            switch (exif_imagetype($file)) {
                case IMAGETYPE_GIF:
                    $this->image = imagecreatefromgif($this->file);
                    break;
                case IMAGETYPE_JPEG:
                    $this->image = imagecreatefromjpeg($this->file);
                    break;
                case IMAGETYPE_PNG:
                    $this->image = imagecreatefrompng($this->file);
                    break;
                case IMAGETYPE_WBMP:
                    $this->image = imagecreatefromwbmp($this->file);
                    break;
                case IMAGETYPE_XBM:
                    $this->image = imagecreatefromxbm($this->file);
                    break;
                default:
                    throw new CoreException('Неподдерживаемый формат изображения.');
                    break;
            }
        }

        /**
         * Установка размера пикселя
         *
         * @param int $size Размер пикселя
         *
         * @return $this
         */
        public function setPixelSize(int $size): self
        {
            $this->pixelSize = $size;
            return $this;
        }

        /**
         * Установка флага Цветного/ЧБ изображения
         *
         * @param bool $isUseColor Флаг использования цвета
         *
         * @return $this
         */
        public function setUseColor(bool $isUseColor = true): self
        {
            $this->isUseColor = $isUseColor;
            return $this;
        }


        /**
         * Получение ASCII изображения
         *
         * @return string ASCII изображение
         */
        public function draw(): string
        {
            $width  = imagesx($this->image);
            $height = imagesy($this->image);

            if ($this->isUseColor === true) {
                $pixel_color = imagecolorat($this->image, 1, 1);
                $rgb         = imagecolorsforindex($this->image, $pixel_color);
                $output      = '<span style="color: ' . $this->rgbToHex($rgb['red'], $rgb['green'], $rgb['blue']) . ';">';
            } else {
                $output = '';
            }

            for ($y = 0; $y < $height; $y += $this->quality) {
                for ($x = 0; $x < $width; $x += $this->quality) {
                    $pixel_color = imagecolorat($this->image, $x, $y);
                    $rgb         = imagecolorsforindex($this->image, $pixel_color);

                    if ($this->isUseColor === true) {
                        if ($x > $this->quality && $y > $this->quality && $pixel_color === imagecolorat($this->image, $x - $this->quality, $y)) {
                            $char = $this->colorChar;
                        } else {
                            $char = '</span><span style="color: ' . $this->rgbToHex($rgb['red'], $rgb['green'], $rgb['blue']) . ';">'
                                    . $this->colorChar;
                        }
                    } else {
                        $brightness = $rgb['red'] + $rgb['green'] + $rgb['blue'];
                        $brightness = round($brightness / (765 / (count($this->chars) - 1)));
                        $char       = $this->chars[$brightness];
                    }
                    $output .= $char;
                }
                $output .= PHP_EOL;
            }

            if ($this->isUseColor === true) {
                $output .= '</span>';
            }

            $result = '<pre style="font: ' . $this->pixelSize * 2 . 'px/' . $this->pixelSize . 'px monospace;">';
            $result .= $output;
            $result .= '</pre>';
            return $result;
        }

        /**
         * Преобразование цвета из RDB в HEX
         *
         * @param int $red   Красный
         * @param int $green Зеленый
         * @param int $blue  Синий
         *
         * @return string Цвет в HEX
         */
        private function rgbToHex(int $red, int $green, int $blue): string
        {
            $hex = '#';
            $hex .= str_pad(dechex($red), 2, '0', STR_PAD_LEFT);
            $hex .= str_pad(dechex($green), 2, '0', STR_PAD_LEFT);
            $hex .= str_pad(dechex($blue), 2, '0', STR_PAD_LEFT);
            return $hex;
        }

        /**
         * Деструктор
         */
        public function __destruct()
        {
            imagedestroy($this->image);
        }

    }
