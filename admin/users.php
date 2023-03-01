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

    use Core\Helpers\Pagination;
    use Core\Models\User;

    require_once __DIR__ . '/inc/header.php';
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
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php
                            Pagination::execute($_REQUEST['page'], User::getAllCount(), PAGINATION_LIMIT);
                            $limit = Pagination::getLimit();

                            $arUsers = User::getUsers($limit);
                            foreach ($arUsers as $elUser) {
                                $userObject = new User($elUser['id']);
                                ?>
                                <tr>
                                    <td><a href="profile.php?userId=<?= $elUser['id']; ?>"><?= $elUser['id']; ?></a></td>
                                    <td><?= $elUser['login']; ?></td>
                                    <td><?= $elUser['password']; ?></td>
                                    <td><?= $elUser['name']; ?></td>
                                    <td><?= $elUser['email']; ?> <?=$userObject->isEmailConfirmed() ? '(Подтвержден)' : '' ?></td>
                                    <td><?php
                                            $image = $userObject->getImage();
                                            if (!empty($image)) {
                                                echo '<img src="' . $image['path'] . '" alt="' . $image['name'] . '" width="50px">';
                                            }
                                        ?></td>
                                    <td><?php
                                            $arRoles = [];
                                            foreach ($userObject->getRolesObject()->getFullRoles() as $userRole) {
                                                $arRoles[] = $userRole['name'] . ' (' . $userRole['id'] . ')';
                                            }
                                            echo implode(', ', $arRoles);
                                        ?></td>
                                    <td><?= $elUser['token']; ?></td>
                                    <td><?= date('d.m.Y H:i:s', $elUser['last_active']); ?></td>
                                    <td><?= $elUser['date_created'] ? date('d.m.Y H:i:s', strtotime($elUser['date_created'])) : '-'; ?></td>
                                    <td><?= $elUser['date_updated'] ? date('d.m.Y H:i:s', strtotime($elUser['date_updated'])) : '-'; ?></td>
                                    <td><?php if ($USER->isAdmin()) { ?><a class="btn btn-primary" href="login.php?loginAs=<?= $elUser['id']; ?>">Войти</a> <?php } ?></td>
                                </tr>
                                <?php
                            }
                        ?>
                        </tbody>
                    </table>
                </div><!-- table-responsive -->
                <?php Pagination::show('page') ?>
            </div>
        </div>
        <!-- end table -->
    </div><!-- contentpanel -->
<?php require_once __DIR__ . '/inc/footer.php'; ?>