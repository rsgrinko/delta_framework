<?php

    use Core\CoreException;
    use Core\ExternalServices\Telegram;
    use Core\Models\{User, DB};
    use Core\Helpers\{SystemFunctions, Cache, Log, Mail, Zip, Pagination};

    require_once $_SERVER['DOCUMENT_ROOT'] . '/core/bootstrap.php';
    echo '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.1/dist/css/bootstrap.min.css">';

    echo '<a href="/admin/">{{ADMIN_PANEL_LINK_NAME}}</a> | <a href="/?clear_cache=Y">{{CLEAR_CACHE_LINK_NAME}}</a> | <a href="/">{{REFRESH_PAGE_LINK_NAME}}</a> <br>';

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
        echo SystemFunctions::arrayToTable($user->getAllUserData(), 'Информация о пользователе');
    }

//echo SystemFunctions::arrayToTable(Cache::getCacheInfo(), 'Информация о кэше');





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

echo SystemFunctions::arrayToTable($arResult, 'Статьи');