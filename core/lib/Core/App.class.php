<?php

    namespace Core;

    class App
    {
        public static function index()
        {
            global $twig;
            $navbarItems = [
                ['name' => 'Главная', 'url' => '/', 'active' => true],
                ['name' => 'Админка', 'url' => '/admin', 'active' => false],
                ['name' => 'Командная PHP строка', 'url' => '/phpcmd.php', 'active' => false],
                ['name' => 'Тест', 'url' => '/test', 'active' => false],
            ];
            echo $twig->render('index.twig', ['navbarItems' => $navbarItems, 'name' => 'Roman Grinko']);
        }

        public static function test(int $id = 1)
        {
            global $twig;
            $navbarItems = [
                ['name' => 'Главная', 'url' => '/', 'active' => true],
                ['name' => 'Админка', 'url' => '/admin', 'active' => false],
                ['name' => 'Командная PHP строка', 'url' => '/phpcmd.php', 'active' => false],
                ['name' => 'Тест', 'url' => '/test', 'active' => false],
            ];

            $res = (new \Core\Models\User($id))->getAllUserData();
            echo $twig->render('test.twig', ['navbarItems' => $navbarItems, 'userId' => $id, 'res' => print_r($res, true)]);
        }
    }