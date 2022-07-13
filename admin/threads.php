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
                    <div class="panel-body" id="info_threads">
                        <div class="preloader"></div>
                    </div>
                </div><!-- panel -->

            </div>


            <div class="col-sm-6">
                <div class="panel panel-primary">
                    <div class="panel-heading">
                        <h4 class="panel-title">Завершенные задания</h4>
                        <p>Задания, которые были успешно выполнены</p>
                    </div><!-- panel-heading -->
                    <div class="panel-body"  id="info_threads_history">
                        <div class="preloader"></div>
                    </div>
                </div><!-- panel -->

            </div>



        </div>

    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-primary">
                <div class="panel-heading">
                    <h4 class="panel-title">Текущая нагрузка на диспетчер очереди</h4>
                    <p>Чем больше количество активных воркеров - тем больше нагрузка</p>
                </div><!-- panel-heading -->
                <div class="panel-body" id="progress">
                    <div class="preloader"></div>
                </div>
            </div>
        </div>
    </div>


    <!-- table -->
    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-primary">
                <div class="panel-heading">
                    <h4 class="panel-title">Очередь</h4>
                    <p>Список текущих заданий очереди</p>
                </div><!-- panel-heading -->

                <div class="table-responsive" id="threads_table">
                    <div class="preloader"></div>
                </div><!-- table-responsive -->

            </div>
        </div>
        <!-- end table -->

    </div><!-- contentpanel -->


    <!-- table -->
    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-primary">
                <div class="panel-heading">
                    <h4 class="panel-title">Выполненные задания</h4>
                    <p>Список заверщившихся заданий</p>
                </div><!-- panel-heading -->

                <div class="table-responsive" id="threads_history_table">
                    <div class="preloader"></div>
                </div><!-- table-responsive -->

            </div>
        </div>
        <!-- end table -->

    </div><!-- contentpanel -->
    <script>
        var isPaused = false;
        function update()
        {
            isPaused = true;
            $.ajax({
                url: 'ajax/threads.php',
                cache: false,
                success: function(data){
                    data = JSON.parse(data);
                    $('#info_threads').html(data.info_threads);
                    $('#info_threads_history').html(data.info_threads_history);
                    $('#progress').html(data.progress);
                    $('#threads_table').html(data.threads);
                    $('#threads_history_table').html(data.threads_history);

                    isPaused = false;
                }
            });
        }
        $(document).ready(function(){
            update();
            setInterval(function () {
                if(!isPaused){
                    update();
                }
            },500);
        });
    </script>
<?php require_once __DIR__ . '/inc/footer.php'; ?>