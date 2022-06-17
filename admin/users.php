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
                            <th>Имя</th>
                            <th>E-Mail</th>
                            <th>Изображение</th>
                            <th>Роли</th>
                            <th>Токен</th>
                            <th>Был активен</th>
                            <th>Создан</th>
                            <th>Обновлен</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php
                        $arUsers = User::getUsers();
                        foreach($arUsers as $elUser){
                            $userObject = new User($elUser['id']);
                        ?>
                        <tr>
                            <td><?=$elUser['id'];?></td>
                            <td><?=$elUser['login'];?></td>
                            <td><?=$elUser['password'];?></td>
                            <td><?=$elUser['name'];?></td>
                            <td><?=$elUser['email'];?></td>
                            <td><?=$elUser['image'] ? '<img src="'.$elUser['image'].'" width="50px">' : ''; ?></td>
                            <td><?php
                                    $arRoles = [];
                                    foreach($userObject->getRolesObject()->getFullGroup() as $userRole) {
                                        $arRoles[] = $userRole['name'] . ' (' . $userRole['id'] . ')';
                                    }
                                    echo implode(', ', $arRoles);
                                ?></td>
                            <td><?=$elUser['token'];?></td>
                            <td><?=date('d.m.Y H:i:s', $elUser['last_active']);?></td>
                            <td><?=$elUser['date_created'] ? date('d.m.Y H:i:s', strtotime($elUser['date_created'])) : '-';?></td>
                            <td><?=$elUser['date_updated'] ? date('d.m.Y H:i:s', strtotime($elUser['date_updated'])) : '-';?></td>
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