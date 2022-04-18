<?php
    use Core\Models\User;

    require_once __DIR__ . '/../../core/bootstrap.php';
    $userId = User::getUserId();

    if(!empty($userId)) {
        $USER = new User($userId);
        $arUser = $USER->getAllUserData();
    } else {
        $USER = null;
        $arUser = [];
    }
