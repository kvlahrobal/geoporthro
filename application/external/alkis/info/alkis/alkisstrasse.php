<?php
//session_start();
$cntget = extract($_GET);
require_once("alkis_conf_location.php");
if ($auth == "mapbender") {require_once($mapbender);}
include("alkisfkt.php");

if ($id == "j") {$idanzeige=true;} else {$idanzeige=false;}
$keys = isset($_GET["showkey"]) ? $_GET["showkey"] : "n";
if ($keys == "j") {$showkey=true;} else {$showkey=false;}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
	<meta name="author" content="b600352" >
	<meta http-equiv="cache-control" content="no-cache">
	<meta http-equiv="pragma" content="no-cache">
	<meta http-equiv="expires" content="0">
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<title>Straße</title>
	<link rel="stylesheet" type="text/css" href="alkisauszug.css">
	<link rel="shortcut icon" type="image/x-icon" href="ico/Lage_an_Strasse.ico">
	<script type="text/javascript">
		function ALKISexport() {
			window.open(<?php echo "'alkisexport.php?gkz=".$gkz."&tabtyp=strasse&gmlid=".$gmlid."'"; ?>);
		}
	</script>
	<style type='text/css' media='print'>
		.noprint {visibility: hidden;}
	</style>
</head>
<body>
<?php
$con = pg_connect("host=".$dbhost." port=" .$dbport." dbname=".$dbname." user=".$dbuser." password=".$dbpass);#." sslmode=".$sslmode);
if (!$con) echo "<p class='err'>Fehler beim Verbinden der DB</p>\n";
$sql ="SELECT k.schluessel_land AS land, s.regierungsbezirk, s.kreis, s.gemeinde, s.lage, s.bezeichnung AS snam, ";
$sql.="r.bezeichnung AS rnam, k.bezeichnung AS knam, g.bezeichnung AS gnam, b.bezeichnung AS bnam, o.gml_id AS ogml ";
$sql.="FROM aaa_ogr.ax_lagebezeichnungkatalogeintrag s ";
$sql.="JOIN aaa_ogr.ax_regierungsbezirk r ON s.regierungsbezirk=r.regierungsbezirk ";
$sql.="JOIN aaa_ogr.ax_kreisregion k ON s.regierungsbezirk=k.regierungsbezirk AND s.kreis=k.kreis ";
$sql.="JOIN aaa_ogr.ax_gemeinde g ON s.regierungsbezirk=g.regierungsbezirk AND s.kreis=g.kreis AND s.gemeinde=g.gemeinde ";
$sql.="JOIN aaa_ogr.ax_bundesland b ON g.gemeindekennzeichen_land=b.schluessel_land ";
$sql.="LEFT JOIN aaa_ogr.ax_lagebezeichnungohnehausnummer o ON s.regierungsbezirk=o.regierungsbezirk AND s.kreis=o.kreis AND s.gemeinde=o.gemeinde AND s.lage=o.lage ";
$sql.="WHERE s.gml_id= $1 AND g.endet IS NULL AND k.endet IS NULL AND r.endet IS NULL AND b.endet IS NULL AND s.endet IS NULL AND o.endet IS NULL;";
$v=array($gmlid);
$res=pg_prepare("", $sql);
$res=pg_execute("", $v);
if (!$res) {
	echo "\n<p class='err'>Fehler bei Lagebezeichnungskatalogeintrag.</p>\n";
	if ($debug > 2) {echo "<p class='err'>SQL=<br>".$sql."<br>$1 = gml_id = '".$gmlid."'</p>";}
}

if ($row = pg_fetch_array($res)) {
	$lage=$row["lage"]; // Strassenschluessel
	$snam=$row["snam"]; // Strassenname
	$gem=$row["gemeinde"];
	// Balken
	echo "<p class='strasse'>Straße ".$snam." <span title='Straßenschlüssel'>(".$lage.")</span>&nbsp;</p>\n";
} else {
	echo "\n<p class='err'>Kein Treffer bei Lagebezeichnungskatalogeintrag.</p>\n";
}

echo "\n<h2><img src='ico/Strassen.ico' width='16' height='16' alt=''> Straße</h2>\n";

echo "\n<table class='outer'>\n<tr>\n\t<td>"; // Tabelle Kennzeichen
echo "\n\t<table class='kennzstra'>";
	echo "\n\t<tr>";
        echo "\n\t\t<td class='head'>Land</td>";
		echo "\n\t\t<td class='head'>Kreis</td>";
		echo "\n\t\t<td class='head'>Gemeinde</td>";
		echo "\n\t\t<td class='head'>Straße</td>";
	echo "\n\t</tr>";
	echo "\n\t<tr>";

		echo "\n\t\t<td>";
		if ($showkey) {echo "<span title='Landesschlüssel (= erste 2 Stellen des Regionalschlüssels)' class='key'>(".$row["land"].")</span><br>";}
		echo $row["bnam"]."&nbsp;</td>";

		echo "\n\t\t<td>";
		if ($showkey) {echo "<span title='Kreisschlüssel (= erste 5 Stellen des Regionalschlüssels)' class='key'>(".$row["land"].str_pad($row["kreis"], 3, "0", STR_PAD_LEFT).")</span><br>";}
		echo $row["knam"]."&nbsp;</td>";

		echo "\n\t\t<td>";
		if ($showkey) {echo "<span title='Gemeindeschlüssel (= Regionalschlüssel)' class='key'>(".$row["land"].str_pad($row["kreis"], 3, "0", STR_PAD_LEFT).str_pad($gem, 7, "0", STR_PAD_LEFT).")</span><br>";}
		echo $row["gnam"]."&nbsp;</td>";

		echo "\n\t\t<td>";
		if ($showkey) {echo "<span title='Straßenschlüssel' class='key'>(".$lage.")</span><br>";}
		echo "<span class='wichtig'>".$snam."</span>";

		echo "&nbsp;</td>";
	echo "\n\t</tr>";
echo "\n\t</table>";

echo "\n\t</td>\n\t<td>";

// Kopf Rechts:
$ogml=$row["ogml"]; // ID von "Lage Ohne HsNr"
if ($ogml != "") {
	echo "\n\t\t<p class='nwlink noprint'>";
		echo "\n\t\t<a href='alkislage.php?gkz=".$gkz."&amp;ltyp=o&amp;gmlid=".$ogml;
			if ($idanzeige) {echo "&amp;id=j";}
			if ($showkey)   {echo "&amp;showkey=j";}
		echo "' title='Lagebezeichnung Straße'>Lage <img src='ico/Lage_an_Strasse.ico' width='16' height='16' alt=''></a>";
	echo "\n\t\t</p>";
}

echo "\n\t</td>\n</tr>\n</table>";
pg_free_result($res);
// Ende Seitenkopf

echo "\n<hr class='thick'>";

// F L U R S T U E C K E
echo "\n\n<a name='fs'></a><h2><img src='ico/Flurstueck.ico' width='16' height='16' alt=''> Flurstücke…</h2>\n";
echo "\n<p>…an dieser Straße</p>";
// ax_Flurstueck >weistAuf> ax_LagebezeichnungMitHausnummer  > = Hauptgebaeude 
// ax_Flurstueck >zeigtAuf> ax_LagebezeichnungOhneHausnummer > = Strasse
$sql="SELECT '' AS lgml, '' AS hausnummer, g.gemarkungsnummer, g.bezeichnung, f.gml_id, f.flurnummer, f.gemarkung_land AS land, f.zaehler, f.zaehler::int AS zaehler_sort, f.nenner, f.nenner::int AS nenner_sort, f.amtlicheflaeche, f.realflaeche AS fsgeomflae ";
$sql.="FROM aaa_ogr.ax_flurstueck f, aaa_ogr.ax_gemarkung g, aaa_ogr.ax_lagebezeichnungkatalogeintrag s, aaa_ogr.ax_lagebezeichnungohnehausnummer lo ";
$sql.="WHERE s.gml_id = $1 AND f.endet IS NULL AND g.endet IS NULL AND s.endet IS NULL AND lo.endet IS NULL AND f.gemarkung_land = g.schluessel_land AND f.gemarkungsnummer = g.gemarkungsnummer AND lo.gml_id = ANY(f.zeigtauf) AND (lo.land = s.land AND lo.regierungsbezirk = s.regierungsbezirk AND lo.kreis = s.kreis AND lo.gemeinde = s.gemeinde AND lo.lage = s.lage) ";
$sql.="UNION SELECT lm.gml_id AS lgml, lm.hausnummer, g.gemarkungsnummer, g.bezeichnung, f.gml_id, f.flurnummer, f.gemarkung_land AS land, f.zaehler, f.zaehler::int AS zaehler_sort, f.nenner, f.nenner::int AS nenner_sort, f.amtlicheflaeche, f.realflaeche AS fsgeomflae ";
$sql.="FROM aaa_ogr.ax_flurstueck f, aaa_ogr.ax_gemarkung g, aaa_ogr.ax_lagebezeichnungkatalogeintrag s, aaa_ogr.ax_lagebezeichnungmithausnummer lm ";
$sql.="WHERE s.gml_id = $1 AND f.endet IS NULL AND g.endet IS NULL AND s.endet IS NULL AND lm.endet IS NULL AND f.gemarkung_land = g.schluessel_land AND f.gemarkungsnummer = g.gemarkungsnummer AND lm.gml_id = ANY(f.weistauf) AND (lm.land = s.land AND lm.regierungsbezirk = s.regierungsbezirk AND lm.kreis = s.kreis AND lm.gemeinde = s.gemeinde AND lm.lage = s.lage)";
$sql.="ORDER BY gemarkungsnummer, flurnummer, zaehler_sort, nenner_sort;";
$v=array($gmlid);
$resf=pg_prepare("", $sql);
$resf=pg_execute("", $v);
if (!$resf) {
	echo "<p class='err'>Fehler bei Flurstück.</p>\n";
	if ($debug > 2) {echo "<p class='err'>SQL=<br>".$sql."<br>$1 = gml_id = '".$gmlid."'</p>";}	
}

echo "\n<table class='fs'>";
echo "\n<tr>"; // Kopfzeile der Tabelle
	echo "\n\t<td class='head'>Gemarkung</td>";
	echo "\n\t<td class='head'>Flur</td>";
	echo "\n\t<td class='head'>Flurstück</td>";
	echo "\n\t<td class='head fla' title='amtliche Fläche (Buchfläche) des Flurstücks'>Fläche</td>";
	echo "\n\t<td class='head fla'>Hausnummer</td>";
	echo "\n\t<td class='head nwlink noprint' title='weitere Auskunft'>weitere Auskunft</td>";
echo "\n</tr>";
$j=0;
while($rowf = pg_fetch_array($resf)) {
	$flur=$rowf["flurnummer"];
	$fskenn=$rowf["zaehler"]; // Bruchnummer
	if ($rowf["nenner"] != "") {$fskenn.="/".$rowf["nenner"];}
    $fsbuchflae=$rowf["amtlicheflaeche"]; // amtliche Fl. aus DB-Feld
	$fsgeomflae=$rowf["fsgeomflae"]; // aus Geometrie ermittelte Fläche
	$fsbuchflaed=number_format($fsbuchflae,0,",",".") . " m&#178;"; // Display-Format dazu
	$fsgeomflaed=number_format($fsgeomflae,0,",",".") . " m&#178;";
	$lgml=$rowf["lgml"]; // ID von "Lage Mit" oder leer

	echo "\n<tr>";
		echo "\n\t<td>";
		if ($showkey) {echo "<span class='key' title='Gemarkungsschlüssel'>".$rowf["land"].$rowf["gemarkungsnummer"]."</span> ";}
		echo "<span title='Gemarkungsname'>".$rowf["bezeichnung"]."</span></td>";
		echo "\n\t<td><span title='Flurnummer'>".$flur."</span></td>";
		echo "\n\t<td><span title='Flurstücksnummer in der Notation: Zähler/Nenner' class='wichtig'>".$fskenn."</span>";
		if ($idanzeige) {linkgml($gkz, $rowf["gml_id"], "Flurstück");}
		echo "</td>";
		echo "\n\t<td class='fla'><span title='geometrisch berechnet: ".$fsgeomflaed."'>".$fsbuchflaed."</span></td>";
		echo "\n\t<td class='hsnr'><span title='Hausnummer aus der Lagebezeichnung des Flurstücks'>".$rowf["hausnummer"]."</span></td>";
		echo "\n\t<td>\n\t\t<p class='nwlink noprint'>";

			if ($lgml != '') {
				echo "\n\t\t<a href='alkislage.php?gkz=".$gkz."&amp;ltyp=m&amp;gmlid=".$lgml;
				if ($idanzeige) {echo "&amp;id=j";}
				if ($showkey)   {echo "&amp;showkey=j";}
				echo "' title='Lagebezeichnung mit Hausnummer'>Lage <img src='ico/Lage_mit_Haus.ico' width='16' height='16' alt=''></a>&nbsp;";
			}

			echo "\n\t\t<a href='alkisfsnw.php?gkz=".$gkz."&amp;gmlid=".$rowf["gml_id"]."&amp;eig=n";
			if ($idanzeige) {echo "&amp;id=j";}
			if ($showkey)   {echo "&amp;showkey=j";}
			echo "' title='Flurstücksnachweis'>Flurstück <img src='ico/Flurstueck_Link.ico' width='16' height='16' alt=''></a>";

		echo "\n\t\t</p>\n\t</td>";
	echo "\n</tr>";
	$j++;
}
echo "\n</table>";
pg_free_result($res);
?>

<form action=''>
	<div class='buttonbereich noprint'>
	<hr class='thick'>
		<a title="zurück zur vorherigen Ansicht" href='javascript:history.back()'><img src="ico/zurueck.ico" width="16" height="16" alt="zurück"></a>&nbsp;
		<a title="drucken" href='javascript:window.print()'><img src="ico/print.ico" width="16" height="16" alt="Drucken"></a>&nbsp;
		<a title="als CSV-Datei exportieren" href='javascript:ALKISexport()'><img src="ico/download_fs.ico" width="32" height="16" alt="Export"></a>&nbsp;
	</div>
</form>

<?php footer($gmlid, $_SERVER['PHP_SELF']."?", "&amp;ltyp=".$ltyp); ?>

</body>
</html>
