<?php

    use Core\Models\Router;
    use Core\Helpers\SystemFunctions;

    Router::route('/', function () {
        echo 'Домашняя станица';
    });

    Router::route('/404', function () {
        header('HTTP/1.0 404 Not Found');
        echo '404 - Page Not Found';
    });

    Router::route('blog/(\w+)/(\d+)', function ($category, $id) {
        echo $category . ':' . $id;
    });




    class Test {
        public static function run($num = 0, $d = 0) {
            echo 'Hello World! ('.$num.') - ('.$d.')';
        }
    }


    Router::route('/test/(\w+)/(\d+)', 'Test::run');




