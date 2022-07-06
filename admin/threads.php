<?php
    use Core\Models\{User, MQ};

    require_once __DIR__ . '/inc/header.php';
    $MQ = new MQ();
?>
    <div class="pageheader">
        <div class="media">
            <div class="pageicon pull-left">
                <i class="fa fa-home"></i>
            </div>
            <div class="media-body">
                <ul class="breadcrumb">
                    <li><a href=""><i class="glyphicon glyphicon-home"></i></a></li>
                    <li>Диспетчер очереди</li>
                </ul>
                <h4>Диспетчер очереди</h4>
            </div>
        </div><!-- media -->
    </div><!-- pageheader -->

    <div class="contentpanel">


        <div class="row">
            <div class="col-sm-6">
                <div class="panel panel-primary">
                    <div class="panel-heading">
                        <h4 class="panel-title">Задания в очереди</h4>
                        <p>Задания, ожидающие выполнения и задания, выполнение которых завершилось ошибкой</p>
                    </div><!-- panel-heading -->
                    <div class="panel-body">
                        <table class="table">
                            <thead>
                            <tr>
                                <th>Всего</th>
                                <th>Активных</th>
                                <th>Выполняются</th>
                                <th>С ошибкой</th>
                            </tr>
                            </thead>

                            <tbody>
                            <tr>
                                <td><?=$MQ->getCountTasks();?></td>
                                <td><?=$MQ->getCountTasks(['active' => 'Y', 'in_progress' => 'N']);?></td>
                                <td><?=$MQ->getCountTasks(['active' => 'Y', 'in_progress' => 'Y']);?></td>
                                <td><?=$MQ->getCountTasks(['active' => 'N', 'in_progress' => 'N', 'status' => MQ::STATUS_ERROR]);?></td>
                            </tr>
                            </tbody>
                        </table>

                    </div>
                </div><!-- panel -->

            </div>


            <div class="col-sm-6">
                <div class="panel panel-primary">
                    <div class="panel-heading">
                        <h4 class="panel-title">Завершенные задания</h4>
                        <p>Задания, которые были успешно выполнены</p>
                    </div><!-- panel-heading -->
                    <div class="panel-body">

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
                                <td><?=$MQ->getCountTasksHistory();?></td>
                                <td><?=$MQ->getCountTasksHistory(['status' => MQ::STATUS_OK]);?></td>
                                <td><?=$MQ->getCountTasksHistory(['status' => MQ::STATUS_ERROR]);?></td>
                            </tr>
                            </tbody>
                        </table>

                    </div>
                </div><!-- panel -->

            </div>



        </div>




        <!-- table -->
        <div class="row">
            <div class="col-md-12r">
                <p class="lead">Задания в очереди</p>
                <div class="table-responsive">
                    <table class="table table-primary table-hover mb30">
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
                            $arTasks = $MQ->getTasks(10, 'priority', 'asc');
                            foreach($arTasks as $task) {
                                ?>
                                <tr>
                                    <td><?=$task['in_progress'] === MQ::VALUE_Y ? '<span class="badge badge-success">R</span>' : '';?></td>
                                    <td><?=$task['id'];?></td>
                                    <td><?=$task['active'] === MQ::VALUE_Y ? 'Да' : 'Нет';?></td>
                                    <td><?=$task['in_progress'] === MQ::VALUE_Y ? 'Да' : 'Нет';?></td>
                                    <td><?=$task['priority'];?></td>
                                    <td><?=$task['execution_time'];?></td>
                                    <td><?=$task['attempts'];?></td>
                                    <td><?=$task['attempts_limit'];?></td>
                                    <td><?=$task['class'];?></td>
                                    <td><?=$task['method'];?></td>
                                    <td><?=$task['params'];?></td>
                                    <td><?=$task['status'];?></td>
                                    <td><?=$task['response'];?></td>
                                    <td><?=$task['date_created'];?></td>
                                    <td><?=$task['date_updated'];?></td>
                                </tr>
                                <?php
                            }
                        ?>
                        </tbody>
                    </table>
                </div><!-- table-responsive -->
            </div>
        </div>
        <!-- end table -->
    </div><!-- contentpanel -->
<?php require_once __DIR__ . '/inc/footer.php'; ?>