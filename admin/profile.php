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

    use Core\Models\{User, MQ};

    require_once __DIR__ . '/inc/header.php';

    use Core\Models\File;
    use Core\Helpers\Thumbs;

    global $USER;

    if (isset($_REQUEST['userId']) && !empty($_REQUEST['userId'])) {
        $objectUser = new User($_REQUEST['userId']);
    } else {
        $objectUser = $USER;
    }
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
                            $newUserFields['image_id'] = $File->getId();

                            $image = new Thumbs($_SERVER['DOCUMENT_ROOT'] . $File->getPath());
                            $image->thumb(300, 300);
                            $image->watermark(UPLOADS_PATH . '/watermark_small.png', 'bottom-right', 50);
                            $image->save();
                        }

                        $objectUser->update($newUserFields);
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
                        <h4 class="panel-title"><?= $objectUser->getName(); ?></h4>
                        <p><?= $objectUser->getAllUserData()['date_created']; ?></p>
                    </div><!-- panel-heading -->

                    <div class="panel-body nopadding">

                        <form class="form-horizontal form-bordered" method="POST" action="profile.php" enctype="multipart/form-data">
                            <input type="hidden" name="userId" value="<?= $objectUser->getId(); ?>">
                            <div class="form-group">
                                <label class="col-sm-4 control-label">Логин</label>
                                <div class="col-sm-8">
                                    <input type="text" name="login" value="<?= $objectUser->getAllUserData()['login']; ?>" class="form-control"
                                           disabled>
                                </div>
                            </div><!-- form-group -->

                            <div class="form-group">
                                <label class="col-sm-4 control-label">Имя</label>
                                <div class="col-sm-8">
                                    <input type="text" name="name" value="<?= $objectUser->getAllUserData()['name']; ?>" class="form-control">
                                </div>
                            </div><!-- form-group -->

                            <div class="form-group">
                                <label class="col-sm-4 control-label">E-Mail</label>
                                <div class="col-sm-8">
                                    <input type="text" name="email" value="<?= $objectUser->getAllUserData()['email']; ?>" class="form-control">
                                </div>
                            </div><!-- form-group -->

                            <div class="form-group">
                                <label class="col-sm-4 control-label">Фотография</label>
                                <div class="col-sm-1"><img width="100px"
                                                           src="<?php
                                                               $image = $objectUser->getImage();
                                                               if (!empty($image)) {
                                                                   echo $image['path'];
                                                               } else {
                                                                   echo '/uploads/users/system.png';
                                                               }
                                                           ?>"></div>
                                <div class="col-sm-7">

                                    <input type="file" name="image" class="form-control">
                                </div>
                            </div><!-- form-group -->

                            <div class="form-group">
                                <label class="col-sm-4 control-label">Токен</label>
                                <div class="col-sm-8">
                                    <input type="text" name="token" value="<?= $objectUser->getAllUserData()['token']; ?>" class="form-control"
                                           disabled>
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