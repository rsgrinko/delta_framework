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

    use Core\Models\User;

    require_once __DIR__ . '/../../core/bootstrap.php';
    $userId = User::getCurrentUserId();

    if (!empty($userId)) {
        $USER   = new User($userId);
        $arUser = $USER->getAllUserData();
    } else {
        $USER   = null;
        $arUser = [];
    }

    /**
     * Единая точка контроля доступа в админку.
     *
     * Раньше проверка прав была скопирована в каждую страницу по отдельности, и любая новая
     * страница легко оставалась без неё — именно так `admin/ajax/threads.php` оказался
     * доступен анонимно и отдавал содержимое очереди задач наружу.
     *
     * Страницы из списка ниже — единственные, доступные без прав администратора: на них
     * пользователь ещё не авторизован либо как раз выходит из системы.
     */
    $publicAdminPages = ['login.php', 'logout.php', 'register.php'];

    /**
     * Страницы, требующие полных прав администратора, а не просто доступа в панель.
     * Список повторяет права, которые раньше были прописаны в самих страницах.
     */
    $adminOnlyPages = ['cacheInfo.php', 'groups.php', 'threads.php', 'users.php'];

    $currentAdminPage = basename((string)($_SERVER['SCRIPT_NAME'] ?? ''));

    if (in_array($currentAdminPage, $publicAdminPages, true) === false) {
        if ($USER === null) {
            header('Location: /admin/login.php');
            die();
        }

        $isAdmin = $USER->isAdmin();

        if (in_array($currentAdminPage, $adminOnlyPages, true)) {
            if ($isAdmin === false) {
                header('Location: /admin/');
                die();
            }
        } elseif ($isAdmin === false && $USER->haveAccessToAdminPanel() === false) {
            header('Location: /');
            die();
        }
    }
