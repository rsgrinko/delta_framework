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

    /**
     * Таблица маршрутизации.
     *
     * Файл получает готовый объект $router из core/bootstrap.php и возвращает его.
     * Обработчики пока заданы строками '\Core\App::method' — это старый стиль, при котором
     * экшен печатает в вывод. Маршрутизатор оборачивает такой вывод в объект ответа,
     * поэтому маршруты можно переводить на новый стиль (возврат Response) по одному.
     */

    use Core\Helpers\Registry;
    use Delta\Http\Response;
    use Delta\Routing\Router;

    /** @var Router $router */

    // Прикладной код узнаёт текущий маршрут через Registry — ядро о Registry не знает
    $router->onMatch(static function (callable|string $handler): void {
        if (is_string($handler)) {
            Registry::set('currentPage', $handler);
        }
    });

    $router->route('/404', static fn(): Response => Response::notFound());

    $router->route('/', '\Core\App::index');
    $router->route('/info', '\Core\App::info');

    $router->route('/login', '\Core\App::login');
    $router->route('/login/authorize', '\Core\App::loginAuthorize');
    $router->route('/login/failed', '\Core\App::loginFailed');
    $router->route('/logout', '\Core\App::logout');

    $router->route('/dialogs', '\Core\App::dialogs');
    $router->route('/dialog/(\d+)', '\Core\App::dialog');
    $router->route('/dialog/(\d+)/messages', '\Core\App::dialogMessages');

    $router->route('/users', '\Core\App::users');
    $router->route('/users/(\d+)', '\Core\App::userProfile');
    $router->route('/users/(\d+)/sendMessage', '\Core\App::sendMessage');
    $router->route('/users/(\d+)/dialog', '\Core\App::goToDialog');

    $router->route('/profile', '\Core\App::profile');
    $router->route('/profile/personal', '\Core\App::profileUpdatePersonal');
    $router->route('/profile/password', '\Core\App::profileUpdatePassword');
    $router->route('/profile/avatar', '\Core\App::profileUpdateAvatar');
    $router->route('/profile/resend-verification', '\Core\App::profileResendVerification');

    return $router;
