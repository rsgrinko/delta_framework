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
                    <li>Командная PHP строка</li>
                </ul>
                <h4>Командная PHP строка</h4>
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
                        <button class="btn btn-primary" type="button" onClick="addUseSection(this, 'use Core\\Models\\DB;');">DB</button>
                        <button class="btn btn-primary" type="button" onClick="addUseSection(this, 'use Core\\Models\\User;');">User</button>
                        <button class="btn btn-primary" type="button" onClick="addUseSection(this, 'use Core\\Models\\MQ;');">MQ</button>
                        <button class="btn btn-primary" type="button" onClick="addUseSection(this, 'use Core\\Helpers\\Cache;');">Cache</button>
                        <button class="btn btn-primary" type="button" onClick="addUseSection(this, 'use Core\\Helpers\\Log;');">Log</button>
                        <button class="btn btn-primary" type="button" onClick="addUseSection(this, 'use Core\\Helpers\\Files;');">Files</button>
                        <button class="btn btn-primary" type="button" onClick="addUseSection(this, 'use Core\\Helpers\\Mail;');">Mail</button>
                        <button class="btn btn-primary" type="button" onClick="addUseSection(this, 'use Core\\Helpers\\Pagination;');">Pagination
                        </button>
                        <button class="btn btn-primary" type="button" onClick="addUseSection(this, 'use Core\\Helpers\\Registry;');">Registry</button>
                        <button class="btn btn-primary" type="button" onClick="addUseSection(this, 'use Core\\Helpers\\SystemFunctions;');">
                            SystemFunctions
                        </button>
                        <button class="btn btn-primary" type="button" onClick="addUseSection(this, 'use Core\\Helpers\\Zip;');">Zip</button>
                        <button class="btn btn-primary" type="button" onClick="addUseSection(this, 'use Core\\CoreException;');">CoreException
                        </button>
                        <button class="btn btn-primary" type="button" onClick="addUseSection(this, 'use Core\\Template;');">Template</button>
                    </div>

                    <div class="mb-3">
                        <label for="query" class="form-label">Введите PHP код для выполнения</label>
                        <textarea class="form-control" rows="8" id="query"
                                  name="query"><?php echo isset($_REQUEST['query']) ? $_REQUEST['query'] : ''; ?></textarea>
                    </div>
                    <div class="mb-3">
                        <input id="use_pre" type="checkbox" name="use_pre" value="Y" <?php if (isset($_REQUEST['use_pre']) and $_REQUEST['use_pre']
                                                                                                                               == 'Y') {
                            echo 'checked';
                        } ?>>
                        <label for="use_pre" class="form-label"> Отображать результат выполнения как текст</label>
                    </div>
                    <div class="mb-3">
                        <input class="btn btn-primary" type="submit" name="btn" value="Выполнить">
                    </div>


                </form>

                <script>
                    /*var editor = CodeMirror.fromTextArea(document.getElementById("query"), {
                        lineNumbers: true
                    });

                    function addUseSection(button, text) {
                        editor.setValue(text + "\n" + editor.getValue());
                        //button.disabled = true;
                        console.log(button);
                    }*/


                    function addUseSection(button, text) {
                        editor = document.getElementById("query");
                        editor.value = text + "\n" + editor.value;
                        //button.disabled = true;
                        console.log(button);
                    }

                </script>

                <?php if (isset($_REQUEST['execute']) and $_REQUEST['execute'] == 'Y') {
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
                        echo '<div class="alert alert-danger"><p><strong>(ParseError)</strong> Ошибка парсинга: ' . $p->getMessage() . '</p></div>';
                    } catch (Throwable $e) {
                        echo '<div class="alert alert-danger"><p><strong>(Throwable)</strong> Ошибка при выполнении: ' . $e->getMessage()
                             . '</p></div>';
                    } catch (Error $e) {
                        echo '<div class="alert alert-danger"><p><strong>(Error)</strong>  Ошибка при выполнении: ' . $e->getMessage() . '</p></div>';
                    }

                    echo '</p>';
                }
                ?>

                <!----->
            </div>
        </div>
        <!-- end table -->
    </div><!-- contentpanel -->
<?php require_once __DIR__ . '/inc/footer.php'; ?>