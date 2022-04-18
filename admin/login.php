<?php

    use Core\Models\User;

    require_once __DIR__ . '/inc/bootstrap.php';

    if (User::isAuthorized()) {
        $auth = true;
    } else {
        $auth = false;
        if (!empty($_REQUEST['login'] && !empty($_REQUEST['pass']))) {

            if (User::securityAuthorize(
                $_REQUEST['login'],
                $_REQUEST['pass'],
                isset($_REQUEST['remember']) && $_REQUEST['remember'] === 'on'
            )) {
                $auth = true;
            }
        }
    }
    if ($auth === false && isset($_REQUEST['login']) && $_REQUEST['login'] !== '') {
        $err_mess = true;
    } else {
        $err_mess = false;
    }

    if ($auth === true) {
        header('Location: index.php');
    } else {
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="utf-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
            <meta name="description" content="">
            <meta name="author" content="">

            <title>Delta Framework - Авторизация</title>

            <link href="//<?php echo $_SERVER['SERVER_NAME']; ?>/admin/styles/css/style.default.css" rel="stylesheet">

            <!-- HTML5 shim and Respond.js IE8 support of HTML5 elements and media queries -->
            <!--[if lt IE 9]>
            <script src="//<?=$_SERVER['SERVER_NAME']; ?>/admin/styles/js/html5shiv.js"></script>
            <script src="//<?=$_SERVER['SERVER_NAME']; ?>/admin/styles/js/respond.min.js"></script>
            <![endif]-->
        </head>

        <body class="signin">


        <section>

            <div class="panel panel-signin">
                <div class="panel-body">
                    <div class="logo text-center">
                        <img src="//<?=$_SERVER['SERVER_NAME']; ?>/admin/styles/images/logo-primary.png" alt="Chain Logo">
                    </div>
                    <br/>
                    <h4 class="text-center mb5">Авторизация в системе</h4>
                    <p class="text-center">Войдите в свой аккаунт</p>

                    <div class="mb30"></div>
                    <?php if($err_mess) { ?>
                        <div class="alert alert-danger">
                            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                            Ошибка авторизации
                        </div>
                    <?php } ?>

                    <form action="login.php" method="post">
                        <div class="input-group mb15">
                            <span class="input-group-addon"><i class="glyphicon glyphicon-user"></i></span>
                            <input type="text" class="form-control" name="login" placeholder="Имя пользователя">
                        </div><!-- input-group -->
                        <div class="input-group mb15">
                            <span class="input-group-addon"><i class="glyphicon glyphicon-lock"></i></span>
                            <input type="password" class="form-control" name="pass" placeholder="Пароль">
                        </div><!-- input-group -->

                        <div class="clearfix">
                            <div class="pull-left">
                                <div class="ckbox ckbox-primary mt10">
                                    <input type="checkbox" name="remember" id="rememberMe" value="on">
                                    <label for="rememberMe">Запомнить меня</label>
                                </div>
                            </div>
                            <div class="pull-right">
                                <button type="submit" class="btn btn-success">Войти <i class="fa fa-angle-right ml5"></i></button>
                            </div>
                        </div>
                    </form>

                </div><!-- panel-body -->
                <div class="panel-footer">
                    <a href="signup" class="btn btn-primary btn-block">Регистрация в системе</a>
                </div><!-- panel-footer -->
            </div><!-- panel -->

        </section>

        <script src="//<?=$_SERVER['SERVER_NAME']; ?>/admin/styles/js/jquery-1.11.1.min.js"></script>
        <script src="//<?=$_SERVER['SERVER_NAME']; ?>/admin/styles/js/jquery-migrate-1.2.1.min.js"></script>
        <script src="//<?=$_SERVER['SERVER_NAME']; ?>/admin/styles/js/bootstrap.min.js"></script>
        <script src="//<?=$_SERVER['SERVER_NAME']; ?>/admin/styles/js/modernizr.min.js"></script>
        <script src="//<?=$_SERVER['SERVER_NAME']; ?>/admin/styles/js/pace.min.js"></script>
        <script src="//<?=$_SERVER['SERVER_NAME']; ?>/admin/styles/js/retina.min.js"></script>
        <script src="//<?=$_SERVER['SERVER_NAME']; ?>/admin/styles/js/jquery.cookies.js"></script>

        <script src="//<?=$_SERVER['SERVER_NAME']; ?>/admin/styles/js/custom.js"></script>

        </body>
        </html>
    <?php } ?>