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

    use Core\Models\Router;

    Router::route('/', '\Core\App::index');
    Router::route('/test', '\Core\App::test', true);
    Router::route('/info', '\Core\App::info');

    Router::route('/login', '\Core\App::login');
    Router::route('/login/authorize', '\Core\App::loginAuthorize');
    Router::route('/login/failed', '\Core\App::loginFailed');
    Router::route('/logout', '\Core\App::logout');


    Router::route('/dialogs', '\Core\App::dialogs');
    Router::route('/dialog/(\d+)', '\Core\App::dialog');

    Router::route('/users', '\Core\App::users');
    Router::route('/users/(\d+)/sendMessage', '\Core\App::sendMessage');

    Router::route('/404', function () {
        header('HTTP/1.0 404 Not Found');
        echo '404 - Page Not Found';
    });

    Router::route('/sections', '\Core\App::sections');
    Router::route('/section/(\d+)', '\Core\App::section');

    Router::route('blog/(\w+)/(\d+)', function ($category, $id) {
        echo $category . ':' . $id;
    });



   // Router::route('/test/(\d+)', '\Core\App::test');




