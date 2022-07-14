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

    use Core\CoreException;
    use Core\ExternalServices\Telegram;
    use Core\Models\{User, DB, Roles};
    use Core\Helpers\{SystemFunctions, Cache, Log, Mail, Zip, Pagination, Files};

    const USE_ROUTER = true;
    require_once $_SERVER['DOCUMENT_ROOT'] . '/core/bootstrap.php';
?>
<!doctype html>
<html lang="ru">
<head>
    <!-- Обязательные метатеги -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.1/dist/css/bootstrap.min.css">
    <title>Главная тестовая</title>
</head>
<body>
<main>
    <nav class="navbar navbar-expand-md navbar-dark bg-dark" aria-label="Fourth navbar example">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">Delta Framework</a>
            <button class="navbar-toggler collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#navbarsExample04"
                    aria-controls="navbarsExample04" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="navbar-collapse collapse" id="navbarsExample04" style="">
                <ul class="navbar-nav me-auto mb-2 mb-md-0">
                    <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="/">Главная</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/admin/">{{ADMIN_PANEL_LINK_NAME}}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/?clear_cache=Y">{{CLEAR_CACHE_LINK_NAME}}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/phpcmd.php">{{PHP_CMD_LINK_NAME}}</a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link disabled" href="#" tabindex="-1" aria-disabled="true">Disabled</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>


    <div class="container">
        <div class="row justify-content-md-center">
            <div class="col-md">
                <?php
                    echo $_REQUEST['route'] . '<br>';
                    if (User::isAuthorized()) {
                        global $USER;
                        echo str_replace(
                            '<table style="',
                            '<table style="width:100%;',
                            SystemFunctions::arrayToTable($USER->getAllUserData(), 'Информация о пользователе')
                        );
                    }

                    //echo str_replace('<table style="', '<table style="width:100%;', SystemFunctions::arrayToTable(Cache::getCacheInfo(), 'Информация о кэше'));

                    $count = (int)(DB::getInstance())->query('select count(*) as count from users')[0]['count'];
                    Pagination::execute($_REQUEST['page'], $count, 2);
                    $limit = Pagination::getLimit();

                    $arData = (DB::getInstance())->query('select * from users order by id desc limit ' . $limit);

                    $arResult = [];
                    foreach ($arData as $element) {
                        //(new User($element['id']))->addToGroup(2);
                        $arResult[$element['id']] = [
                            'id'          => $element['id'],
                            'login'       => $element['login'],
                            //'password'     => $element['password'],
                            'name'        => $element['name'],
                            'email'       => $element['email'],
                            'image'       => $element['image'],
                            'token'       => $element['token'],
                            'last_active' => $element['last_active'],
                            'groups'      => array_map(function ($element) {
                                return Roles::getAllRoles()[$element];
                            }, (new User($element['id']))->getRolesObject()->getRoles()),
                            //'groups_human' => User::getAllGroups(),

                        ];
                    }
                    echo '<nav aria-label="Page navigation example">';
                    Pagination::show('page');
                    echo '</nav>';

                    echo str_replace('<table style="', '<table style="width:100%;', SystemFunctions::arrayToTable($arResult, 'Пользователи'));
                ?>
            </div>
        </div>
    </div>
</main>
<footer class="py-3 my-4">
    <ul class="nav justify-content-center border-bottom pb-3 mb-3">
        <li class="nav-item"><a href="#" class="nav-link px-2 text-muted">Home</a></li>
        <li class="nav-item"><a href="#" class="nav-link px-2 text-muted">Features</a></li>
        <li class="nav-item"><a href="#" class="nav-link px-2 text-muted">Pricing</a></li>
        <li class="nav-item"><a href="#" class="nav-link px-2 text-muted">FAQs</a></li>
        <li class="nav-item"><a href="#" class="nav-link px-2 text-muted">About</a></li>
    </ul>
    <?php
        $finish_time = microtime(true);
        $delta       = round($finish_time - START_TIME, 3);
        if ($delta < 0.001) {
            $delta = 0.001;
        }
        $cacheSize = Cache::getCacheSize();
        echo '<p class="text-center text-muted">Использовано ОЗУ: ' . round(memory_get_usage() / 1024 / 1024, 2) . ' МБ / БД: ' . round(
                DB::$workingTime,
                3
            ) . ' сек (' . DB::$quantity . ' шт.) / Кэш: ' . Files::convertBytes($cacheSize) . ' / Генерация: ' . $delta . ' сек</p>';

    ?>
    <p class="text-center text-muted">© <?= date('Y'); ?> Delta Project</p>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-ka7Sk0Gln4gmtz2MlQnikT1wXgYsOg+OMhuP+IlRH9sENBO0LRn5q+8nbTov4+1p" crossorigin="anonymous"></script>
</body>
</html>
