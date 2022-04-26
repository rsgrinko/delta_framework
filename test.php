<?php
error_reporting(E_ALL);
require_once $_SERVER['DOCUMENT_ROOT'] . '/core/bootstrap.php';
$smarty = new Smarty;

// Включение кэширования.
$smarty->caching = true;

// Время жизни кеша в секундах (-1 - включает его навсегда).
$smarty->cache_lifetime = 120;

// Передача значений в шаблон:
$smarty->assign('h1', 'хелоу');
$smarty->assign('text', 'Hello World...');
$smarty->assign('number', 200);

// Обычный массив.
$smarty->assign(
'array_1', array(
'Понедельник',
'Вторник',
'Среда'
)
);

// Ассоциативный массив.
$smarty->assign(
'array_2', array(
'item_a' => 'Январь',
'item_b' => 'Февраль',
'item_c' => 'Март'
)
);

// Многомерный массив.
$smarty->assign(
'array_3', array(
array(
'id' => 1,
'name' => 'Весна',
),
array(
'id' => 2,
'name' => 'Лето',
),
array(
'id' => 3,
'name' => 'Осень',
),
)
);

// Вывод шаблона.
$smarty->display('index.tpl');

