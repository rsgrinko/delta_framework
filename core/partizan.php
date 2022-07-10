<?php
    if(count($argv) < 2) {
        die('Не выбрано действие. Для справки наберите php -f partizan.php -?' . PHP_EOL);
    }
    switch($argv[1]) {
        case 'migrate':
            define('DB_HOST', 'localhost');
            define('DB_USER', 'rsgrinko_delta');
            define('DB_PASSWORD', '2670135');
            define('DB_NAME', 'rsgrinko_delta');
            define('DB_TABLE_VERSIONS', 'versions');

            function connectDB() {
                $errorMessage = 'Невозможно подключиться к серверу базы данных';
                $conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
                if (!$conn)
                    throw new Exception($errorMessage);
                else {
                    $query = $conn->query('set names utf8');
                    if (!$query)
                        throw new Exception($errorMessage);
                    else
                        return $conn;
                }
            }

            function getMigrationFiles($conn) {
                $sqlFolder = 'migrations/';
                $allFiles = glob($sqlFolder . '*.sql');

                $query = sprintf('show tables from `%s` like "%s"', DB_NAME, DB_TABLE_VERSIONS);
                $data = $conn->query($query);
                $firstMigration = !$data->num_rows;

                if ($firstMigration) {
                    return $allFiles;
                }

                $versionsFiles = [];
                $query = sprintf('select `name` from `%s`', DB_TABLE_VERSIONS);
                $data = $conn->query($query)->fetch_all(MYSQLI_ASSOC);
                foreach ($data as $row) {
                    array_push($versionsFiles, $sqlFolder . $row['name']);
                }
                return array_diff($allFiles, $versionsFiles);
            }


            function migrate($conn, $file) {
                $command = sprintf('mysql -u%s -p%s -h %s -D %s < %s', DB_USER, DB_PASSWORD, DB_HOST, DB_NAME, $file);
                shell_exec($command);
                $baseName = basename($file);
                $query = sprintf('insert into `%s` (`name`) values("%s")', DB_TABLE_VERSIONS, $baseName);
                $conn->query($query);
            }

            $conn = connectDB();
            $files = getMigrationFiles($conn);
            if (empty($files)) {
                echo 'Ваша база данных в актуальном состоянии.';
            } else {
                echo 'Начинаем миграцию...' . PHP_EOL;

                // Накатываем миграцию для каждого файла
                foreach ($files as $file) {
                    migrate($conn, $file);
                    // Выводим название выполненного файла
                    echo basename($file) . PHP_EOL;
                }

                echo PHP_EOL . 'Миграция завершена.';
            }



            break;
        default:
            echo 'Неизвестная команда' . PHP_EOL;
            break;
    }