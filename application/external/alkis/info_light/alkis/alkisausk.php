<?php
//session_start();
$cntget = extract($_GET);
require_once("alkis_conf_location.php");
if ($auth == "mapbender") {require_once($mapbender);}
include("alkisfkt.php");
if ($id == "j") {$idanzeige=true;} else {$idanzeige=false;}
$keys = isset($_GET["showkey"]) ? $_GET["showkey"] : "n";
if ($keys == "j") {$showkey=true;} else {$showkey=false;}
echo <<<END
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
	<meta name="author" content="b600352" >
	<meta http-equiv="cache-control" content="no-cache">
	<meta http-equiv="pragma" content="no-cache">
	<meta http-equiv="expires" content="0">
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<link rel="stylesheet" type="text/css" href="alkisauszug.css">
	<link rel="shortcut icon" type="image/x-icon" href="ico/Flurstueck.ico">
	<title>Auskunft</title>
	<style type='text/css' media='print'>
		.noprint { visibility: hidden;}
	</style>
</head>
<body>
END;
$con = pg_connect("host=".$dbhost." port=".$dbport." dbname=".$dbname." user=".$dbuser." password=".$dbpass);#." sslmode=".$sslmode);
if (!$con) {echo "<br>Fehler beim Verbinden der DB.\n<br>";}

// *** F L U R S T U E C K ***
$sql ="SELECT f.flurnummer, f.zaehler, f.nenner, f.amtlicheflaeche, g.gemarkungsnummer, g.bezeichnung ";
$sql.="FROM aaa_ogr.ax_flurstueck f ";
$sql.="LEFT JOIN aaa_ogr.ax_gemarkung g ON f.gemarkungsnummer = g.gemarkungsnummer ";
$sql.="WHERE f.endet IS NULL AND g.endet IS NULL AND f.gml_id = $1;";
// Weiter joinen: g.stelle -> ax_dienststelle "Katasteramt"

$v = array($gmlid);
$res = pg_prepare("", $sql);
$res = pg_execute("", $v);
if (!$res) {
	echo "\n<p class='err'>Fehler bei Flurstuecksdaten.</p>\n";
	if ($debug > 2) {echo "<p class='err'>SQL=<br>".$sql."<br>$1 = gml_id = '".$gmlid."'</p>";}
}

if ($row = pg_fetch_array($res)) {
	$gemkname=htmlentities($row["bezeichnung"], ENT_QUOTES, "UTF-8");
	$gmkgnr=$row["gemarkungsnummer"];
	$flurnummer=$row["flurnummer"];
	$flstnummer=$row["zaehler"];
	$nenner=$row["nenner"];
	if ($nenner > 0) $flstnummer.="/".$nenner; // BruchNr
  $amtlicheflaeche=$row["amtlicheflaeche"]; // amtliche Fläche
	$amtlicheflaeched=($amtlicheflaeche < 1 ? rtrim(number_format($amtlicheflaeche,2,",","."),"0") : number_format($amtlicheflaeche,0,",",".")); // Display-Format dazu
} else {
	echo "<p class='err'>Kein Treffer fuer gml_id=".$gmlid."</p>";
}

// Balken
echo "\n<p class='fsausk'>Flurstücksübersicht ".$gmkgnr."-".$flurnummer."-".$flstnummer."</p>";

echo "\n<table class='outer'>\n<tr><td>";
	// linke Seite
	echo "\n<h1>Auskunft</h1>";
	echo "\n<h2><img src='ico/Flurstueck.ico' width='16' height='16' alt=''> Flurstück - &Uuml;bersicht</h2>";
echo "</td><td align='right'>";
	// rechte Seite
	echo "<img src='pic/AAA.gif' alt=''>";
echo "</td></tr></table>";

echo "\n<table class='outer'>\n<tr>\n<td>";
	echo "\n\t<table class='kennzfs' title='Flurstückskennzeichen'>\n\t<tr>";
	echo "\n\t\t<td class='head'>Gmkg</td>\n\t\t<td class='head'>Flur</td>\n\t\t<td class='head'>Flurst-Nr.</td>\n\t</tr>";
	echo "\n\t<tr>\n\t\t<td title='Gemarkung'>";
   if ($showkey) {
		echo "<span class='key' title='Gemarkungsschlüssel'>".$gmkgnr."</span><br>";
	}
	echo $gemkname."</td>";
	echo "\n\t\t<td title='Flurnummer'>".$flurnummer."</td>";
	echo "\n\t\t<td title='Flurstücksnummer (Zähler / Nenner)'><span class='wichtig'>".$flstnummer."</span></td>\n\t</tr>";
	echo "\n\t</table>";
echo "\n</td>\n<td>";
if ($idanzeige) {linkgml($gkz, $gmlid, "Flurstück"); }
echo "\n\t<p class='nwlink'>weitere Auskunft:<br>";

// Flurstuecksnachweis
echo "\n\t<a href='alkisfsnw.php?gkz=".$gkz."&amp;gmlid=".$gmlid;
	if ($idanzeige) {echo "&amp;id=j";}
	if ($showkey)   {echo "&amp;showkey=j";}
	echo "' title='Flurstücksnachweis, alle Flurstücksdaten'>Flurstück ";
	echo "<img src='ico/Flurstueck_Link.ico' width='16' height='16' alt=''>";
echo "</a><br>";

// FS-Historie
echo "\n\t\t<a href='alkisfshist.php?gkz=".$gkz."&amp;gmlid=".$gmlid;
	if ($idanzeige) {echo "&amp;id=j";}
	if ($showkey)   {echo "&amp;showkey=j";}
	echo "' title='Vorgänger des Flurstücks'>Historie ";
	echo "<img src='ico/Flurstueck_Historisch.ico' width='16' height='16' alt=''>";
echo "</a><br>";

// Gebaeude-NW
echo "\n\t\t<a href='alkisgebaeudenw.php?gkz=".$gkz."&amp;gmlid=".$gmlid;
	if ($idanzeige) {echo "&amp;id=j";}
	if ($showkey)   {echo "&amp;showkey=j";}
	echo "' title='Gebäudenachweis'>Gebäude ";
	echo "<img src='ico/Haus.ico' width='16' height='16' alt=''>";
echo "</a>";

echo "\n\t</p>\n</td>";

// Lagebezeichnung MIT Hausnummer (Adresse)
$sql ="SELECT DISTINCT l.gml_id, k.gml_id AS kgml, l.gemeinde, l.lage, l.hausnummer, k.bezeichnung ";
$sql.="FROM aaa_ogr.ax_flurstueck f ";
$sql.="JOIN aaa_ogr.ax_lagebezeichnungmithausnummer l ON l.gml_id = ANY(f.weistauf) ";
$sql.="LEFT JOIN aaa_ogr.ax_lagebezeichnungkatalogeintrag k ON l.lage = k.lage ";
$sql.="WHERE f.gml_id = $1 ";
$sql.="AND f.endet IS NULL AND l.endet IS NULL AND k.endet IS NULL ";
$sql.="ORDER BY l.gemeinde, l.lage, l.hausnummer;";
$v = array($gmlid);
$res = pg_prepare("", $sql);
$res = pg_execute("", $v);
if (!$res) {
	echo "<p class='err'>Fehler bei Lagebezeichnung mit Hausnummer.</p>";
	if ($debug > 2) {echo "<p class='err'>SQL=<br>".$sql."<br>$1 = gml_id = '".$gmlid."'</p>";}
}
$j=0;
while($row = pg_fetch_array($res)) {
	$sname = htmlentities($row["bezeichnung"], ENT_QUOTES, "UTF-8"); // Str.-Name
	echo "\n<tr>\n\t";
		echo "\n\t<td class='lr'>".$sname."&nbsp;".$row["hausnummer"]."</td>";
		echo "\n\t<td>\n\t\t<p class='nwlink noprint'>";
			echo "\n\t\t\t<a title='Lagebezeichnung mit Hausnummer' href='alkislage.php?gkz=".$gkz."&amp;ltyp=m&amp;gmlid=".$row["gml_id"]."'>Lage ";
			echo "<img src='ico/Lage_mit_Haus.ico' width='16' height='16' alt=''></a>&nbsp;";

			echo "\n\t\t\t<a href='alkisstrasse.php?gkz=".$gkz."&amp;gmlid=".$row["kgml"]; // Katalog GML-ID
			echo "' title='Straße'>Straße <img src='ico/Strassen.ico' width='16' height='16' alt=''></a>";
		echo "\n\t\t</p>\n\t</td>";
	echo "\n</tr>";
	$j++;
}
echo "\n</tr>\n</table>\n";

// Flurstuecksflaeche
echo "\n<p class='fsd'>Flurstücksfläche: <b>".$amtlicheflaeched." m²</b></p>\n";

// *** G R U N D B U C H ***
echo "\n<h2><img src='ico/Grundbuch_zu.ico' width='16' height='16' alt=''> Grundbuch</h2>";
// ALKIS: FS --> bfs --> GS --> bsb --> GB.
$sql ="SELECT b.gml_id, b.bezirk, b.buchungsblattnummermitbuchstabenerweiterung as blatt, b.blattart, ";
$sql.="s.gml_id AS s_gml, s.buchungsart, s.laufendenummer, s.zaehler, s.nenner, ";
$sql.="z.bezeichnung, a.beschreibung AS bart ";
$sql.="FROM aaa_ogr.ax_buchungsblatt b ";
$sql.="JOIN aaa_ogr.ax_buchungsstelle s ON s.istbestandteilvon = b.gml_id ";
$sql.="JOIN aaa_ogr.ax_flurstueck f ON f.istgebucht = s.gml_id ";
$sql.="LEFT JOIN aaa_ogr.ax_buchungsblattbezirk z ON z.bezirk = b.bezirk ";
$sql.="LEFT JOIN aaa_ogr.ax_buchungsart_buchungsstelle a ON a.wert = s.buchungsart ";
$sql.="WHERE f.endet IS NULL AND b.endet IS NULL AND s.endet IS NULL AND z.endet IS NULL AND f.gml_id = $1";
$sql.="ORDER BY b.bezirk, b.buchungsblattnummermitbuchstabenerweiterung, s.laufendenummer;";
$v = array($gmlid);
$resg = pg_prepare("", $sql);
$resg = pg_execute("", $v);
if (!$resg) {
	echo "\n<p class='err'>Keine Buchungen.</p>\n";
	if ($debug > 2) {echo "<p class='err'>SQL=<br>".$sql."<br>$1 = gml_id = '".$gmlid."'</p>";}
}

$j=0; // Z.Blatt
while($rowg = pg_fetch_array($resg)) {
	$beznam=$rowg["bezeichnung"];
	echo "\n<hr>\n<table class='outer'>";
	echo "\n<tr>";
	echo "\n<td>";

		$blattkey=$rowg["blattart"];
		$blattart=blattart($blattkey);
		if ($blattkey == 1000) {
			echo "\n\t<table class='kennzgb' title='Bestandskennzeichen'>";
		} else {
			echo "\n\t<table class='kennzgbf' title='Bestandskennzeichen'>"; // dotted
		}
			echo "\n\t<tr>";
				echo "\n\t\t<td class='head'>Bezirk</td>";
				echo "\n\t\t<td class='head'>".$blattart."</td>";
				echo "\n\t\t<td class='head'>Lfd-Nr,</td>";
				echo "\n\t\t<td class='head'>Buchungsart</td>";
			echo "\n\t</tr>";
			echo "\n\t<tr>";
				echo "\n\t\t<td title='Grundbuchbezirk'>";
					if ($showkey) {
						echo "<span class='key' title='Grundbuchbezirksschlüssel'>".$rowg["bezirk"]."</span><br>";
					}
				echo $beznam."</td>";
				echo "\n\t\t<td title='Grundbuch-Blatt'><span class='wichtig'>".$rowg["blatt"]."</span></td>";
				echo "\n\t\t<td title='Bestandsverzeichnis-Nummer (BVNR, Grundstück)'>".$rowg["laufendenummer"]."</td>";
				echo "\n\t\t<td title='Buchungsart'>";
					if ($showkey) {
						echo "<span class='key' title='Buchungsartschlüssel'>".$rowg["buchungsart"]."</span><br>";
					}
					echo $rowg["bart"];
				echo "</td>";
			echo "\n\t</tr>";
		echo "\n\t</table>";

		if ($rowg["zahler"] <> "") {
			echo "\n<p class='ant'>".$rowg["zahler"]."/".$rowg["nenner"]."&nbsp;Anteil am Flurstück</p>";
		}
		echo "\n</td>\n<td>";
		if ($idanzeige) {linkgml($gkz, $rowg[0], "Buchungsblatt");}
		echo "\n\t<p class='nwlink'>weitere Auskunft:<br>";
			echo "\n\t\t<a href='alkisbestnw.php?gkz=".$gkz."&amp;gmlid=".$rowg[0];
				if ($idanzeige) {echo "&amp;id=j";}
				if ($showkey)   {echo "&amp;showkey=j";}
				echo "' title='Bestandsnachweis'>";
				echo $blattart;
				echo " <img src='ico/GBBlatt_link.ico' width='16' height='16' alt=''>";
			echo "</a>";
		echo "\n\t</p>";
	echo "\n</td>";
	echo "\n</tr>";
	echo "\n</table>";
	$j++;
}
if ($j == 0) { // Entwicklungshilfe
	echo "\n<p class='err'>Keine Buchungen gefunden.</p>";
	echo "\n<p><a target='_blank' href=alkisrelationen.php?gkz=".$gkz."&amp;gmlid=".$gmlid.">Beziehungen des Flurstücks</a></p>";
	//echo "<p>".$sql."</p>"; // TEST
}
echo "\n<hr>";

footer($gmlid, $_SERVER['PHP_SELF']."?", "");

?>
</body>
</html>