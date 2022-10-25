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
    use Core\ExternalServices\RemoteHosts;

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
                    <li>Список удаленных площадок</li>
                </ul>
                <h4>Список площадок</h4>
            </div>
        </div><!-- media -->
    </div><!-- pageheader -->

    <div class="contentpanel">
        <!-- table -->
        <div class="row">
            <div class="col-md-12r">
                <form id="hostsForm">
                <div class="table-responsive">
                    <table class="table table-primary table-hover mb30">
                        <thead>
                        <tr>
                            <th>Выбор</th>
                            <th>ID</th>
                            <th>Статус</th>
                            <th>URL</th>
                            <th>Название</th>
                            <th>Hostname</th>
                            <th>Дата создания</th>
                            <th>Дата обновления</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php
                            Pagination::execute($_REQUEST['page'], RemoteHosts::getAllCount(), PAGINATION_LIMIT);
                            $limit = Pagination::getLimit();

                            $arHosts = RemoteHosts::getHosts($limit);
                            foreach ($arHosts as $elHost) {
                                $hostObject = (new RemoteHosts())->selectHost($elHost['id']);
                                ?>
                                <tr>
                                    <td><input type="checkbox" name="hosts[]" value="<?= $elHost['id'] ?>"></td>
                                    <td><?= $elHost['id'] ?></td>
                                    <td><?php if ($hostObject->isOnline()) { ?>
                                       <span class="badge badge-success">Online</span></td>
                                    <?php } else { ?>
                                        <span class="badge badge-danger">Offline</span></td>
                                    <?php } ?>
                                    <td><?= $elHost['url'] ?></td>
                                    <td><?= $elHost['name'] ?></td>
                                    <td><?= $hostObject->getHostname() ?></td>
                                    <td><?= $elHost['date_created'] ? date('d.m.Y H:i:s', strtotime($elHost['date_created'])) : '-'; ?></td>
                                    <td><?= $elHost['date_updated'] ? date('d.m.Y H:i:s', strtotime($elHost['date_updated'])) : '-'; ?></td>
                                </tr>
                                <?php
                            }
                        ?>
                        </tbody>
                    </table>
                </div><!-- table-responsive -->
                    <select name="cmd" class="form-control">
                        <option value="getUname">uname -a</option>
                        <option value="getUptime">uptime</option>
                        <option value="getHostname">hostname</option>
                        <option value="gethostInfo">Получить информацию о площадке</option>
                    </select>
                    <button class="btn btn-success form-control" id="execute">Выполнить</button>
                </form>
                <div class="result" id="result"></div>
                <?php Pagination::show('page') ?>
            </div>
        </div>
        <!-- end table -->
    </div><!-- contentpanel -->
    <script>
        $(document).ready(function(){
            var resultBox = $('#result');
            $('#hostsForm').on('submit', function (e) {
                e.preventDefault();

                $.post(
                    './ajax/executeRemoteHost.php',
                    $('#hostsForm').serialize(),

                    function(data) {
                        resultBox.html(data);
                    }
                );

            });
        });
    </script>
<?php require_once __DIR__ . '/inc/footer.php'; ?>