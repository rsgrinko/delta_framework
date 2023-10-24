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
    use Core\Models\File;
    use Core\Models\Posts;
    use Core\Models\User;
    use Core\SystemConfig;

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
                    <li>Список записей</li>
                </ul>
                <h4>Список записей</h4>
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
                            <th>Заголовок</th>
                            <th>Текст</th>
                            <th>Автор</th>
                            <th>Картинка</th>
                            <th>Создан</th>
                            <th>Обновлен</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php
                            $objectPosts = new Posts();
                            Pagination::execute($_REQUEST['page'], $objectPosts->getAllCount(), SystemConfig::getValue('PAGINATION_LIMIT'));
                            $limit = Pagination::getLimit();

                            $arPosts = $objectPosts->getPosts($limit);
                            foreach ($arPosts as $elPost) {
                                ?>
                                <tr>
                                    <td><?= $elPost['id'] ?></a></td>
                                    <td><?= $elPost['title'] ?></td>
                                    <td><?= htmlspecialchars($elPost['text']) ?></td>
                                    <td><?= User::getInfoById($elPost['user_id']) ?></td>
                                    <td><?php
                                            $image = (new File($elPost['image_id']))->getAllProps();
                                            if(!empty($image)) {
                                                echo '<img src="' . $image['path'] . '" alt="' . $image['name'] . '" width="50px">';
                                            }
                                            ?></td>

                                    <td><?= $elPost['date_created'] ? date('d.m.Y H:i:s', strtotime($elPost['date_created'])) : '-'; ?></td>
                                    <td><?= $elPost['date_updated'] ? date('d.m.Y H:i:s', strtotime($elPost['date_updated'])) : '-'; ?></td>
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