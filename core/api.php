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

    use Core\Api\{ApiController, ApiException, ApiView};
    use Core\Models\User;

    require_once __DIR__ . '/bootstrap.php';
    session_write_close();

    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Max-Age: 6000');
    header('Content-type: text/json; charset=UTF-8;');

    /** @var array $noAuthMethods Методы, не требующие авторизации */
    $noAuthMethods = [
        'testNoAuth',
        'test',
        'testNotFound',
    ];

    $method = $_REQUEST['method'] ?: null;
    $token  = $_REQUEST['token'] ?: null;

    try {
        // Если вызван метод, не требующий авторизации
        if (in_array($method, $noAuthMethods, true)) {
            ApiController::$method();
            die();
        }

        if (empty($token)) {
            throw new ApiException('Не задан токен', ApiException::ERROR_TOKEN_UNDEFINED);
        }

        $userObject = User::getByToken($token);
        if ($userObject === null) {
            throw new ApiException('Токен некорректен', ApiException::ERROR_INCORRECT_TOKEN);
        }

        $apiController = new ApiController($userObject);

        // Проверяем существование метода контроллера
        if (!method_exists($apiController, $method)) {
            throw new ApiException('Метод не существует', ApiException::ERROR_METHOD_NOT_FOUND);
        }

        $apiController->$method();
    } catch (Throwable $e) {
        ApiView::outputError($e);
    }