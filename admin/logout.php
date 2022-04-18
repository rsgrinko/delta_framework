<?php
    use Core\Models\User;
    require_once __DIR__ . '/inc/bootstrap.php';
    User::logout();
    header('Location: index.php');
?>