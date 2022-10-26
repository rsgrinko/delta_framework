<?php

    $timer1 = $timer2 = 0;

    $varTrue  = true;
    $varFalse = false;

    $count = 100000000;


    /**********************/
    $start = microtime(true);
    // Проверка эталона
    for ($i = 0; $i < $count; $i++) {
        if ($varTrue) {
            // пустота
        }
    }
    $end      = microtime(true);
    $deltaOne = $end - $start;

    /**********************/
    $start = microtime(true);
    // Проверка инверсии
    for ($i = 0; $i < $count; $i++) {
        if (!$varFalse) {
            // пустота
        }
    }
    $end      = microtime(true);
    $deltaTwo = $end - $start;
    /**********************/

    $start = microtime(true);
    // Проверка сравнением в булевым
    for ($i = 0; $i < $count; $i++) {
        if ($varFalse === false) {
            // пустота
        }
    }
    $end        = microtime(true);
    $deltaThree = $end - $start;
    /**********************/
?>
<b>Тестирование условий с количеством выполнения <?= $count ?></b><br>
<table border="1px">
    <tr style="font-weight: bold"><td>Тестирование</td><td>Время</td></tr>
    <tr><td>Эталон</td><td><?= $deltaOne ?></td></tr>
    <tr><td>Инверсия</td><td><?= $deltaTwo ?></td></tr>
    <tr><td>Сравнение с bool</td><td><?= $deltaThree ?></td></tr>
</table>