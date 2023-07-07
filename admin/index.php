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

    use Core\Helpers\Cache;use Core\Helpers\Files;use Core\Models\{User, Roles};

    require_once __DIR__ . '/inc/header.php';
?>
    <div class="pageheader">
        <div class="media">
            <div class="pageicon pull-left">
                <i class="fa fa-home"></i>
            </div>
            <div class="media-body">
                <ul class="breadcrumb">
                    <li><a href=""><i class="glyphicon glyphicon-home"></i></a></li>
                    <li>Дашбоард</li>
                </ul>
                <h4>Дашбоард</h4>
            </div>
        </div><!-- media -->
    </div><!-- pageheader -->

    <div class="contentpanel">




        <div class="row">

            <div class="col-md-2">
                <div class="panel panel-info">
                    <div class="panel-heading">
                        <h3 class="panel-title">Версия ядра</h3>
                    </div>
                    <div class="panel-body">
                        <?= CORE_VERSION ?>
                    </div>
                </div>
            </div>

            <div class="col-md-2">
                <div class="panel panel-info">
                    <div class="panel-heading">
                        <h3 class="panel-title">Версия PHP</h3>
                    </div>
                    <div class="panel-body">
                        <?= phpversion() ?>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="panel panel-info">
                    <div class="panel-heading">
                        <h3 class="panel-title">Корневая директория</h3>
                    </div>
                    <div class="panel-body">
                        <?= ROOT_PATH ?>
                    </div>
                </div>
            </div>

            <div class="col-md-2">
                <div class="panel panel-info">
                    <div class="panel-heading">
                        <h3 class="panel-title">Логирование</h3>
                    </div>
                    <div class="panel-body">
                        <?= USE_LOG ? 'Включено' : 'Отключено' ?>
                    </div>
                </div>
            </div>

            <div class="col-md-2">
                <div class="panel panel-info">
                    <div class="panel-heading">
                        <h3 class="panel-title">Отладка</h3>
                    </div>
                    <div class="panel-body">
                        <?= DEBUG ? 'Включена' : 'Отключена' ?>
                    </div>
                </div>
            </div>

            <div class="col-md-2">
                <div class="panel panel-info">
                    <div class="panel-heading">
                        <h3 class="panel-title">Всего пользователей в базе</h3>
                    </div>
                    <div class="panel-body">
                        <?= User::getUsersCount() ?>
                    </div>
                </div>
            </div>

            <div class="col-md-2">
                <div class="panel panel-info">
                    <div class="panel-heading">
                        <h3 class="panel-title">Подтвержденных E-Mail</h3>
                    </div>
                    <div class="panel-body">
                        <?= User::getUsersCount(true) ?>
                    </div>
                </div>
            </div>




            <div class="col-md-2">
                <div class="panel panel-info">
                    <div class="panel-heading">
                        <h3 class="panel-title">Ролей в БД</h3>
                    </div>
                    <div class="panel-body">
                        <?= count(Roles::getAllRoles()) ?>
                    </div>
                </div>
            </div>

            <div class="col-md-2">
                <div class="panel panel-info">
                    <div class="panel-heading">
                        <h3 class="panel-title">Файловый кеш</h3>
                    </div>
                    <div class="panel-body">
                        <?= USE_CACHE ? 'Включен' : 'Отключен' ?>
                    </div>
                </div>
            </div>

            <div class="col-md-2">
                <div class="panel panel-info">
                    <div class="panel-heading">
                        <h3 class="panel-title">Размер кеша</h3>
                    </div>
                    <div class="panel-body">
                        <?= Files::convertBytes(Cache::getCacheSize()) ?>
                    </div>
                </div>
            </div>



            <div class="col-md-3">
                <div class="panel panel-info">
                    <div class="panel-heading">
                        <h3 class="panel-title">Расположение кеша</h3>
                    </div>
                    <div class="panel-body">
                        <?= CACHE_DIR ?>
                    </div>
                </div>
            </div>


            <div class="col-md-2">
                <div class="panel panel-info">
                    <div class="panel-heading">
                        <h3 class="panel-title">E-Mail сайта</h3>
                    </div>
                    <div class="panel-body">
                        <?= SERVER_EMAIL ?>
                    </div>
                </div>
            </div>

            <div class="col-md-2">
                <div class="panel panel-info">
                    <div class="panel-heading">
                        <h3 class="panel-title">Имя E-Mail сайта</h3>
                    </div>
                    <div class="panel-body">
                        <?= SERVER_EMAIL_NAME ?>
                    </div>
                </div>
            </div>








        </div>






    </div><!-- contentpanel -->
<?php
    require_once __DIR__ . '/inc/footer.php';
