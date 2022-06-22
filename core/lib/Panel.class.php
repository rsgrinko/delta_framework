<?php
    /**
     * Класс административной панели
     */

    namespace Core;

    class Panel
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
            echo $twig->render('admin/index.twig', ['navbarItems' => $navbarItems, 'name' => 'Roman Grinko']);
        }
    }