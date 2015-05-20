<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<style>
table, td, th {
    border: 1px solid;
}

td {
    padding: 5px;
    text-align: left;
}
.number {
	text-align: right;
}

</style>
<?php
require('config.php');

if (isset($_REQUEST['buscar'])) {
	$from= $_REQUEST['fano'].'-'.$_REQUEST['fmes'].'-'.$_REQUEST['fdia'];
	$to= $_REQUEST['tano'].'-'.$_REQUEST['tmes'].'-'.$_REQUEST['tdia'];
	$fecha= 'where facturas.fecha >= "'.$from.'" and facturas.fecha <= "'.$to.'"';
}

//Facturación
$sql0='select 
  facturas.factura as factura
, facturas.pedido as pedido
, date(facturas.fecha) as fecha 
, facturas.estado
, facturas.productos as productos
, facturas.transporte as transporte
, facturas.contrareembolso as contrareembolso
, facturas.subtotal as subtotal
, facturas.iva0 + facturas.iva4 + facturas.iva10 + facturas.iva21 as iva
, facturas.total as total
 from ( 
	SELECT sfig.entity_id as id_factura 
	, sfig.order_id as id_pedido
	, sfig.increment_id as factura
	, sfig.order_increment_id as pedido
	, sfi.created_at as fecha
	, sfo.state as estado 
	, sfi.subtotal - sfi.discount_amount as productos 
	, sfi.shipping_amount as transporte
	, ifnull(sfi.cod_fee,0) as contrareembolso
	, sfi.subtotal - sfi.discount_amount 
	+ sfi.shipping_amount 
	+ ifnull(sfi.cod_fee,0) as subtotal  
	,sum(if(sfoi.tax_percent=0,sfoi.tax_amount,0)) as iva0 
	,sum(if(sfoi.tax_percent=4,sfoi.tax_amount,0)) as iva4 
	,sum(if(sfoi.tax_percent=10,sfoi.tax_amount,0)) as iva10 
	,sum(if(sfoi.tax_percent=21,sfoi.tax_amount,0)) +  sfi.shipping_tax_amount + ifnull(sfi.cod_tax_amount,0) as iva21 
    , sfi.grand_total as Total
	FROM sales_flat_invoice sfi, sales_flat_order sfo, sales_flat_invoice_grid sfig, sales_flat_order_item sfoi 
	WHERE sfi.entity_id = sfig.entity_id AND sfi.order_id = sfo.entity_id AND sfoi.order_id = sfi.order_id 
	GROUP BY sfi.entity_id) 
facturas '.$fecha.'
 group by facturas.factura';


//Bases imponibles e ivas
$sql1='select 
  month(facturas.fecha) as mes
, year(facturas.fecha) as anyo
, sum(facturas.iva0) as iva0 
, sum(facturas.iva4) as iva4 
, sum(facturas.iva10) as  iva10 
, sum(facturas.iva21) as iva21 
, sum(facturas.bi0) as bi0 
, sum(facturas.bi4) as bi4 
, sum(facturas.bi10) as bi10 
, sum(facturas.bi21) as bi21
, sum(facturas.iva0 + facturas.iva4 + facturas.iva10 + facturas.iva21) + sum(facturas.bi0 + facturas.bi4 + facturas.bi10 + facturas.bi21) as total 
 from ( 
	select 
	 date( sfi.created_at ) as fecha
    ,sum(if(sfoi.tax_percent=0,sfoi.base_row_invoiced - sfoi.base_discount_invoiced,0)) as bi0
	,sum(if(sfoi.tax_percent=4,sfoi.base_row_invoiced - sfoi.base_discount_invoiced,0)) as bi4 
	,sum(if(sfoi.tax_percent=10,sfoi.base_row_invoiced - sfoi.base_discount_invoiced,0)) as bi10 
	,sum(if(sfoi.tax_percent=21,sfoi.base_row_invoiced - sfoi.base_discount_invoiced,0)) + sfi.shipping_amount + ifnull(sfi.cod_fee,0) as bi21 
	,sum(if(sfoi.tax_percent=0,sfoi.tax_amount,0)) as iva0 
	,sum(if(sfoi.tax_percent=4,sfoi.tax_amount,0)) as iva4 
	,sum(if(sfoi.tax_percent=10,sfoi.tax_amount,0)) as iva10 
	,sum(if(sfoi.tax_percent=21,sfoi.tax_amount,0)) +  sfi.shipping_tax_amount + ifnull(sfi.cod_tax_amount,0) as iva21 
	from sales_flat_invoice sfi, sales_flat_order_item sfoi 
	where  sfoi.order_id = sfi.order_id 
	GROUP BY sfi.order_id) facturas
group by month(fecha), year(fecha)';

//Metodos de pago
$sql2='select 
  date(facturas.fecha) as fecha
, facturas.metodo as metodo
, facturas.subtotal as subtotal 
, facturas.iva as iva
, facturas.total as total
 from ( 
select
	  sfi.created_at as fecha
    , sfop.method as metodo
	, round(sum(sfi.subtotal - replace(sfi.discount_amount, "-", "" ) 
	+ sfi.shipping_amount 
	+ ifnull(sfi.cod_fee,0)),2) as subtotal 
	, sum(sfi.tax_amount) as iva
    , sum(sfi.grand_total) as total
	from sales_flat_invoice sfi, sales_flat_order_payment sfop
	where  sfi.order_id = sfop.parent_id
	group by sfop.method, date(sfi.created_at)
    order by date(sfi.created_at) desc) 
facturas'.$fecha;

//Errores de facturacion
$sqle='select facturas.id_factura 
,facturas.id_pedido 
,facturas.pedido,facturas.factura
,facturas.estado 
,facturas.iva0 + facturas.iva4 + facturas.iva10 + facturas.iva21 as ivatotal 
,facturas.bi0 + facturas.bi4 + facturas.bi10 + facturas.bi21 as base_total 
,facturas.iva0 +facturas.iva4 + facturas.iva10 + facturas.iva21 + facturas.bi0 + facturas.bi4 + facturas.bi10 + facturas.bi21 as total 
,facturas.magento_total from ( 
	SELECT sfig.entity_id as id_factura 
	, sfig.order_id as "id_pedido"
	, sfig.increment_id as "factura" 
	, sfig.order_increment_id as "pedido"
	, sfo.state as "estado" ,sfi.subtotal - replace(sfi.discount_amount, "-", "" ) as "productos" 
	, sfi.shipping_amount as "transporte" 
	, ifnull(sfi.cod_fee,0) as "contrareembolso" 
	,sum(if(sfoi.tax_percent=0,sfoi.tax_amount,0)) as iva0 
	,sum(if(sfoi.tax_percent=4,sfoi.tax_amount,0)) as iva4 
	,sum(if(sfoi.tax_percent=10,sfoi.tax_amount,0)) as iva10 
	,sum(if(sfoi.tax_percent=21,sfoi.tax_amount,0)) +  sfi.shipping_tax_amount + ifnull(sfi.cod_tax_amount,0) as iva21 
	,sum(if(sfoi.tax_percent=0,sfoi.base_row_invoiced - sfoi.base_discount_invoiced,0)) as bi0
	,sum(if(sfoi.tax_percent=4,sfoi.base_row_invoiced - sfoi.base_discount_invoiced,0)) as bi4 
	,sum(if(sfoi.tax_percent=10,sfoi.base_row_invoiced - sfoi.base_discount_invoiced,0)) as bi10 
	,sum(if(sfoi.tax_percent=21,sfoi.base_row_invoiced - sfoi.base_discount_invoiced,0)) + sfi.shipping_amount + ifnull(sfi.cod_fee,0) as bi21 
	, sfi.grand_total as magento_total FROM sales_flat_invoice sfi, sales_flat_order sfo
	, sales_flat_invoice_grid sfig, sales_flat_order_item sfoi 
	WHERE sfi.entity_id = sfig.entity_id AND sfi.order_id = sfo.entity_id AND sfoi.order_id = sfi.order_id GROUP BY sfi.order_id) 
facturas where magento_total <> 
facturas.iva0 +facturas.iva4 + facturas.iva10 + facturas.iva21 + facturas.bi0 + facturas.bi4 + facturas.bi10 + facturas.bi21';


//Facturación
if (isset($_REQUEST['facturacion']) || isset($_REQUEST['buscar']) ) {
	$sql=$sql0;
	echo '<table><tr>
	<td>N Factura</td>
	<td>N Pedido</td>
	<td>Fecha</td>
	<td>Estado</td>
	<td>Productos</td>
	<td>Transporte</td>
	<td>Contrareembolso</td>
	<td>Subtotal</td>
	<td>IVA</td>
	<td>Total + IVA</td></tr>';

	$lista = mysqli_query($link, $sql);
	$sigue= TRUE;

	$productos=0;
	$transporte=0;
	$contrareembolso=0;
	$subtotal=0;
	$iva=0;
	$total=0;
	while ($sigue) {
		$factura= mysqli_fetch_array($lista);
		if ($factura) {
			$productos=$productos + $factura['productos'];
			$transporte=$transporte + $factura['transporte'];
			$contrareembolso=$contrareembolso + $factura['contrareembolso'];
			$subtotal=$subtotal + $factura['subtotal'];
			$iva=$iva + $factura['iva'];
			$total=$total + $factura['total'];
			echo'
			<tr>
			<td>'.$factura['factura'].'</td>
			<td>'.$factura['pedido'].'</td>
			<td>'.$factura['fecha'].'</td>
			<td>'.$factura['estado'].'</td>
			<td class="number">'.number_format($factura['productos'],2).'</td>
			<td class="number">'.number_format($factura['transporte'],2).'</td>
			<td class="number">'.number_format($factura['contrareembolso'],2).'</td>
			<td class="number">'.number_format($factura['subtotal'],2).'</td>
			<td class="number">'.number_format($factura['iva'],2).'</td>
			<td class="number">'.number_format($factura['total'],2).'</td></tr>';


		} else {
			$sigue = FALSE;
			echo '<tr><td colspan="4">Totales</td>
			<td class="number">'.number_format($productos,2).'</td>
			<td class="number">'.number_format($transporte,2).'</td>
			<td class="number">'.number_format($contrareembolso,2).'</td>
			<td class="number">'.number_format($subtotal,2).'</td>
			<td class="number">'.number_format($iva,2).'</td>
			<td class="number">'.number_format($total,2).'</td></tr></table>';

		}
	}
mysqli_close($link);
}

//BI IVA
if (isset($_REQUEST['bi_iva'])) {
	$sql=$sql1;
	echo '
	<table><tr>
	<td>Mes</td>
	<td>Año</td>
	<td>IVA 0</td>
	<td>IVA 4</td>
	<td>IVA 10</td>
	<td>IVA 21</td>
	<td>B.I 0</td>
	<td>B.I 4</td>
	<td>B.I 10</td>
	<td>B.I 21</td>
	<td>Total</td></tr>';

	$lista = mysqli_query($link, $sql);
	print mysqli_error($link);
	$sigue= TRUE;

	$iva0= 0;
	$iva4= 0;
	$iva10= 0;
	$iva21= 0;
	$bi0= 0;
	$bi4= 0;
	$bi10= 0;
	$bi21= 0;
	$total=0;
	while ($sigue) {
		$factura= mysqli_fetch_array($lista);
		if ($factura) {
			$iva0=$iva0 + $factura['iva0'];
			$iva4=$iva4 + $factura['iva4'] ;
			$iva10=$iva10 + $factura['iva10'] ;
			$iva21=$iva21 + $factura['iva21'] ;
			$bi0=$bi0 + $factura['bi0'] ;
			$bi4=$bi4 + $factura['bi4'] ;
			$bi10=$bi10 + $factura['bi10'] ;
			$bi21=$bi21 + $factura['bi21'] ;
			$total=$total + $factura['total'];
			echo'
			<tr>
			<td>'.$factura['mes'].'</td>
			<td>'.$factura['anyo'].'</td>
			<td class="number">'.number_format($factura['iva0'],2).'</td>
			<td class="number">'.number_format($factura['iva4'],2).'</td>
			<td class="number">'.number_format($factura['iva10'],2).'</td>
			<td class="number">'.number_format($factura['iva21'],2).'</td>
			<td class="number">'.number_format($factura['bi0'],2).'</td>
			<td class="number">'.number_format($factura['bi4'],2).'</td>
			<td class="number">'.number_format($factura['bi10'],2).'</td>
			<td class="number">'.number_format($factura['bi21'],2).'</td>
			<td class="number">'.number_format($factura['total'],2).'</td></tr>';


		} else {
			$sigue = FALSE;
			echo '<tr><td colspan="2">Totales</td>
			<td class="number">'.number_format($iva0,2).'</td>
			<td class="number">'.number_format($iva4,2).'</td>
			<td class="number">'.number_format($iva10,2).'</td>
			<td class="number">'.number_format($iva21,2).'</td>
			<td class="number">'.number_format($bi0,2).'</td>
			<td class="number">'.number_format($bi4,2).'</td>
			<td class="number">'.number_format($bi10,2).'</td>
			<td class="number">'.number_format($bi21,2).'</td>
			<td class="number">'.number_format($total,2).'</td></tr></table>';

		}
	}
mysqli_close($link);
}

//Pagos
if (isset($_REQUEST['pagos'])) {
	$sql=$sql2;
	echo '
	<table><tr>
	<td>Fecha</td>
	<td>Metodo</td>
	<td>Subtotal</td>
	<td>IVA</td>
	<td>Total</td></tr>';

	$lista = mysqli_query($link, $sql);
	print mysqli_error($link);
	$sigue= TRUE;

	$subtotal=0;
	$iva=0;
	$total=0;
	while ($sigue) {
		$factura= mysqli_fetch_array($lista);
		if ($factura) {
			$subtotal=$subtotal + $factura['subtotal'] ;
			$iva=$iva + $factura['iva'] ;
			$total=$total + $factura['total'];
			echo'
			<tr>
			<td>'.$factura['fecha'].'</td>
			<td>'.$factura['metodo'].'</td>
			<td class="number">'.number_format($factura['subtotal'],2).'</td>
			<td class="number">'.number_format($factura['iva'],2).'</td>
			<td class="number">'.number_format($factura['total'],2).'</td></tr>';


		} else {
			$sigue = FALSE;
			echo '<tr><td colspan="2">Totales</td>
			<td class="number">'.number_format($subtotal,2).'</td>
			<td class="number">'.number_format($iva,2).'</td>
			<td class="number">'.number_format($total,2).'</td></tr></table>';

		}
	}
mysqli_close($link);
}

if (isset($_REQUEST['error'])) {
	$sql=$sqle;
	echo '
	<table><tr>
	<td>ID Factura</td>
	<td>ID Pedido</td>
	<td>Factura</td>
	<td>Pedido</td>
	<td>Estado</td>
	<td>Base Total</td>
	<td>IVA Total</td>
	<td>Total Suma</td>
	<td>Total Magento</td>';

	$lista = mysqli_query($link, $sql);
	print mysqli_error($link);
	$sigue= TRUE;
	
	while ($sigue) {
		$factura= mysqli_fetch_array($lista);
		if ($factura) {
			echo'
			<tr>
			<td>'.$factura['id_factura'].'</td>
			<td>'.$factura['id_pedido'].'</td>
			<td class="number">'.$factura['factura'].'</td>
			<td class="number">'.$factura['pedido'].'</td>
			<td class="number">'.$factura['estado'].'</td>
			<td class="number">'.number_format($factura['base_total'],2).'</td>
			<td class="number">'.number_format($factura['ivatotal'],2).'</td>
			<td class="number">'.number_format($factura['total'],2).'</td>
			<td class="number">'.number_format($factura['magento_total'],2).'</td></tr>';


		} else {
			$sigue = FALSE;

		}
	}
mysqli_close($link);
}

?>
</html>