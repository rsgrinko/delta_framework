<?php
    use Core\Models\{User, Roles};
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
                    <li>Список ролей</li>
                </ul>
                <h4>Список ролей</h4>
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
                            <th>Название</th>
                            <th>Описание</th>
                            <th>Создана</th>
                            <th>Обновлена</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php
                            $arRoles = Roles::getAllGroups();
                            foreach($arRoles as $role){
                                ?>
                                <tr>
                                    <td><?= $role['id'];?></td>
                                    <td><?= $role['name'];?></td>
                                    <td><?= $role['description'];?></td>
                                    <td><?= $role['date_created'] ? date('d.m.Y H:i:s', strtotime($role['date_created'])) : '-';?></td>
                                    <td><?= $role['date_updated'] ? date('d.m.Y H:i:s', strtotime($role['date_updated'])) : '-';?></td>
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