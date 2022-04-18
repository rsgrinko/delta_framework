<?php
    use Core\Models\User;
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
                    <li>Список пользователей</li>
                </ul>
                <h4>Список пользователей</h4>
            </div>
        </div><!-- media -->
    </div><!-- pageheader -->

    <div class="contentpanel">
<!-- table -->
        <div class="row">
            <div class="col-md-12r">
                <div class="table-responsive">
                    <table class="table table-primary table-hover mb30">
                        <thead>
                        <tr>
                            <th>ID</th>
                            <th>Логин</th>
                            <th>Пароль</th>
                            <th>Права</th>
                            <th>Имя</th>
                            <th>E-Mail</th>
                            <th>Изображение</th>
                            <th>Токен</th>
                            <th>Был активен</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php
                        $arUsers = User::getUsers();
                        foreach($arUsers as $elUser){
                        ?>
                        <tr>
                            <td><?=$elUser['id'];?></td>
                            <td><?=$elUser['login'];?></td>
                            <td><?=$elUser['password'];?></td>
                            <td><?=$elUser['access_level'];?></td>
                            <td><?=$elUser['name'];?></td>
                            <td><?=$elUser['email'];?></td>
                            <td><?=$elUser['image'];?></td>
                            <td><?=$elUser['token'];?></td>
                            <td><?=date('d.m.Y H:i:s', $elUser['last_active']);?></td>
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