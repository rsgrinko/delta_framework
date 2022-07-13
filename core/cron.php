<?php
    @ignore_user_abort(true);
    set_time_limit(0);
    use Core\Helpers\Cache;
    use Core\Models\MQ;

    if (empty($_SERVER['DOCUMENT_ROOT'])) {
        $_SERVER['DOCUMENT_ROOT'] = __DIR__ . '/..';
    }
    require_once __DIR__ . '/bootstrap.php';

    // Запускаем воркер диспетчера очереди
    exec('(php -f ' . __DIR__ . '/runtime/threadsWorker.php & ) >> /dev/null 2>&1');


    // Каждые 10 минут добавляем в очередь задание на проверку новостей слободы и отправку в телегу
    $cacheId = md5('cron_myslo');
    $runMyslo = false;
    if(Cache::check($cacheId) && $cronMyslo = Cache::get($cacheId)) {
        if((time() - (int)$cronMyslo) > 600) {
            $runMyslo = true;
            Cache::set($cacheId, time());
            sendTelegram('cron_myslo if if set (' . $cronMyslo . ')');
        }
    } else {
        $runMyslo = true;
        sendTelegram('cron_myslo global else (' . $cronMyslo . ')');
        Cache::set($cacheId, time());
    }

    if($runMyslo === true) {
        $result = (new MQ())->setAttempts(3)
                  ->setPriority(3)
                  ->setCheckDuplicates(true)
                  ->createTask('Core\MQTasks', 'getMySLO');
    }