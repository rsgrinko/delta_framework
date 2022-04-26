<?php
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
        self::$page = $page;
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
        self::$total_pages = intval((self::$total - 1) / self::$limit) + 1;
        if (empty(self::$page) or self::$page < 0) {
            self::$page = 1;
        }
        if (self::$page > self::$total_pages) {
            self::$page = self::$total_pages;
        }
        $start = self::$page * self::$limit - self::$limit;
        $limit = $start . ', ' . self::$limit;

        return $limit;

    }

    /**
     * Вывод пагинации на страницу
     *
     * @param string $paginator_name
     * @param array $params
     */
    public static function show($paginator_name = 'page', $params = []): void
    {
        $url = '?';
        foreach ($params as $key => $value) {
            $url .= $key . '=' . $value . '&';
        }

        if (self::$page != 1) {
            $prevPage = '<li class="page-item active"><a class="page-link" href="' . $url . $paginator_name . '=' . (self::$page - 1) . '">‹</a></li>';
        } else {
            $prevPage = '<li class="page-item"><span class="page-link">‹</span></li>';
        }
        if (self::$page != self::$total_pages) {
            $nextPage = '<li class="page-item active"><a class="page-link" href="' . $url . $paginator_name . '=' . (self::$page + 1) . '">›</a></li>';
        } else {
            $nextPage = '<li class="page-item"><span class="page-link">›</span></li>';
        }
        echo '<ul class="pagination">'.$prevPage.'<li class="page-item"><a class="page-link" href="#">' . self::$page . ' из ' . self::$total_pages . '</a></li>'.$nextPage.'</ul>';
    }
}