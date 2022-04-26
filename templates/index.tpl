{$h1}
HTML
В PHP эта запись аналогична:

<?php echo $h1; ?>
PHP
Заголовок H1
Также можно вывести значение массива по ключу.

{$array_1[0]}
{$array_2.item_a}
{$array_3[0].name}
HTML
В PHP это выглядело бы так:

<?php echo $array_1[0]; ?>
<?php echo $array_2['item_a']; ?>
<?php echo $array_3[0]['name']; ?>
