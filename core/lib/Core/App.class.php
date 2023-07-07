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

    namespace Core;

    use Core\DataBases\DB;
    use Core\Helpers\Cache;
    use Core\Helpers\Files;
    use Core\Helpers\SystemFunctions;
    use Core\Models\{Posts, User};

    class App
    {

        /**
         * Отрисовка главной страницы
         *
         * @return void
         * @throws CoreException
         */
        public static function index()
        {
            global $twig, $USER;

            $finish_time = microtime(true);
            $delta       = round($finish_time - START_TIME, 3);
            if ($delta < 0.001) {
                $delta = 0.001;
            }

            echo $twig->render(
                'index.twig',
                [
                    'isAuthorized'  => User::isAuthorized(),
                    'isAdmin'       => User::isAuthorized() && $USER->isAdmin(),
                    'userDataTable' => User::isAuthorized() ? str_replace(
                        '<table style="',
                        '<table style="width:100%;',
                        SystemFunctions::arrayToTable($USER->getAllUserData(), 'Информация о текущем пользователе')
                    ) : '',
                    'statString' => 'Использовано ОЗУ: '
                                    . round(memory_get_usage() / 1024 / 1024, 2) . ' МБ / БД: '
                                    . round(DB::$workingTime,3) . ' сек (' . DB::$quantity . ' шт.) / Кэш: '
                                    . Files::convertBytes(Cache::getCacheSize())
                                    . ' / Генерация: ' . $delta . ' сек',
                            ]
            );
        }

        public static function sections()
        {
            global $twig, $USER;
            $userData = $USER ? $USER->getAllUserData() : '';
            $userRoles = $USER ? $USER->getRolesObject()->getFullRoles() : '';
            echo $twig->render(
                'sections.twig',
                [
                    'sections' => (new Posts())->getMainSections(),
                    'user' => $USER ? $USER->getAllUserData() : null,
                ]
            );
        }

        public static function section($sectionId)
        {
            global $twig, $USER;
            $userData = $USER ? $USER->getAllUserData() : '';
            $userRoles = $USER ? $USER->getRolesObject()->getFullRoles() : '';
            echo $twig->render(
                'section.twig',
                [
                    'sections' => (new Posts())->getMainSections(),
                    'user' => $USER ? $USER->getAllUserData() : null,
                    'sectionId' => $sectionId,
                ]
            );
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