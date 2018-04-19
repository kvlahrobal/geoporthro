<?php
/*	Modul: alkisrelationen.php

	ALKIS-Buchauskunft, Kommunales Rechenzentrum Minden-Ravensberg/Lippe (Lemgo).
	Verfolgt die Beziehungen von ALKIS-Objekten in der Tabelle 'alkis_beziehungen'.
	Link durch "&id=j;" in den anderen Modulen zuschaltbar.
	Dies ist fuer die Entwicklung der Auskunft gedacht (Sonderfaelle) nicht fuer den Anwender.

	Version:
	2011-11-10  Relationen-Zähler ausgeben, ab 5 Zeilen nicht mehr 'auf einen Blick' erkennbar.
	2011-11-30  import_request_variables
	2013-04-08  deprecated "import_request_variables" ersetzt
*/
//session_start();
$cntget = extract($_GET);
require_once("alkis_conf_location.php");
if ($auth == "mapbender") {require_once($mapbender);}
$otyp=htmlentities($otyp, ENT_QUOTES, "UTF-8");
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
	<meta name="author" content="b600352" >
	<meta http-equiv="cache-control" content="no-cache">
	<meta http-equiv="pragma" content="no-cache">
	<meta http-equiv="expires" content="0">
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<link rel="stylesheet" type="text/css" href="alkisauszug.css">
	<link rel="shortcut icon" type="image/x-icon" href="ico/Beziehung.ico">
	<title>ALKIS-Relationen-Browser</title>
</head>
<body>
<?php
$con = pg_connect("host=".$dbhost." port=".$dbport." dbname=".$dbname." user=".$dbuser." password=".$dbpass);#." sslmode=".$sslmode);
echo "\n<p class='bezieh'>Beziehungen ".$gmlid."</p>"; // Balken

echo "\n\n<h2><img src='ico/Beziehung.ico' width='16' height='16' alt=''> Beziehungen</h2>";
if (!$con) {
	echo "\n<p class='err'>Fehler beim Verbinden der DB.</p>";
} else {
	echo "\n\n<h3 title='Die gml_is ist global eindeutig'>ALKIS-".$otyp." mit gml_id = '".$gmlid."'</h3>";
	$sql="SELECT beziehungsart, beziehung_zu FROM aaaa_ogr.lkis_beziehungen WHERE beziehung_von= $1;";
	$v = array($gmlid);
	$res = pg_prepare("", $sql);
	$res = pg_execute("", $v);

	echo "\n<table>";
	if (!$res) {
		echo "\n<tr>\n\t<td colspan=3><b>Keine</b> Beziehungen vom Objekt</td>\n</tr>";
	} else {
		echo "\n<tr>\n\t<td class='head' colspan=3><br>Beziehungen <b>vom</b> Objekt zu ..</td>\n</tr>";
		$i=0;
		while($row = pg_fetch_array($res)) {
			echo "\n<tr>\n\t<td>".$otyp."</td>";
			echo "\n\t<td class='bez'>".$row["beziehungsart"]."</td>";
			echo "\n\t<td>\n\t\t<a href='".$_SERVER['PHP_SELF']."?gkz=".$gkz."&amp;gmlid=".$row["beziehung_zu"]."'>".$row["beziehung_zu"]."</a>";
			echo "\n\t</td>\n</tr>";
			$i++;
		}
		if ($i == 0) {
			echo "<tr><td colspan=3>.. keine</td></tr>";
		} elseif ($i > 4) {
			echo "\n<tr>\n\t<td colspan=3>".$i." Relationen</td>\n</tr>";
		}
	}
	$sql="SELECT beziehungsart, beziehung_von FROM aaa_ogr.alkis_beziehungen WHERE beziehung_zu= $1;";
	$v = array($gmlid);
	$res = pg_prepare("", $sql);
	$res = pg_execute("", $v);

	if (!$res) {
		echo "<tr><td colspan=3><b>Keine</b> Beziehungen zum Objekt</td></tr>";
	} else {
		echo "\n<tr>\n\t<td class='head' colspan=3><br>Beziehungen <b>zum</b> Objekt von ..</td>\n</tr>";
		$i=0;
		while($row = pg_fetch_array($res)) {
			echo "\n<tr>\n\t<td>";
			echo "\n\t\t<a href='".$_SERVER['PHP_SELF']."?gkz=".$gkz."&amp;gmlid=".$row["beziehung_von"]."'>".$row["beziehung_von"]."</a>";
			echo "\n\t</td>";
			echo "\n\t<td class='bez'>".$row["beziehungsart"]."</td>";
			echo "\n\t<td>".$otyp."</td>\n</tr>";
			$i++;
		}
		if ($i == 0) {
			echo "\n<tr>\n\t<td colspan=3>.. keine</td>\n</tr>";
		} elseif ($i > 4) {
			echo "\n<tr>\n\t<td colspan=3>".$i." Relationen</td>\n</tr>";
		}
	}
	echo "\n</table>";
}
echo "\n<hr>\n<p class='nwlink'>\n\t<a target='_blank' href='".$hilfeurl."' title='Dokumentation'>Hilfe zur ALKIS-Auskunft</a>\n</p>\n";
?>

</body>
</html>