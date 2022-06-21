<?php

    use Core\Models\Router;

    Router::route('/', '\Core\App::index');

    Router::route('/404', function () {
        header('HTTP/1.0 404 Not Found');
        echo '404 - Page Not Found';
    });

    Router::route('blog/(\w+)/(\d+)', function ($category, $id) {
        echo $category . ':' . $id;
    });




    Router::route('/test', '\Core\App::test');
    Router::route('/test/(\d+)', '\Core\App::test');




