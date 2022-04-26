<?php

    use Core\CoreException;
    use Core\ExternalServices\Telegram;
    use Core\Models\{User, DB};
    use Core\Helpers\{SystemFunctions, Cache, Log, Mail, Zip};

    require_once $_SERVER['DOCUMENT_ROOT'] . '/core/bootstrap.php';

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


    echo SystemFunctions::arrayToTable(Cache::getCacheInfo(), 'Информация о кэше');

$arPosts = (DB::getInstance())->query('select * from d_posts where post_type="post" and post_status="publish" order by id desc limit 10');
$arResult = [];
foreach($arPosts as $post) {
    $arResult[] = [
        'title' => $post['post_title'],
        'postname' => $post['post_name'],
        'date' => $post['post_date'],
        'userId' => $post['post_author'],
        'userName' => (new User((int)$post['post_author']))->getName()

    ];
}

echo SystemFunctions::arrayToTable($arResult, 'Статьи');