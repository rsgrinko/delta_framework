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
                    <li>Список Групп</li>
                </ul>
                <h4>Список групп</h4>
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
                            $arGroups = Roles::getAllGroups();
                            foreach($arGroups as $group){
                                ?>
                                <tr>
                                    <td><?=$group['id'];?></td>
                                    <td><?=$group['name'];?></td>
                                    <td><?=$group['description'];?></td>
                                    <td><?=$group['date_created'] ? date('d.m.Y H:i:s', strtotime($group['date_created'])) : '-';?></td>
                                    <td><?=$group['date_updated'] ? date('d.m.Y H:i:s', strtotime($group['date_updated'])) : '-';?></td>
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