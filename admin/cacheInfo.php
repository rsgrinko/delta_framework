<?php
    use Core\Helpers\{Cache, SystemFunctions};
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
                                    <td><?=$arCacheInfo['countAll'] . ' ' . SystemFunctions::numWord($arCacheInfo['countAll'], ['раз', 'раза', 'раз']);?></td>
                                    <td><?=$arCacheInfo['countWrite'] . ' ' . SystemFunctions::numWord($arCacheInfo['countWrite'], ['раз', 'раза', 'раз']);?></td>
                                    <td><?=$arCacheInfo['countRead'] . ' ' . SystemFunctions::numWord($arCacheInfo['countRead'], ['раз', 'раза', 'раз']);?></td>
                                    <td><?=$arCacheInfo['cacheDir'];?></td>
                                    <td><?=$arCacheInfo['cachedCount'] . ' ' . SystemFunctions::numWord($arCacheInfo['cachedCount'], ['элемент', 'элемента', 'элементов']);?></td>
                                    <td><?=SystemFunctions::convertBytes($arCacheInfo['size']);?></td>
                                </tr>
                        </tbody>
                    </table>
                </div><!-- table-responsive -->
            </div>
        </div>
        <!-- end table -->

    </div><!-- contentpanel -->
<?php require_once __DIR__ . '/inc/footer.php'; ?>