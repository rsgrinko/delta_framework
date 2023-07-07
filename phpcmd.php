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

    die();
    use Core\DataBases\DB;
    use Core\Helpers\{Cache, Files};
    use Core\Models\{User};

    require_once $_SERVER['DOCUMENT_ROOT'] . '/core/bootstrap.php';
    /** @var $USER User */
    global $USER;
?>
<!doctype html>
<html lang="ru">
<head>
    <!-- Обязательные метатеги -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.1/dist/css/bootstrap.min.css">
    <title>Главная тестовая</title>


    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.3/codemirror.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.3/mode/xml/xml.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.3/mode/php/php.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.3/mode/sql/sql.min.js"></script>


    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.3/codemirror.min.css">


</head>
<body>
<main>
    <nav class="navbar navbar-expand-md navbar-dark bg-dark" aria-label="Fourth navbar example">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">Delta Framework</a>
            <button class="navbar-toggler collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#navbarsExample04"
                    aria-controls="navbarsExample04" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="navbar-collapse collapse" id="navbarsExample04" style="">
                <ul class="navbar-nav me-auto mb-2 mb-md-0">
                    <li class="nav-item">
                        <a class="nav-link" aria-current="page" href="/">Главная</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/admin/">{{ADMIN_PANEL_LINK_NAME}}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/?clear_cache=Y">{{CLEAR_CACHE_LINK_NAME}}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="/phpcmd.php">{{PHP_CMD_LINK_NAME}}</a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link disabled" href="#" tabindex="-1" aria-disabled="true">Disabled</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <div class="container">
        <div class="row justify-content-md-center">
            <div class="col-md">

                <h1 class="entry-title">Командная строка PHP</h1>
                <?php if (!empty($USER) && $USER->isAdmin()) { ?>
                    <form action="" method="POST">
                        <input type="hidden" name="execute" value="Y">

                        <div class="mb-3">
                            <button class="btn btn-primary" type="button" onClick="addUseSection(this, 'use Core\\Models\\DB;');">DB</button>
                            <button class="btn btn-primary" type="button" onClick="addUseSection(this, 'use Core\\Models\\User;');">User</button>
                            <button class="btn btn-primary" type="button" onClick="addUseSection(this, 'use Core\\Helpers\\Cache;');">Cache</button>
                            <button class="btn btn-primary" type="button" onClick="addUseSection(this, 'use Core\\Helpers\\Log;');">Log</button>
                            <button class="btn btn-primary" type="button" onClick="addUseSection(this, 'use Core\\Helpers\\Files;');">Files</button>
                            <button class="btn btn-primary" type="button" onClick="addUseSection(this, 'use Core\\Helpers\\Mail;');">Mail</button>
                            <button class="btn btn-primary" type="button" onClick="addUseSection(this, 'use Core\\Helpers\\Pagination;');">
                                Pagination
                            </button>
                            <button class="btn btn-primary" type="button" onClick="addUseSection(this, 'use Core\\Helpers\\Registry;');">Registry
                            </button>
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
                            <input id="use_pre" type="checkbox" name="use_pre" value="Y" <?php if (isset($_REQUEST['use_pre'])
                                                                                                   and $_REQUEST['use_pre'] == 'Y') {
                                echo 'checked';
                            } ?>>
                            <label for="use_pre" class="form-label"> Отображать результат выполнения как текст</label>
                        </div>
                        <div class="mb-3">
                            <input class="btn btn-primary" type="submit" name="btn" value="Выполнить">
                        </div>


                    </form>

                    <script>
                        var editor = CodeMirror.fromTextArea(document.getElementById("query"), {
                            lineNumbers: true
                        });

                        function addUseSection(button, text) {
                            editor.setValue(text + "\n" + editor.getValue());
                            //button.disabled = true;
                            console.log(button);
                        }
                    </script>

                <?php if (isset($_REQUEST['execute']) and $_REQUEST['execute'] == 'Y') {
                    echo '<h2 class="entry-title">Результат</h2><p class="exec_result">';

                    try {
                        if (isset($_REQUEST['use_pre']) and $_REQUEST['use_pre'] == 'Y') {
                            echo '<pre>';
                        }
                        eval($_REQUEST['query']);
                        if (isset($_REQUEST['use_pre']) and $_REQUEST['use_pre'] == 'Y') {
                            echo '</pre>';
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


                <?php } else { ?>
                    <div class="alert alert-danger"><p>Недостаточно прав</p></div>
                <?php } ?>

            </div>
        </div>
    </div>
</main>
<footer class="py-3 my-4">
    <ul class="nav justify-content-center border-bottom pb-3 mb-3">
        <li class="nav-item"><a href="#" class="nav-link px-2 text-muted">Home</a></li>
        <li class="nav-item"><a href="#" class="nav-link px-2 text-muted">Features</a></li>
        <li class="nav-item"><a href="#" class="nav-link px-2 text-muted">Pricing</a></li>
        <li class="nav-item"><a href="#" class="nav-link px-2 text-muted">FAQs</a></li>
        <li class="nav-item"><a href="#" class="nav-link px-2 text-muted">About</a></li>
    </ul>
    <?php
        $finish_time = microtime(true);
        $delta       = round($finish_time - START_TIME, 3);
        if ($delta < 0.001) {
            $delta = 0.001;
        }
        $cacheSize = Cache::getCacheSize();
        echo '<p class="text-center text-muted">Использовано ОЗУ: ' . round(memory_get_usage() / 1024 / 1024, 2) . ' МБ / БД: ' . round(
                DB::$workingTime,
                3
            ) . ' сек (' . DB::$quantity . ' шт.) / Кэш: ' . Files::convertBytes($cacheSize) . ' / Генерация: ' . $delta . ' сек</p>';

    ?>
    <p class="text-center text-muted">© <?= date('Y'); ?> Delta Project</p>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-ka7Sk0Gln4gmtz2MlQnikT1wXgYsOg+OMhuP+IlRH9sENBO0LRn5q+8nbTov4+1p" crossorigin="anonymous"></script>
</body>
</html>
