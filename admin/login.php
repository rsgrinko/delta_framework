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

    use Core\Helpers\Captcha;
    use Core\Helpers\SystemFunctions;
    use Core\SystemConfig;
    use Core\Models\{User, UserMeta};
    use Core\CoreException;

    require_once __DIR__ . '/inc/bootstrap.php';

    // Определяем тип авторизации
    $authType = isset($_REQUEST['authType']) && !empty($_REQUEST['authType']) ? $_REQUEST['authType'] : 'default';


    /**
     * array(7) {
    ["id"]=> string(7) "1831337"
    ["first_name"]=> string(18) "Александр"
    ["last_name"]=> string(16) "Менщиков"
    ["username"]=> string(5) "n0str"
    ["photo_url"]=> string(36) "https://t.me/i/userpic/100/n0str.jpg"
    ["auth_date"]=> string(10) "1518168109"
    ["hash"]=> string(64) "abba<..>1345"
    }
     */
    switch ($authType) {
        case 'telegram':
            $data = [
                'id'         => $_REQUEST['id'],
                'first_name' => $_REQUEST['first_name'],
                'last_name'  => $_REQUEST['last_name'],
                'username'   => $_REQUEST['username'],
                'photo_url'  => $_REQUEST['photo_url'],
                'auth_date'  => $_REQUEST['auth_date'],
                'hash'       => $_REQUEST['hash'],
            ];
            $isCorrectTelegramAuth = SystemFunctions::checkTelegramAuthorization($data);
            if ($isCorrectTelegramAuth) {
                $res = UserMeta::getListByParams(['name' => 'telegramId', 'value' => $data['id']]);
                if (!empty($res)) {
                    $userId = array_shift($res)['user_id'];
                    User::authorize($userId);
                    header('Location: index.php');
                    die();
                }
            }
            $auth = false;
            $err_mess = true;
            break;
        case 'default':
            $captchaCorrect = true;
            if (USE_CAPTCHA) {
                $captchaCorrect = isset($_REQUEST['captchaCode']) && Captcha::isValidCaptcha($_REQUEST['captchaCode']);
            }

            if (User::isAuthorized()) {
                $auth = true;
            } else {
                $auth = false;
                if ($captchaCorrect && !empty($_REQUEST['login'] && !empty($_REQUEST['pass']))) {
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
            break;

    }



    if ($auth === true) {
        global $USER;
        if (isset($_REQUEST['loginAs']) && !empty($_REQUEST['loginAs']) && $USER->isAdmin()) {
            try {
                User::logout();
                User::authorize($_REQUEST['loginAs']);
                die();
            } catch (CoreException $e) {
                die('Авторизация не удалась');
            }
        }
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
            <script src="//<?= $_SERVER['SERVER_NAME']; ?>/admin/styles/js/html5shiv.js"></script>
            <script src="//<?= $_SERVER['SERVER_NAME']; ?>/admin/styles/js/respond.min.js"></script>
            <![endif]-->
            <style>
                .telegramAuthButtonBox {
                    margin-top: 20px;
                    float: right;
                }
            </style>
        </head>

        <body class="signin">
        <section>
            <div class="panel panel-signin">
                <div class="panel-body">
                    <div class="logo text-center">
                        <img src="//<?= $_SERVER['SERVER_NAME']; ?>/admin/styles/images/logo-primary.png" alt="Chain Logo">
                    </div>
                    <br/>
                    <h4 class="text-center mb5">Авторизация в системе</h4>
                    <p class="text-center">Войдите в свой аккаунт</p>

                    <div class="mb30"></div>
                    <?php if ($err_mess) { ?>
                        <div class="alert alert-danger">
                            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                            Ошибка авторизации
                        </div>
                    <?php } ?>

                    <form action="login.php" method="post">
                        <input type="hidden" name="authType" value="default">
                        <div class="input-group mb15">
                            <span class="input-group-addon"><i class="glyphicon glyphicon-user"></i></span>
                            <input type="text" class="form-control" name="login" placeholder="Имя пользователя">
                        </div><!-- input-group -->

                        <div class="input-group mb15">
                            <span class="input-group-addon"><i class="glyphicon glyphicon-lock"></i></span>
                            <input type="password" class="form-control" name="pass" placeholder="Пароль">
                        </div><!-- input-group -->

                        <?php if (USE_CAPTCHA) { ?>
                            <div class="input-group mb15">
                                <span class="input-group-addon"><i class="glyphicon glyphicon-qrcode"></i></span>
                                <img id="captchaCode" src="<?= SITE_URL ?>/core/captcha.php">
                                <span id="updateCaptcha" style="margin-left: 37%;cursor: pointer;">Обновить код</span>
                            </div><!-- input-group -->

                            <div class="input-group mb15">
                                <span class="input-group-addon"><i class="glyphicon glyphicon-qrcode"></i></span>
                                <input type="text" class="form-control" name="captchaCode" placeholder="Код с картинки">
                            </div><!-- input-group -->

                        <?php } ?>

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

                    <h4 class="text-center mb5">Вход через сторонний сервис</h4>
                    <p class="text-center">Вы можете использовать для авторизации один из вариантов ниже</p>

                    <div class="telegramAuthButtonBox">
                    <script async src="https://telegram.org/js/telegram-widget.js?22" data-telegram-login="<?= SystemConfig::getValue('TELEGRAM_BOT_USERNAME') ?>" data-size="large" data-onauth="onTelegramAuth(user)" data-request-access="write"></script>
                    <script type="text/javascript">
                        function onTelegramAuth(user) {
                            document.location.href = 'login.php?authType=telegram&hash='+ user.hash
                                                    + '&id=' + user.id
                                                    + '&auth_date=' + user.auth_date
                                                    + '&first_name=' + user.first_name
                                                    + '&last_name=' + user.last_name
                                                    + '&photo_url=' + user.photo_url
                                                    + '&username=' + user.username
                        }
                    </script>
                    </div>

                </div><!-- panel-body -->
                <div class="panel-footer">
                    <a href="register.php" class="btn btn-primary btn-block">Регистрация в системе</a>
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
        <?php if (USE_CAPTCHA) { ?>
            <script>
                $(document).ready(function () {
                    let $captchaImage = $('#captchaCode'),
                        $captchaSrc   = $captchaImage.attr('src'),
                        $updateBtn    = $('#updateCaptcha');
                    $updateBtn.click(function () {
                        $captchaImage.attr('src', $captchaSrc + `?v=${new Date().getTime()}`);
                    });
                });
            </script>
        <?php } ?>
        </body>
        </html>
    <?php } ?>