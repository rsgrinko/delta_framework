<?php
    use Core\Models\{User, DB};

    const USE_ROUTER = false;
    $_SERVER['DOCUMENT_ROOT'] = '/home/rsgrinko/sites/dev.it-stories.ru';
    require_once $_SERVER['DOCUMENT_ROOT'] . '/core/bootstrap.php';

    $DB = DB::getInstance();

    use Workerman\Timer;
    use Workerman\Worker;

    function replaceBBCode($text_post) {
        $str_search = array(
            "#\\\n#is",
            "#\[b\](.+?)\[\/b\]#is",
            "#\[i\](.+?)\[\/i\]#is",
            "#\[u\](.+?)\[\/u\]#is",
            "#\[code\](.+?)\[\/code\]#is",
            "#\[quote\](.+?)\[\/quote\]#is",
            "#\[url=(.+?)\](.+?)\[\/url\]#is",
            "#\[url\](.+?)\[\/url\]#is",
            "#\[img\](.+?)\[\/img\]#is",
            "#\[size=(.+?)\](.+?)\[\/size\]#is",
            "#\[color=(.+?)\](.+?)\[\/color\]#is",
            "#\[list\](.+?)\[\/list\]#is",
            "#\[listn](.+?)\[\/listn\]#is",
            "#\[\*\](.+?)\[\/\*\]#"
        );
        $str_replace = array(
            "<br />",
            "<b>\\1</b>",
            "<i>\\1</i>",
            "<span style='text-decoration:underline'>\\1</span>",
            "<code class='code'>\\1</code>",
            "<table width = '95%'><tr><td>Цитата</td></tr><tr><td class='quote'>\\1</td></tr></table>",
            "<a href='\\1'>\\2</a>",
            "<a href='\\1'>\\1</a>",
            "<img width='150px' src='\\1' alt = 'Изображение' />",
            "<span style='font-size:\\1%'>\\2</span>",
            "<span style='color:\\1'>\\2</span>",
            "<ul>\\1</ul>",
            "<ol>\\1</ol>",
            "<li>\\1</li>"
        );
        return preg_replace($str_search, $str_replace, $text_post);
    }



    $connections = []; // сюда будем складывать все подключения

    // Стартуем WebSocket-сервер на порту 27800
    $worker = new Worker('websocket://0.0.0.0:8282');

    $worker->onWorkerStart = function($worker) use (&$connections)
    {
        $interval = 5; // пингуем каждые 5 секунд


        Timer::add($interval, function() use(&$connections) {
        foreach ($connections as $c) {
            // Если ответ от клиента не пришел 3 раза, то удаляем соединение из списка
            // и оповещаем всех участников об "отвалившемся" пользователе
            if ($c->pingWithoutResponseCount >= 3) {
                $messageData = [
                    'action' => 'ConnectionLost',
                    'userId' => $c->id,
                    'userName' => $c->userName,
                    'gender' => $c->gender,
                    'userColor' => $c->userColor
                ];
                $message = json_encode($messageData);

                unset($connections[$c->id]);
                $c->destroy(); // уничтожаем соединение

                // рассылаем оповещение
                foreach ($connections as $c) {
                    $c->send($message);
                }
            } else {
                $c->send('{"action":"Ping"}');
                $c->pingWithoutResponseCount++; // увеличиваем счетчик пингов
            }
        }
    });


        Timer::add(10, function () use (&$connections) {
            $infoMessages = [
                'Случайное число: <b>' . random_int(1000, 9999). '</b>',
                'Чат разработан командой <a href=\'https://it-stories.ru/\' target=\'_blank\'>it-stories.ru</a> - проекта о веб разработке и программировании',
                'Это информационное сообщение для проверки работы чата',
                'Нужен сайт? Студия <a href=\'https://softline71.ru/\' target=\'_blank\'>SoftLine71</a> поможет вам!',
            ];
            $randomKey = random_int(0, count($infoMessages) - 1);
            $randMessage = $infoMessages[$randomKey];

            foreach ($connections as $c) {
                $c->send('{"action":"Information", "text": "' . $randMessage . '"}');
                $c->pingWithoutResponseCount++; // увеличиваем счетчик пингов
            }
        });
    };

    $worker->onConnect = function($connection) use(&$connections)
    {
        // Эта функция выполняется при подключении пользователя к WebSocket-серверу
        $connection->onWebSocketConnect = function($connection) use (&$connections)
        {
            // Достаём имя пользователя, если оно было указано
            if (isset($_GET['userName'])) {
                $originalUserName = preg_replace('/[^a-zA-Zа-яА-ЯёЁ0-9\-\_ ]/u', '', trim($_GET['userName']));
            }
            else {
                $originalUserName = 'Инкогнито';
            }


            // Добавляем соединение в список
            // + мы можем добавлять произвольные поля в $connection
            //   и затем читать их из любой функции:
            $connection->userName = $originalUserName;
            $connection->pingWithoutResponseCount = 0; // счетчик безответных пингов

            $connections[$connection->id] = $connection;

            // Собираем список всех пользователей
            $users = [];
            foreach ($connections as $c) {
                // TcpConnection::id - уникальный идентификатор соединения,
                // присваивается автоматически. Будем использовать его как
                // идентификатор пользователя 'userId'.
                $users[] = [
                    'userId'    => $c->id,
                    'userName'  => $c->userName,
                ];
            }

            // Отправляем пользователю данные авторизации
            $messageData = [
                'action' => 'Authorized',
                'userId' => $connection->id,
                'userName' => $connection->userName,
                'users' => $users
            ];
            //$connection->send(json_encode($messageData));
            foreach ($connections as $c) {
                $c->send(json_encode($messageData));
            }

            // Оповещаем всех пользователей о новом участнике в чате
            $messageData = [
                'action' => 'Connected',
                'userId' => $connection->id,
                'userName' => $connection->userName,
                'gender' => $connection->gender,
                'userColor' => $connection->userColor
            ];
            $message = json_encode($messageData);

            foreach ($connections as $c) {
                $c->send($message);
            }
        };
    };


    $worker->onClose = function($connection) use(&$connections)
    {
        // Эта функция выполняется при закрытии соединения
        if (!isset($connections[$connection->id])) {
            return;
        }

        // Удаляем соединение из списка
        unset($connections[$connection->id]);

        // Собираем список всех пользователей
        $users = [];
        foreach ($connections as $c) {
            // TcpConnection::id - уникальный идентификатор соединения,
            // присваивается автоматически. Будем использовать его как
            // идентификатор пользователя 'userId'.
            $users[] = [
                'userId'    => $c->id,
                'userName'  => $c->userName,
            ];
        }

        // Оповещаем всех пользователей о выходе участника из чата
        $messageData = [
            'action' => 'Disconnected',
            'userId' => $connection->id,
            'userName' => $connection->userName,
            'users'   => $users,
        ];
        $message = json_encode($messageData);

        foreach ($connections as $c) {
            $c->send($message);
        }
    };


    $worker->onMessage = function($connection, $message) use (&$connections)
    {
        // распаковываем json
        $messageData = json_decode($message, true);
        // проверяем наличие ключа 'toUserId', который используется для отправки приватных сообщений
        $toUserId = isset($messageData['toUserId']) ? (int) $messageData['toUserId'] : 0;
        $action = isset($messageData['action']) ? $messageData['action'] : '';

        if ($action == 'Pong') {
            // При получении сообщения "Pong", обнуляем счетчик пингов
            $connection->pingWithoutResponseCount = 0;
        } else {
            // Все остальные сообщения дополняем данными об отправителе
            $messageData['userId'] = $connection->id;
            $messageData['userName'] = $connection->userName;

            // Преобразуем специальные символы в HTML-сущности в тексте сообщения
            $messageData['text'] = htmlspecialchars($messageData['text']);
            // Заменяем текст заключенный в фигурные скобки на жирный
            // (позже будет описано зачем и почему)
            //$messageData['text'] = preg_replace('/\{(.*)\}/u', '<b>\\1</b>', $messageData['text']);
            $messageData['text'] = replaceBBCode($messageData['text']);


            if ($toUserId == 0) {
                // Отправляем сообщение всем пользователям
                $messageData['action'] = 'PublicMessage';
                foreach ($connections as $c) {
                    $c->send(json_encode($messageData));
                }
            }
            else {
                $messageData['action'] = 'PrivateMessage';
                if (isset($connections[$toUserId])) {
                    // Отправляем приватное сообщение указанному пользователю
                    $connections[$toUserId]->send(json_encode($messageData));
                    // и отправителю
                    $connections->send(json_encode($messageData));
                }
                else {
                    $messageData['text'] = 'Не удалось отправить сообщение выбранному пользователю';
                    $connection->send(json_encode($messageData));
                }
            }
        }
    };


    Worker::runAll();