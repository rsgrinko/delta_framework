<?php

    use Core\CoreException;
    use Core\ExternalServices\Telegram;
    use Core\Models\{User, DB};
    use Core\Helpers\{SystemFunctions, Cache, Log, Mail, Zip, Pagination};

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
            <button class="navbar-toggler collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#navbarsExample04" aria-controls="navbarsExample04" aria-expanded="false" aria-label="Toggle navigation">
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
    Telegram::init('5232660453:AAGfMWu6EcRfBGSSURJsEEvGPmAqhCyzYHU', './');

    $userId = User::getCurrentUserId();
    try {
        $user = new User($userId);
        User::authorize((int)$userId);
        echo 'Current user id: ' . $userId . '<br>';
    } catch (CoreException $e) {
        echo $e->showTrace();
    }
    echo '<br><br>';

    if (User::isAuthorized()) {
        echo str_replace('<table style="', '<table style="width:100%;', SystemFunctions::arrayToTable($user->getAllUserData(), 'Информация о пользователе'));
    }

//echo str_replace('<table style="', '<table style="width:100%;', SystemFunctions::arrayToTable(Cache::getCacheInfo(), 'Информация о кэше'));

$count = (int)(DB::getInstance())->query('select count(*) as count from d_posts where post_type="post" and post_status="publish"')[0]['count'];
Pagination::execute($_REQUEST['page'], $count, 3);
$limit = Pagination::getLimit();

$arPosts = (DB::getInstance())->query('select * from d_posts where post_type="post" and post_status="publish" order by id desc limit ' . $limit);

$arResult = [];
foreach($arPosts as $post) {
    $arResult[$post['ID']] = [
        'title' => $post['post_title'],
        'postname' => $post['post_name'],
        'date' => $post['post_date'],
        'userId' => $post['post_author'],
        'userName' => (new User((int)$post['post_author']))->getName()

    ];
}
echo '<nav aria-label="Page navigation example">';
Pagination::show('page');
echo '</nav>';

echo str_replace('<table style="', '<table style="width:100%;', SystemFunctions::arrayToTable($arResult, 'Статьи'));
?>
            </div></div></div>
</main>
<footer class="py-3 my-4">
    <ul class="nav justify-content-center border-bottom pb-3 mb-3">
        <li class="nav-item"><a href="#" class="nav-link px-2 text-muted">Home</a></li>
        <li class="nav-item"><a href="#" class="nav-link px-2 text-muted">Features</a></li>
        <li class="nav-item"><a href="#" class="nav-link px-2 text-muted">Pricing</a></li>
        <li class="nav-item"><a href="#" class="nav-link px-2 text-muted">FAQs</a></li>
        <li class="nav-item"><a href="#" class="nav-link px-2 text-muted">About</a></li>
    </ul>
    <p class="text-center text-muted">© 2021 Company, Inc</p>
</footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ka7Sk0Gln4gmtz2MlQnikT1wXgYsOg+OMhuP+IlRH9sENBO0LRn5q+8nbTov4+1p" crossorigin="anonymous"></script>
    </body>
</html>
