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

    use Core\Models\{User, Roles};

    require_once __DIR__ . '/inc/header.php';

    global $USER;
    if ($USER->isAdmin() === false) {
        header('Location: ./');
        die();
    }

    if (isset($_REQUEST['execute']) && $_REQUEST['execute'] == 'Y') {
        $_SESSION['phpcmd_query'] = $_REQUEST['query'];

        if (isset($_REQUEST['save']) and $_REQUEST['save'] == 'Y') {
            $arQuery = explode(PHP_EOL, $_REQUEST['query']);
            if (stristr($arQuery[0], '//title: ')) {
                $queryName = str_replace('//title: ', '', $arQuery[0]);
            } else {
                $queryName = 'PHP код ' . date('d.m H:i');
            }
            $USER->getMetaObject()->addMeta('code', serialize(['name' => $queryName, 'code' => $_REQUEST['query']]));
        }
    }

    if (isset($_REQUEST['delMeta'])) {
        $USER->getMetaObject()->deleteMeta($_REQUEST['delMeta']);
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
                    <li>Командная PHP строка</li>
                </ul>
                <h4>Командная строка</h4>
            </div>
        </div><!-- media -->
    </div><!-- pageheader -->

    <div class="contentpanel">
        <!-- table -->
        <div class="row">
            <div class="col-md-12r">
                <!---->

                <form action="" method="POST">
                    <input type="hidden" name="execute" value="Y">


                    <div class="mb-3">
                        <?php
                            $userCodes = $USER->getMetaObject()->getMetaByName('code');
                            if (!empty($userCodes)) {
                                foreach ($userCodes as $code) {
                                    $element = unserialize($code['value']);
                                    ?>
                                    <div class="phpcmdButton">
                                        <button class="btn btn-primary button-get" type="button"
                                                onClick="addUseSection(this, <?= $code['id'] ?>, '<?= base64_encode(
                                                    $element['code']
                                                ); ?>');"><?= $element['name']; ?></button>
                                        <button class="btn btn-danger button-delete" type="button" onclick="deleteUserCode(<?= $code['id'] ?>)">X
                                        </button>
                                    </div>
                                    <?php
                                }
                            }

                        ?>
                    </div>

                    <div class="mb-3">
                        <label for="query" class="form-label">Введите PHP код для выполнения</label>
                        <textarea class="form-control" rows="8" id="query"
                                  name="query"><?php
                                if (isset($_REQUEST['query']) && !empty($_REQUEST['query'])) {
                                    echo $_REQUEST['query'];
                                } elseif (isset($_SESSION['phpcmd_query']) && !empty($_SESSION['phpcmd_query'])) {
                                    echo $_SESSION['phpcmd_query'];
                                }

                            ?></textarea>
                    </div>
                    <div class="mb-3">
                        <input id="use_pre" type="checkbox" name="use_pre" value="Y" <?php if (isset($_REQUEST['use_pre']) and $_REQUEST['use_pre']
                                                                                                                               == 'Y') {
                            echo 'checked';
                        } ?>>
                        <label for="use_pre" class="form-label"> Отображать результат выполнения как текст</label>
                    </div>
                    <div class="mb-3">

                        <input id="save" type="checkbox" name="save" value="Y">
                        <label for="save" class="form-label"> Сохранить вариант кода</label>
                    </div>
                    <div class="mb-3">
                        <input class="btn btn-primary" type="submit" name="btn" value="Выполнить">
                    </div>


                </form>

                <script>
                    function b64EncodeUnicode(str) {
                        return btoa(encodeURIComponent(str).replace(/%([0-9A-F]{2})/g,
                            function toSolidBytes(match, p1) {
                                return String.fromCharCode('0x' + p1);
                            }));
                    }

                    function b64DecodeUnicode(str) {
                        return decodeURIComponent(atob(str).split('').map(function (c) {
                            return '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2);
                        }).join(''));
                    }

                    function addUseSection(button, id, text) {
                        editor = document.getElementById("query");
                        editor.value = b64DecodeUnicode(text) + "\n";
                    }

                    function deleteUserCode(id) {
                        window.location.href = "phpcmd.php?delMeta=" + id
                    }

                </script>

                <?php
                    if (isset($_REQUEST['execute']) and $_REQUEST['execute'] == 'Y') {
                        echo '<h2 class="entry-title">Результат</h2><p class="exec_result">';

                        $execResult = '';
                        try {
                            ob_start();
                            eval($_REQUEST['query']);
                            $execResult = ob_get_clean();
                            if (isset($_REQUEST['use_pre']) and $_REQUEST['use_pre'] == 'Y') {
                                echo '<pre>' . $execResult . '</pre>';
                            } else {
                                echo $execResult;
                            }
                        } catch (ParseError $p) {
                            echo '<div class="alert alert-danger"><p><strong>(ParseError)</strong> Ошибка парсинга: ' . $p->getMessage()
                                 . '</p></div>';
                        } catch (Throwable $e) {
                            echo '<div class="alert alert-danger"><p><strong>(Throwable)</strong> Ошибка при выполнении: ' . $e->getMessage()
                                 . '</p></div>';
                        } catch (Error $e) {
                            echo '<div class="alert alert-danger"><p><strong>(Error)</strong>  Ошибка при выполнении: ' . $e->getMessage()
                                 . '</p></div>';
                        }

                        echo '</p>';
                    }
                ?>

                <!----->
            </div>
        </div>
        <!-- end table -->
    </div><!-- contentpanel -->
    <style>
        button.btn.btn-primary.button-get {
            border-radius: 3px 0px 0px 3px;
        }

        button.btn.btn-danger.button-delete {
            margin-left: -5px;
            border-radius: 0px 3px 3px 0px;
        }

        .phpcmdButton {
            display: inline-block;
            margin: 3px;
        }
    </style>
<?php require_once __DIR__ . '/inc/footer.php'; ?>