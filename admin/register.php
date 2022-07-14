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

    use Core\Models\User;

    require_once __DIR__ . '/inc/bootstrap.php';
    if (User::isAuthorized()) {
        header('Location: index.php');
    } else {
        $arErrors = [];
        if (!empty($_REQUEST['send']) && $_REQUEST['send'] === 'Y') {
            if (empty($_REQUEST['login'])) {
                $arErrors[] = 'Логин не задан';
            }
            if (empty($_REQUEST['name'])) {
                $arErrors[] = 'Имя не задано';
            }
            if (empty($_REQUEST['email'])) {
                $arErrors[] = 'E-Mail не задан';
            }
            if (empty($_REQUEST['pass'])) {
                $arErrors[] = 'Пароль не задан';
            }
            if (!empty($_REQUEST['login']) && User::isUserExists($_REQUEST['login'])) {
                $arErrors[] = 'Пользователь с таким логином уже зарегистрирован';
            }

            if (empty($arErrors)) {
                $userId = User::create($_REQUEST['login'], $_REQUEST['pass'], $_REQUEST['email'], $_REQUEST['name']);

                User::authorize($userId);
                header('Location: index.php');
            }
        }


        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="utf-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
            <meta name="description" content="">
            <meta name="author" content="">

            <title>Delta Framework - Регистрация</title>

            <link href="//<?php echo $_SERVER['SERVER_NAME']; ?>/admin/styles/css/style.default.css" rel="stylesheet">

            <!-- HTML5 shim and Respond.js IE8 support of HTML5 elements and media queries -->
            <!--[if lt IE 9]>
            <script src="//<?= $_SERVER['SERVER_NAME']; ?>/admin/styles/js/html5shiv.js"></script>
            <script src="//<?= $_SERVER['SERVER_NAME']; ?>/admin/styles/js/respond.min.js"></script>
            <![endif]-->
        </head>

        <body class="signin">


        <section>

            <div class="panel panel-signin">
                <div class="panel-body">
                    <div class="logo text-center">
                        <img src="//<?= $_SERVER['SERVER_NAME']; ?>/admin/styles/images/logo-primary.png" alt="Chain Logo">
                    </div>
                    <br/>
                    <h4 class="text-center mb5">Регистрация в системе</h4>
                    <p class="text-center">Создайте свой аккаунт</p>

                    <div class="mb30"></div>
                    <?php if (!empty($arErrors)) { ?>
                        <div class="alert alert-danger">
                            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                            <?php echo implode('<br>', $arErrors); ?>
                        </div>
                    <?php } ?>

                    <form action="register.php" method="post">
                        <input type="hidden" name="send" value="Y">
                        <div class="input-group mb15">
                            <span class="input-group-addon"><i class="glyphicon glyphicon-user"></i></span>
                            <input value="<?php echo $_REQUEST['login']; ?>" type="text" class="form-control" name="login" placeholder="Логин">
                        </div><!-- input-group -->
                        <div class="input-group mb15">
                            <span class="input-group-addon"><i class="glyphicon glyphicon-user"></i></span>
                            <input value="<?php echo $_REQUEST['name']; ?>" type="text" class="form-control" name="name" placeholder="Имя">
                        </div><!-- input-group -->
                        <div class="input-group mb15">
                            <span class="input-group-addon"><i class="glyphicon glyphicon-user"></i></span>
                            <input value="<?php echo $_REQUEST['email']; ?>" type="text" class="form-control" name="email" placeholder="E-Mail">
                        </div><!-- input-group -->
                        <div class="input-group mb15">
                            <span class="input-group-addon"><i class="glyphicon glyphicon-lock"></i></span>
                            <input value="<?php echo $_REQUEST['pass']; ?>" type="text" class="form-control" name="pass" placeholder="Пароль">
                        </div><!-- input-group -->

                        <div class="clearfix">
                            <div class="pull-right">
                                <button type="submit" class="btn btn-success">Регистрация <i class="fa fa-angle-right ml5"></i>
                                </button>
                            </div>
                        </div>
                    </form>

                </div><!-- panel-body -->
                <div class="panel-footer">
                    <a href="signup" class="btn btn-primary btn-block">Войти в систему</a>
                </div><!-- panel-footer -->
            </div><!-- panel -->

        </section>

        <script src="//<?= $_SERVER['SERVER_NAME']; ?>/admin/styles/js/jquery-1.11.1.min.js"></script>
        <script src="//<?= $_SERVER['SERVER_NAME']; ?>/admin/styles/js/jquery-migrate-1.2.1.min.js"></script>
        <script src="//<?= $_SERVER['SERVER_NAME']; ?>/admin/styles/js/bootstrap.min.js"></script>
        <script src="//<?= $_SERVER['SERVER_NAME']; ?>/admin/styles/js/modernizr.min.js"></script>
        <script src="//<?= $_SERVER['SERVER_NAME']; ?>/admin/styles/js/pace.min.js"></script>
        <script src="//<?= $_SERVER['SERVER_NAME']; ?>/admin/styles/js/retina.min.js"></script>
        <script src="//<?= $_SERVER['SERVER_NAME']; ?>/admin/styles/js/jquery.cookies.js"></script>

        <script src="//<?= $_SERVER['SERVER_NAME']; ?>/admin/styles/js/custom.js"></script>

        </body>
        </html>
    <?php } ?>