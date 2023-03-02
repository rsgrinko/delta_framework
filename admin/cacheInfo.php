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

    use Core\Helpers\{Cache, SystemFunctions};

    require_once __DIR__ . '/inc/header.php';

    global $USER;
    if ($USER->isAdmin() === false) {
        header('Location: ./');
        die();
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
                    <li>Статистика использования кэша</li>
                </ul>
                <h4>Статистика использования кэша</h4>
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
                            <th>Количество обращений</th>
                            <th>Запись</th>
                            <th>Чтение</th>
                            <th>Папка кэша</th>
                            <th>В кэше</th>
                            <th>Размер</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php
                            $arCacheInfo = Cache::getCacheInfo();
                        ?>
                        <tr>
                            <td><?= $arCacheInfo['countAll'] . ' ' . SystemFunctions::numWord($arCacheInfo['countAll'], ['раз', 'раза', 'раз']
                                ); ?></td>
                            <td><?= $arCacheInfo['countWrite'] . ' ' . SystemFunctions::numWord($arCacheInfo['countWrite'], ['раз', 'раза', 'раз']
                                ); ?></td>
                            <td><?= $arCacheInfo['countRead'] . ' ' . SystemFunctions::numWord($arCacheInfo['countRead'], ['раз', 'раза', 'раз']
                                ); ?></td>
                            <td><?= $arCacheInfo['cacheDir']; ?></td>
                            <td><?= $arCacheInfo['cachedCount'] . ' ' . SystemFunctions::numWord(
                                    $arCacheInfo['cachedCount'],
                                    ['элемент', 'элемента', 'элементов']
                                ); ?></td>
                            <td><?= SystemFunctions::convertBytes($arCacheInfo['size']); ?></td>
                        </tr>
                        </tbody>
                    </table>
                </div><!-- table-responsive -->
            </div>
        </div>
        <!-- end table -->

    </div><!-- contentpanel -->
<?php require_once __DIR__ . '/inc/footer.php'; ?>