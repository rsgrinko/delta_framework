<?php
    //use Core\CoreException;
    //use Core\ExternalServices\Telegram;
    use Dompdf\Dompdf;

    //use Core\Helpers\{SystemFunctions, Cache, Log, Mail, Zip, Pagination, Files};

    const USE_ROUTER = false;
    require_once $_SERVER['DOCUMENT_ROOT'] . '/core/bootstrap.php';


    $prods = array(
        array(
            'name'  => 'Плита CERAMAGUARD FINE FISSURED (100 RH) 600*600*15',
            'count' => 25.3,
            'unit'  => 'м2',
            'price' => 1210,
            'nds'   => 18,
        ),
        array(
            'name'  => 'Европодвес (0.5м)',
            'count' => 100,
            'unit'  => 'шт.',
            'price' => 5.50,
            'nds'   => 0,
        ),
        array(
            'name'  => 'Профиль 20*20',
            'count' => 10,
            'unit'  => 'м',
            'price' => 550,
            'nds'   => 10,
        ),
        [
            'name'  => 'Разработка и сопровождение веб-сайта',
            'count' => 1,
            'unit'  => 'шт',
            'price' => 1800,
            'nds'   => 20,
        ],

    );

    // Форматирование цен.
    function format_price($value)
    {
        return number_format($value, 2, ',', ' ');
    }

    // Сумма прописью.
    function str_price($value)
    {
        $value = explode('.', number_format($value, 2, '.', ''));

        $f = new NumberFormatter('ru', NumberFormatter::SPELLOUT);
        $str = $f->format($value[0]);

        // Первую букву в верхний регистр.
        $str = mb_strtoupper(mb_substr($str, 0, 1)) . mb_substr($str, 1, mb_strlen($str));

        // Склонение слова "рубль".
        $num = $value[0] % 100;
        if ($num > 19) {
            $num = $num % 10;
        }
        switch ($num) {
            case 1: $rub = 'рубль'; break;
            case 2:
            case 3:
            case 4: $rub = 'рубля'; break;
            default: $rub = 'рублей';
        }

        return $str . ' ' . $rub . ' ' . $value[1] . ' копеек.';
    }


    $html = '
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
	
	<style type="text/css">
	* {font-family: DejaVu Sans;font-size: 12px;line-height: 12px;}
	table {margin: 0 0 15px 0;width: 100%;border-collapse: collapse;border-spacing: 0;}		
	table th {padding: 5px;font-weight: bold;}        
	table td {padding: 5px;}	
	.header {margin: 0 0 0 0;padding: 0 0 15px 0;font-size: 10px;line-height: 10px;text-align: center;}
	h1 {margin: 0 0 10px 0;padding: 10px 0;border-bottom: 2px solid #000;font-weight: bold;font-size: 18px;}
		
	/* Реквизиты банка */
	.details td {padding: 3px 2px;border: 1px solid #000000;font-size: 10px;line-height: 10px;vertical-align: top;}
 
	/* Поставщик/Покупатель */
	.contract th {padding: 3px 0;vertical-align: top;text-align: left;font-size: 10px;line-height: 12px;}	
	.contract td {padding: 3px 0;}		
 
	/* Наименование товара, работ, услуг */
	.list thead, .list tbody  {border: 2px solid #000;}
	.list thead th {padding: 4px 0;border: 1px solid #000;vertical-align: middle;text-align: center;}	
	.list tbody td {padding: 0 2px;border: 1px solid #000;vertical-align: middle;font-size: 11px;line-height: 13px;}	
	.list tfoot th {padding: 3px 2px;border: none;text-align: right;}	
 
	/* Сумма */
	.total {margin: 0 0 20px 0;padding: 0 0 10px 0;border-bottom: 2px solid #000;}	
	.total p {margin: 0;padding: 0;}
		
	/* Руководитель, бухгалтер */
	.sign {position: relative;}
	.sign table {width: 60%;}
	.sign th {padding: 40px 0 0 0;text-align: left;}
	.sign td {padding: 40px 0 0 0;border-bottom: 1px solid #000;text-align: right;font-size: 12px;}
	.sign-1 {position: absolute;left: 149px;top: -44px; width:200px}	
	.sign-2 {position: absolute;left: 149px;top: 0;width:200px}	
	.printing {position: absolute;left: 271px;top: -15px;width:200px}
	</style>
</head>
<body>
	<p class="header">
		Внимание! Оплата данного счета означает согласие с условиями поставки товара.
		Уведомление об оплате обязательно, в противном случае не гарантируется наличие
		товара на складе. Товар отпускается по факту прихода денег на р/с Поставщика,
		самовывозом, при наличии доверенности и паспорта.
	</p>
 
	<table class="details">
		<tbody>
			<tr>
				<td colspan="2" style="border-bottom: none;">ЗАО "БАНК", г.Москва</td>
				<td>БИК</td>
				<td style="border-bottom: none;">000000000</td>
			</tr>
			<tr>
				<td colspan="2" style="border-top: none; font-size: 10px;">Банк получателя</td>
				<td>Сч. №</td>
				<td style="border-top: none;">00000000000000000000</td>
			</tr>
			<tr>
				<td width="25%">ИНН 0000000000</td>
				<td width="30%">КПП 000000000</td>
				<td width="10%" rowspan="3">Сч. №</td>
				<td width="35%" rowspan="3">00000000000000000000</td>
			</tr>
			<tr>
				<td colspan="2" style="border-bottom: none;">ООО "ИНКОСИСТЕМС"</td>
			</tr>
			<tr>
				<td colspan="2" style="border-top: none; font-size: 10px;">Получатель</td>
			</tr>
		</tbody>
	</table>
 
	<h1>Счет на оплату № 10 от 01 февраля 2018 г.</h1>
 
	<table class="contract">
		<tbody>
			<tr>
				<td width="15%">Поставщик:</td>
				<th width="85%">ООО "Компания", ИНН 0000000000, КПП 000000000, 125009, Москва г, Тверская, дом № 9</th>
			</tr>
			<tr>
				<td>Покупатель:</td>
				<th>ООО "Покупатель", ИНН 0000000000, КПП 000000000, 119019, Москва г, Новый Арбат, дом № 10
				</th>
			</tr>
		</tbody>
	</table>
 
	<table class="list">
		<thead>
			<tr>
				<th width="5%">№</th>
				<th width="54%">Наименование товара, работ, услуг</th>
				<th width="8%">Коли-<br>чество</th>
				<th width="5%">Ед.<br>изм.</th>
				<th width="14%">Цена</th>
				<th width="14%">Сумма</th>
			</tr>
		</thead>
		<tbody>';


    $total = $nds = 0;
    foreach ($prods as $i => $row) {
        $total += $row['price'] * $row['count'];
        $nds += ($row['price'] * $row['nds'] / 100) * $row['count'];

        $html .= '
			<tr>
				<td align="center">' . (++$i) . '</td>
				<td align="left">' . $row['name'] . '</td>
				<td align="right">' . $row['count'] . '</td>
				<td align="left">' . $row['unit'] . '</td>
				<td align="right">' . format_price($row['price']) . '</td>
				<td align="right">' . format_price($row['price'] * $row['count']) . '</td>
			</tr>';
    }

    $html .= '
		</tbody>
		<tfoot>
			<tr>
				<th colspan="5">Итого:</th>
				<th>' . format_price($total) . '</th>
			</tr>
			<tr>
				<th colspan="5">В том числе НДС:</th>
				<th>' . ((empty($nds)) ? '-' : format_price($nds)) . '</th>
			</tr>
			<tr>
				<th colspan="5">Всего к оплате:</th>
				<th>' . format_price($total) . '</th>
			</tr>
		</tfoot>
	</table>
	
	<div class="total">
		<p>Всего наименований ' . count($prods) . ', на сумму ' . format_price($total) . ' руб.</p>
		<p><strong>' . str_price($total) . '</strong></p>
	</div>
	
	<div class="sign">
		<img class="sign-1" src="https://xn--80ap4as.xn--5-stbmg8e.xn--p1ai/wp-content/uploads/sites/57/2017/08/podpis-470x412.png">
		<img class="sign-2" src="https://avatanplus.com/files/resources/original/583801312ebef1589ac4a827.png">
		<img class="printing" src="https://xn-----6kcblnft2byagprce4eva2dya.xn--p1ai/images/dok.png">
		<table>
			<tbody>
				<tr>
					<th width="30%">Руководитель</th>
					<td width="70%">Иванов А.А.</td>
				</tr>
				<tr>
					<th>Бухгалтер</th>
					<td>Сидоров Б.Б.</td>
				</tr>
			</tbody>
		</table>
	</div>
</body>
</html>';





    $dompdf = new Dompdf();
    $dompdf->set_option('isRemoteEnabled', TRUE);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->loadHtml($html, 'UTF-8');
    $dompdf->render();

    // Вывод файла в браузер:
    $dompdf->stream('schet');
