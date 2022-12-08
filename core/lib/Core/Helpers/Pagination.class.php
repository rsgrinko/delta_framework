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

    class Pagination
    {
        private static $page;

        private static $total;

        private static $total_pages;

        private static $limit;

        /**
         * Задание первоначальных данных для пагинации
         *
         * @param int $page
         * @param int $total
         * @param int $limit
         */
        public static function execute($page, $total, $limit): void
        {
            self::$page  = $page;
            self::$total = $total;
            self::$limit = $limit;
        }

        /**
         * Формирование лимита для выборки из базы элементов текущей страницы
         *
         * @return string
         */
        public static function getLimit(): string
        {
            self::$total_pages = (int)((self::$total - 1) / self::$limit) + 1;
            if (empty(self::$page) || self::$page < 0) {
                self::$page = 1;
            }
            if (self::$page > self::$total_pages) {
                self::$page = self::$total_pages;
            }
            $start = self::$page * self::$limit - self::$limit;
            return $start . ', ' . self::$limit;
        }

        /**
         * Вывод пагинации на страницу
         *
         * @param string $paginator_name
         * @param array  $params
         */
        public static function show($paginator_name = 'page', $params = []): void
        {
            $url = '?';
            foreach ($params as $key => $value) {
                $url .= $key . '=' . $value . '&';
            }

            if (self::$page != 1) {
                $prevPage = '<li class="page-item active"><a class="page-link" href="' . $url . $paginator_name . '=' . (self::$page - 1)
                            . '">‹</a></li>';
            } else {
                $prevPage = '<li class="page-item"><span class="page-link">‹</span></li>';
            }
            if (self::$page != self::$total_pages) {
                $nextPage = '<li class="page-item active"><a class="page-link" href="' . $url . $paginator_name . '=' . (self::$page + 1)
                            . '">›</a></li>';
            } else {
                $nextPage = '<li class="page-item"><span class="page-link">›</span></li>';
            }
            echo '<ul class="pagination">' . $prevPage . '<li class="page-item"><a class="page-link" href="#">' . self::$page . ' из '
                 . self::$total_pages . '</a></li>' . $nextPage . '</ul>';
        }
    }