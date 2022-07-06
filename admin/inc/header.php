<?php

    use Core\Models\User;

    require_once __DIR__ . '/bootstrap.php';
    global $USER, $arUser;

    if (!User::isAuthorized() && $_SERVER['REQUEST_URI'] !== '/login.php') {
        header('Location: login.php');
        die();
    } elseif (!$USER->isAdmin()) {
        header('Location: ../');
        die();
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>Delta - CPanel</title>

    <link href="//<?=$_SERVER['SERVER_NAME']; ?>/admin/styles/css/style.default.css" rel="stylesheet">

    <!-- HTML5 shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!--[if lt IE 9]>
    <script src="//<?=$_SERVER['SERVER_NAME']; ?>/admin/styles/js/html5shiv.js"></script>
    <script src="//<?=$_SERVER['SERVER_NAME']; ?>/admin/styles/js/respond.min.js"></script>
    <![endif]-->
</head>

<body>

<header>
    <div class="headerwrapper">
        <div class="header-left">
            <a href="index.php" class="logo">
                <img src="//<?=$_SERVER['SERVER_NAME']; ?>/admin/styles/images/logo.png" alt=""/>
            </a>
            <div class="pull-right">
                <a href="" class="menu-collapse">
                    <i class="fa fa-bars"></i>
                </a>
            </div>
        </div><!-- header-left -->

        <div class="header-right">

            <div class="pull-right">

                <div class="btn-group btn-group-list btn-group-notification">
                    <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
                        <i class="fa fa-bell-o"></i>
                        <span class="badge">5</span>
                    </button>
                    <div class="dropdown-menu pull-right">
                        <a href="" class="link-right"><i class="fa fa-search"></i></a>
                        <h5>Notification</h5>
                        <ul class="media-list dropdown-list">
                            <li class="media">
                                <img class="img-circle pull-left noti-thumb"
                                     src="//<?=$_SERVER['SERVER_NAME']; ?>/admin/styles/images/photos/user1.png" alt="">
                                <div class="media-body">
                                    <strong>Nusja Nawancali</strong> likes a photo of you
                                    <small class="date"><i class="fa fa-thumbs-up"></i> 15 minutes ago</small>
                                </div>
                            </li>
                            <li class="media">
                                <img class="img-circle pull-left noti-thumb"
                                     src="//<?=$_SERVER['SERVER_NAME']; ?>/admin/styles/images/photos/user2.png" alt="">
                                <div class="media-body">
                                    <strong>Weno Carasbong</strong> shared a photo of you in your <strong>Mobile Uploads</strong> album.
                                    <small class="date"><i class="fa fa-calendar"></i> July 04, 2014</small>
                                </div>
                            </li>
                            <li class="media">
                                <img class="img-circle pull-left noti-thumb"
                                     src="//<?=$_SERVER['SERVER_NAME']; ?>/admin/styles/images/photos/user3.png" alt="">
                                <div class="media-body">
                                    <strong>Venro Leonga</strong> likes a photo of you
                                    <small class="date"><i class="fa fa-thumbs-up"></i> July 03, 2014</small>
                                </div>
                            </li>
                            <li class="media">
                                <img class="img-circle pull-left noti-thumb"
                                     src="//<?=$_SERVER['SERVER_NAME']; ?>/admin/styles/images/photos/user4.png" alt="">
                                <div class="media-body">
                                    <strong>Nanterey Reslaba</strong> shared a photo of you in your <strong>Mobile Uploads</strong> album.
                                    <small class="date"><i class="fa fa-calendar"></i> July 03, 2014</small>
                                </div>
                            </li>
                            <li class="media">
                                <img class="img-circle pull-left noti-thumb"
                                     src="//<?=$_SERVER['SERVER_NAME']; ?>/admin/styles/images/photos/user1.png" alt="">
                                <div class="media-body">
                                    <strong>Nusja Nawancali</strong> shared a photo of you in your <strong>Mobile Uploads</strong> album.
                                    <small class="date"><i class="fa fa-calendar"></i> July 02, 2014</small>
                                </div>
                            </li>
                        </ul>
                        <div class="dropdown-footer text-center">
                            <a href="" class="link">See All Notifications</a>
                        </div>
                    </div><!-- dropdown-menu -->
                </div><!-- btn-group -->

                <div class="btn-group btn-group-list btn-group-messages">
                    <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
                        <i class="fa fa-envelope-o"></i>
                        <span class="badge">2</span>
                    </button>
                    <div class="dropdown-menu pull-right">
                        <a href="" class="link-right"><i class="fa fa-plus"></i></a>
                        <h5>New Messages</h5>
                        <ul class="media-list dropdown-list">
                            <li class="media">
                                <span class="badge badge-success">New</span>
                                <img class="img-circle pull-left noti-thumb"
                                     src="//<?=$_SERVER['SERVER_NAME']; ?>/admin/styles/images/photos/user1.png" alt="">
                                <div class="media-body">
                                    <strong>Nusja Nawancali</strong>
                                    <p>Hi! How are you?...</p>
                                    <small class="date"><i class="fa fa-clock-o"></i> 15 minutes ago</small>
                                </div>
                            </li>
                            <li class="media">
                                <span class="badge badge-success">New</span>
                                <img class="img-circle pull-left noti-thumb"
                                     src="//<?=$_SERVER['SERVER_NAME']; ?>/admin/styles/images/photos/user2.png" alt="">
                                <div class="media-body">
                                    <strong>Weno Carasbong</strong>
                                    <p>Lorem ipsum dolor sit amet...</p>
                                    <small class="date"><i class="fa fa-clock-o"></i> July 04, 2014</small>
                                </div>
                            </li>
                            <li class="media">
                                <img class="img-circle pull-left noti-thumb"
                                     src="//<?=$_SERVER['SERVER_NAME']; ?>/admin/styles/images/photos/user3.png" alt="">
                                <div class="media-body">
                                    <strong>Venro Leonga</strong>
                                    <p>Do you have the time to listen to me...</p>
                                    <small class="date"><i class="fa fa-clock-o"></i> July 03, 2014</small>
                                </div>
                            </li>
                            <li class="media">
                                <img class="img-circle pull-left noti-thumb"
                                     src="//<?=$_SERVER['SERVER_NAME']; ?>/admin/styles/images/photos/user4.png" alt="">
                                <div class="media-body">
                                    <strong>Nanterey Reslaba</strong>
                                    <p>It might seem crazy what I'm about to say...</p>
                                    <small class="date"><i class="fa fa-clock-o"></i> July 03, 2014</small>
                                </div>
                            </li>
                            <li class="media">
                                <img class="img-circle pull-left noti-thumb"
                                     src="//<?=$_SERVER['SERVER_NAME']; ?>/admin/styles/images/photos/user1.png" alt="">
                                <div class="media-body">
                                    <strong>Nusja Nawancali</strong>
                                    <p>Hey I just met you and this is crazy...</p>
                                    <small class="date"><i class="fa fa-clock-o"></i> July 02, 2014</small>
                                </div>
                            </li>
                        </ul>
                        <div class="dropdown-footer text-center">
                            <a href="" class="link">See All Messages</a>
                        </div>
                    </div><!-- dropdown-menu -->
                </div><!-- btn-group -->

                <div class="btn-group btn-group-option">
                    <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
                        <i class="fa fa-caret-down"></i>
                    </button>
                    <ul class="dropdown-menu pull-right" role="menu">
                        <li><a href="#"><i class="glyphicon glyphicon-user"></i> My Profile</a></li>
                        <li><a href="#"><i class="glyphicon glyphicon-star"></i> Activity Log</a></li>
                        <li><a href="#"><i class="glyphicon glyphicon-cog"></i> Account Settings</a></li>
                        <li><a href="#"><i class="glyphicon glyphicon-question-sign"></i> Help</a></li>
                        <li class="divider"></li>
                        <li><a href="//<?=$_SERVER['SERVER_NAME']; ?>/admin/logout.php"><i class="glyphicon glyphicon-log-out"></i>Выход</a>
                        </li>
                    </ul>
                </div><!-- btn-group -->

            </div><!-- pull-right -->

        </div><!-- header-right -->

    </div><!-- headerwrapper -->
</header>

<section>
    <div class="mainwrapper">
        <div class="leftpanel">
            <div class="media profile-left">
                <a class="pull-left profile-thumb" href="profile.php">
                    <img class="img-circle" src="<?php
                        $image = $USER->getImage();
                        if (!empty($image)) {
                            echo $image['path'];
                        } else {
                            echo '/uploads/users/system.png';
                        }
                        ?>" alt="">
                </a>
                <div class="media-body">
                    <h4 class="media-heading"><?=$arUser['name']; ?></h4>
                    <small class="text-muted"><?=$arUser['email'];?>
                    </small>
                </div>
            </div><!-- media -->

            <h5 class="leftpanel-title">Навигация</h5>
            <ul class="nav nav-pills nav-stacked">
                <li><a href="/admin/"><i class="fa fa-home"></i> <span>Дашбоард</span></a></li>
                <li><a href="/admin/users.php"><i class="fa fa-home"></i> <span>Список пользователей</span></a></li>
                <li><a href="/admin/groups.php"><i class="fa fa-home"></i> <span>Список ролей</span></a></li>
                <li><a href="/admin/cacheInfo.php"><i class="fa fa-home"></i> <span>Кэширование</span></a></li>
                <li><a href="/admin/threads.php"><i class="fa fa-home"></i> <span>Диспетчер очереди</span></a></li>
                <li><a href="/admin/phpcmd.php"><i class="fa fa-home"></i> <span>Командная PHP строка</span></a></li>
                <li><a href="index.html"><i class="fa fa-home"></i> <span>Dashboard</span></a></li>


            </ul>

        </div><!-- leftpanel -->

        <div class="mainpanel">
