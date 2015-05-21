<html>
<head>
 <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
 </head>
<table>
<tr>
<td>
<form action="lista.php" method="post" target="lista">
<input name="facturacion" type="submit" value="Facturación">
</form>
</td>
<td>
<form action="lista.php" method="post" target="lista">
<input name="bi_iva" type="submit" value="B.I e IVA">
</form>
</td>
<td>
<form action="lista.php" method="post" target="lista">
<input name="pagos" type="submit" value="Metodos de pago">
</form>
</td>
<td>
<form action="lista.php" method="post" target="lista">
<input name="error" type="submit" value="errores">
</form>
</td>     
<table>
<tr>
<form action="lista.php" method="post" target="lista">
<td>Desde:
<select name="fano">
        <?php
        for($i=date('o'); $i>=1990; $i--){
            if ($i == date('o'))
                echo '<option value="'.$i.'" selected>'.$i.'</option>';
            else
                echo '<option value="'.$i.'">'.$i.'</option>';
        }
        ?>
</select></td>
<td><select name="fmes">
        <?php
        for ($i=1; $i<=12; $i++) {
            if ($i == date('m'))
                echo '<option value="'.$i.'" selected>'.$i.'</option>';
            else
                echo '<option value="'.$i.'">'.$i.'</option>';
        }
        ?>
</select></td>
<td><select name="fdia">
        <?php
        for ($i=1; $i<=31; $i++) {
            if ($i == date('d'))
                echo '<option value="'.$i.'" selected>'.$i.'</option>';
            else
                echo '<option value="'.$i.'">'.$i.'</option>';
        }
        ?>
</select></td>
<td>Hasta:
    <select name="tano">
        <?php
        for($i=date('o'); $i>=1990; $i--){
            if ($i == date('o'))
                echo '<option value="'.$i.'" selected>'.$i.'</option>';
            else
                echo '<option value="'.$i.'">'.$i.'</option>';
        }
        ?>
</select></td>
<td><select name="tmes">
        <?php
        for ($i=1; $i<=12; $i++) {
            if ($i == date('m'))
                echo '<option value="'.$i.'" selected>'.$i.'</option>';
            else
                echo '<option value="'.$i.'">'.$i.'</option>';
        }
        ?>
</select></td>
<td><select name="tdia">
        <?php
        for ($i=1; $i<=31; $i++) {
            if ($i == date('d'))
                echo '<option value="'.$i.'" selected>'.$i.'</option>';
            else
                echo '<option value="'.$i.'">'.$i.'</option>';
        }
        ?>
</select></td>
<td><input name="buscar" type="submit" value="Buscar"></td></tr>
</form>
</table></td></tr>
</table>
<iframe name="lista" src="lista.php" width="100%" height="90%" frameborder="0"></iframe>

</html>