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

    use Core\Models\MQ;
    use Core\Helpers\SystemFunctions;

    require_once __DIR__ . '/../inc/bootstrap.php';
    $result = null;
    $MQ     = new MQ();
    ob_start();
?>
    <table class="table table-primary mb30">
        <thead>
        <tr>
            <th></th>
            <th>ID</th>
            <th>Активен</th>
            <th>Выполняется</th>
            <th>Приоритет</th>
            <th>Время выполнения</th>
            <th>Попытки</th>
            <th>Лимит попыток</th>
            <th>Класс</th>
            <th>Метод</th>
            <th>Параметры</th>
            <th>Статус</th>
            <th>Результат</th>
            <th>Создан</th>
            <th>Обновлен</th>
        </tr>
        </thead>
        <tbody>
        <?php
            $arTasks = $MQ->getTasks(10, 'in_progress', 'desc');
            foreach ($arTasks as $task) {
                ?>
                <tr class="<?php
                    if ($task['active'] === MQ::VALUE_Y && $task['in_progress'] === MQ::VALUE_Y && $task['status'] === 'ERROR') {
                        echo 'alert-warning';
                    } elseif ($task['active'] === MQ::VALUE_N && $task['status'] === 'ERROR') {
                        echo 'alert-danger';
                    } elseif ($task['in_progress'] === MQ::VALUE_Y) {
                        echo 'alert-success';
                    } elseif ($task['in_progress'] === MQ::VALUE_N && $task['status'] === 'ERROR') {
                        echo 'alert-danger';
                    } elseif ($task['active'] === MQ::VALUE_Y && $task['in_progress'] === MQ::VALUE_N) {
                        echo 'alert-info';
                    }
                ?>">
                    <td><?= $task['in_progress'] === MQ::VALUE_Y ? '<span class="badge badge-success">RUN</span>' : ''; ?></td>
                    <td><?= $task['id']; ?></td>
                    <td><?= $task['active'] === MQ::VALUE_Y ? 'Да' : 'Нет'; ?></td>
                    <td><?= $task['in_progress'] === MQ::VALUE_Y ? 'Да' : 'Нет'; ?></td>
                    <td><?= $task['priority']; ?></td>
                    <td><?= $task['execution_time']; ?></td>
                    <td><?= $task['attempts']; ?></td>
                    <td><?= $task['attempts_limit']; ?></td>
                    <td><?= $task['class']; ?></td>
                    <td><?= $task['method']; ?></td>
                    <td><?= $task['params']; ?></td>
                    <td><?= $task['status']; ?></td>
                    <td><?= $task['response']; ?></td>
                    <td><?= $task['date_created']; ?></td>
                    <td><?= $task['date_updated']; ?></td>
                </tr>
                <?php
            }
        ?>
        </tbody>
    </table>
<?php
    $result['threads'] = ob_get_clean();
    ob_start();
?>
    <table class="table table-primary mb30">
        <thead>
        <tr>
            <th>ID</th>
            <th>ID задания</th>
            <th>Время выполнения</th>
            <th>Попыток</th>
            <th>Лимит попыток</th>
            <th>Класс</th>
            <th>Метод</th>
            <th>Параметры</th>
            <th>Статус</th>
            <th>Результат</th>
            <th>Создан</th>
            <th>Обновлен</th>
        </tr>
        </thead>
        <tbody>
        <?php
            $arTasks = $MQ->getTasksHistory(10, 'id', 'desc');
            foreach ($arTasks as $task) {
                ?>
                <tr class="<?php
                    if ($task['status'] === MQ::STATUS_OK) {
                        echo 'alert-success';
                    } elseif ($task['status'] === MQ::STATUS_ERROR) {
                        echo 'alert-danger';
                    }
                ?>">
                    <td><?= $task['id']; ?></td>
                    <td><?= $task['task_id']; ?></td>
                    <td><?= $task['execution_time']; ?></td>
                    <td><?= $task['attempts']; ?></td>
                    <td><?= $task['attempts_limit']; ?></td>
                    <td><?= $task['class']; ?></td>
                    <td><?= $task['method']; ?></td>
                    <td><?= $task['params']; ?></td>
                    <td><?= $task['status']; ?></td>
                    <td><?= $task['response']; ?></td>
                    <td><?= $task['date_created']; ?></td>
                    <td><?= $task['date_updated']; ?></td>
                </tr>
                <?php
            }
        ?>
        </tbody>
    </table>
<?php
    $result['threads_history'] = ob_get_clean();
    ob_start();
?>
    <table class="table">
        <thead>
        <tr>
            <th>Всего</th>
            <th>Воркеров</th>
            <th>Активных</th>
            <th>Выполняются</th>
            <th>С ошибкой</th>
        </tr>
        </thead>

        <tbody>
        <tr>
            <td><?= $MQ->getCountTasks(); ?></td>
            <td><?= $MQ->getCountWorkers() . ' / ' . MQ::WORKERS_LIMIT; ?></td>
            <td><?= $MQ->getCountTasks(['active' => 'Y']); ?></td>
            <td><?= $MQ->getCountTasks(['active' => 'Y', 'in_progress' => 'Y']); ?></td>
            <td><?= $MQ->getCountTasks(['in_progress' => 'N', 'status' => MQ::STATUS_ERROR]); ?></td>
        </tr>
        </tbody>
    </table>
<?php
    $result['info_threads'] = ob_get_clean();
    ob_start();
?>
    <table class="table">
        <thead>
        <tr>
            <th>Всего</th>
            <th>Успешных</th>
            <th>Неудачных</th>
        </tr>
        </thead>

        <tbody>
        <tr>
            <td><?= $MQ->getCountTasksHistory(); ?></td>
            <td><?= $MQ->getCountTasksHistory(['status' => MQ::STATUS_OK]); ?></td>
            <td><?= $MQ->getCountTasksHistory(['status' => MQ::STATUS_ERROR]); ?></td>
        </tr>
        </tbody>
    </table>
<?php
    $result['info_threads_history'] = ob_get_clean();
    ob_start();
?>
    <div class="progress">
        <div style="width: <?= round(($MQ->getCountWorkers() / MQ::WORKERS_LIMIT) * 100, 2); ?>%" aria-valuemax="100" aria-valuemin="0"
             aria-valuenow="40" role="progressbar" class="progress-bar progress-bar-warning">
            <span class="sr-only">40% Complete (success)</span>
        </div>
    </div>
<?php
    $result['progress'] = ob_get_clean();
    ob_start();
    $memory = SystemFunctions::getHostMemoryInfo();
    ?>
    <div class="progress">
        <div style="width: <?= round(($memory['free'] + $memory['swapFree']) / ($memory['total'] + $memory['swapTotal']) * 100, 2); ?>%" aria-valuemax="100" aria-valuemin="0"
             aria-valuenow="40" role="progressbar" class="progress-bar progress-bar-warning">
            <span class="sr-only">40% Complete (success)</span>
        </div>
    </div>
    <?php
    $result['memory'] = ob_get_clean();
    echo json_encode($result, JSON_UNESCAPED_UNICODE);