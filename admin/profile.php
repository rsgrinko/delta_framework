<?php

    use Core\Models\{User, MQ};

    require_once __DIR__ . '/inc/header.php';

    use Core\Models\File;

    global $USER;

?>
    <div class="pageheader">
        <div class="media">
            <div class="pageicon pull-left">
                <i class="fa fa-home"></i>
            </div>
            <div class="media-body">
                <ul class="breadcrumb">
                    <li><a href=""><i class="glyphicon glyphicon-home"></i></a></li>
                    <li>Профиль пользователя</li>
                </ul>
                <h4>Профиль пользователя</h4>
            </div>
        </div><!-- media -->
    </div><!-- pageheader -->

    <div class="contentpanel">
        <?php if (isset($_REQUEST['save'])) { ?>
            <div class="row">
                <div class="col-md-12">
                    <?php
                        $newUserFields = ['name' => $_POST['name'], 'email' => $_POST['email']];
                        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                            $File = new File();
                            $File->saveFile($_FILES['image']['tmp_name'], $_FILES['image']['name'], true);
                            $newUserFields['image'] = $File->getPath();
                        }

                        $USER->update($newUserFields);
                    ?>
                    <div class="alert alert-success">
                        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                        Профиль успешно обновлен
                    </div>
                </div>
            </div>
        <?php } ?>
        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h4 class="panel-title"><?= $USER->getName(); ?></h4>
                        <p><?= $USER->getAllUserData()['date_created']; ?></p>
                    </div><!-- panel-heading -->

                    <div class="panel-body nopadding">

                        <form class="form-horizontal form-bordered" method="POST" action="profile.php" enctype="multipart/form-data">
                            <div class="form-group">
                                <label class="col-sm-4 control-label">Логин</label>
                                <div class="col-sm-8">
                                    <input type="text" name="login" value="<?= $USER->getAllUserData()['login']; ?>" class="form-control" disabled>
                                </div>
                            </div><!-- form-group -->

                            <div class="form-group">
                                <label class="col-sm-4 control-label">Имя</label>
                                <div class="col-sm-8">
                                    <input type="text" name="name" value="<?= $USER->getAllUserData()['name']; ?>" class="form-control">
                                </div>
                            </div><!-- form-group -->

                            <div class="form-group">
                                <label class="col-sm-4 control-label">E-Mail</label>
                                <div class="col-sm-8">
                                    <input type="text" name="email" value="<?= $USER->getAllUserData()['email']; ?>" class="form-control">
                                </div>
                            </div><!-- form-group -->

                            <div class="form-group">
                                <label class="col-sm-4 control-label">Фотография</label>
                                <div class="col-sm-1"><img width="100px"
                                                           src="<?= ($USER->getAllUserData()['image'] ?? '/uploads/users/system.png'); ?>"></div>
                                <div class="col-sm-7">

                                    <input type="file" name="image" class="form-control">
                                </div>
                            </div><!-- form-group -->

                            <div class="form-group">
                                <label class="col-sm-4 control-label">Токен</label>
                                <div class="col-sm-8">
                                    <input type="text" name="token" value="<?= $USER->getAllUserData()['token']; ?>" class="form-control" disabled>
                                </div>
                            </div><!-- form-group -->

                            <div class="panel-footer">
                                <input type="submit" name="save" value="Сохранить" class="btn btn-primary">
                            </div>
                        </form>
                    </div><!-- panel-body -->
                </div><!-- panel -->


            </div>


        </div>

    </div><!-- contentpanel -->
<?php require_once __DIR__ . '/inc/footer.php'; ?>