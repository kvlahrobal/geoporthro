<?php
//ini_set('error_reporting', 'E_ALL & ~ E_NOTICE');
session_start();
import_request_variables("G");
//$gkz=urldecode($_REQUEST["gkz"]);
require_once("alkis_conf_location.php");
if ($auth == "mapbender") {require_once($mapbender);}
// $gmlid=urldecode($_REQUEST["gmlid"]);
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
	<meta name="author" content="F. Jaeger krz" >
	<meta http-equiv="cache-control" content="no-cache">
	<meta http-equiv="pragma" content="no-cache">
	<meta http-equiv="expires" content="0">
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<title>Bau-, Raum- oder Bodenordnungsrecht</title>
	<link rel="stylesheet" type="text/css" href="alkisauszug.css">
	<link rel="shortcut icon" type="image/x-icon" href="ico/Gericht.ico">
	<base target="_blank">
</head>
<body>

<?php
$con = pg_connect("host=".$dbhost." port=" .$dbport." dbname=".$dbname." user=".$dbuser." password=".$dbpass);#." sslmode=".$sslmode);
if (!$con) echo "<p class='err'>Fehler beim Verbinden der DB</p>\n";
if ($debug > 1) {echo "<p class='err'>DB=".$dbname.", user=".$dbuser."</p>";}

// wie View "baurecht"
$sql ="SELECT r.ogc_fid, r.artderfestlegung as adfkey, r.name, r.stelle, r.bezeichnung AS rechtbez, ";
$sql.="a.beschreibung AS adfbez, d.bezeichnung AS stellbez, d.stellenart, ";
$sql.="round(st_area(r.wkb_geometry)::numeric,0) AS flae ";
$sql.="FROM aaa_ogr.ax_bauraumoderbodenordnungsrecht r ";
$sql.="JOIN aaa_ogr.ax_artderfestlegung_bauraumoderbodenordnungsrecht a ON r.artderfestlegung = a.wert ";
$sql.="LEFT JOIN aaa_ogr.ax_dienststelle d ON r.land = d.land AND r.stelle = d.stelle ";
$sql.="WHERE r.gml_id= $1 AND r.endet IS NULL AND d.endet IS NULL ;";
$v = array($gmlid);
$res = pg_prepare("", $sql);
$res = pg_execute("", $v);

if (!$res) {
	echo "\n<p class='err'>Fehler bei Baurecht.</p>\n";
	if ($debug > 2) {echo "<p class='err'>SQL=<br>".$sql."<br>$1 = ".$gmlid."</p>\n";}
}
echo "\n<h2><img src='ico/Gericht.ico' width='16' height='16' alt=''> Bau-, Raum- oder Bodenordnungsrecht</h2>\n";

if ($row = pg_fetch_array($res)) {
	echo "\n<table>";

		echo "\n<tr>";
			echo "\n\t<td><b>Festlegung</b></td>\n\t<td><span class='wichtig'>";
			echo $row["adfbez"]."</span></td>";
		echo "\n</tr>";

		$enam=$row["name"];
		if ($enam != "") {
			echo "\n<tr>";
				echo "\n\t<td><b>Eigenname des Gebietes</b></td>\n\t<td>".$enam."</td>";
			echo "\n</tr>";
		}

		$stell=$row["stelle"];
		if ($stell != "") {
			echo "\n<tr>";
				echo "\n\t<td><b>Dienststelle</b></td>\n\t<td>".$row["stellbez"]."</td>";
			echo "\n</tr>";
		}
		echo "\n<tr>";
			echo "\n\t<td><b>Verfahren</b></td>";
			echo "\n\t<td>".$row["rechtbez"]."</td>";
		echo "\n</tr>";

		echo "\n<tr>";
			echo "\n\t<td><b>Fläche</b></td>";
			$flae=number_format($row["flae"],0,",",".");
			echo "\n\t<td>".$flae." m²</td>";
		echo "\n</tr>";

	echo "\n</table>";
} else {
	echo "\n<p class='err'>Fehler! Kein Treffer bei gml_id=".$gmlid."</p>";
}
echo "\n<hr class='thick'>";
echo "\n<h2><img src='ico/Flurstueck.ico' width='16' height='16' alt=''> betroffene Flurstücke…</h2>\n";
echo "\n<p>…(ermittelt durch Verschneidung der Geometrien; nach Größe absteigend sortiert)</p>";

$sql ="SELECT f.gml_id, f.gemarkungsnummer, f.flurnummer, f.zaehler, f.nenner, f.amtlicheflaeche, f.realflaeche AS realflaeche, CASE WHEN round(f.realflaeche::numeric, 2)::text ~ '50$' AND round(f.realflaeche::numeric, 2) >= 1 THEN CASE WHEN (trunc(f.realflaeche)::int % 2) = 0 THEN trunc(f.realflaeche) ELSE round(round(f.realflaeche::numeric, 2)::numeric) END WHEN round(f.realflaeche::numeric, 2) < 1 THEN round(f.realflaeche::numeric, 2) ELSE round(f.realflaeche::numeric) END AS realflaeche_geodaetisch_gerundet, ST_Area(f.wkb_geometry) AS geomflaeche, ";
$sql.="round(st_area(ST_Intersection(r.wkb_geometry,f.wkb_geometry))::numeric,1) AS schnittflae ";
$sql.="FROM aaa_ogr.ax_flurstueck f, aaa_ogr.ax_bauraumoderbodenordnungsrecht r  ";
$sql.="WHERE r.gml_id= $1 AND r.endet IS NULL AND f.endet IS NULL "; 
$sql.="AND r.wkb_geometry && f.wkb_geometry ";
$sql.="AND st_intersects(r.wkb_geometry,f.wkb_geometry) IS TRUE ";
$sql.="AND st_area(st_intersection(r.wkb_geometry,f.wkb_geometry)) > 0.05 ";  // > 0.0 ist gemeint, Ungenauigkeit durch st_simplify
$sql.="AND st_isvalid(r.wkb_geometry) AND st_isvalid(f.wkb_geometry) "; 
$sql.="ORDER BY schnittflae DESC ";
// Limit: Flurbereinigungsgebiete koennen sehr gross werden!
$sql.="LIMIT 40;";
// Trotz Limit lange Antwortzeit, wegen OrderBy -> intersection
$v = array($gmlid);
$res = pg_prepare("", $sql);
$res = pg_execute("", $v);

if (!$res) {
	echo "\n<p class='err'>Keine Flurstücke ermittelt.<br>\nSQL=<br></p>\n";
	if ($debug > 2) {echo "<p class='err'>SQL=<br>".$sql."<br>$1 = ".$gmlid."</p>\n";}
}

echo "\n<table class='fs'>";
	echo "\n<tr>"; // Header
		echo "\n\t<td class='head'>Flurstückskennzeichen</td>";
		echo "\n\t<td class='head fla' title='vom Recht betroffene Fläche des Flurstücks'>Fläche</td>";
		echo "\n\t<td class='head fla' title='amtliche Fläche (Buchfläche) des Flurstücks'>von</td>";
		echo "\n\t<td class='head nwlink'>weitere Auskunft</td>";
	echo "\n</tr>";

	$fscnt=0;
	while($row = pg_fetch_array($res)) {
		$fscnt++;
		echo "\n<tr>";
			echo "\n\t<td>".$row["gemarkungsnummer"]."-".$row["flurnummer"]."-<span class='wichtig'>".$row["zaehler"];
			$nen=$row["nenner"];
			if ($nen != "") {
				echo "/".$nen;
			}
			echo "</span></td>";
            $amtlicheflaeche=$row["amtlicheflaeche"]; // amtliche Fläche
            $amtlicheflaeched=($amtlicheflaeche < 1 ? rtrim(number_format($amtlicheflaeche,2,",","."),"0") : number_format($amtlicheflaeche,0,",",".")); // Display-Format dazu
            $realflaeche=$row["realflaeche"]; // reale Fläche
            $realflaeche_geodaetisch_gerundet=$row["realflaeche_geodaetisch_gerundet"]; // geodätisch gerundeter Wert der realen Fläche
            $realflaeche_geodaetisch_gerundetd=($realflaeche_geodaetisch_gerundet < 1 ? rtrim(number_format($realflaeche_geodaetisch_gerundet,2,",","."),"0") : number_format($realflaeche_geodaetisch_gerundet,0,",",".")); // Display-Format dazu
            $geomflaeche=$row["geomflaeche"]; // aus Geometrie ermittelte Fläche
            $the_Xfactor=$amtlicheflaeche / $geomflaeche; // Verhältnis zwischen aus Geometrie ermittelter und amtlicher Fläche
            $absflaebuch = $row["schnittflae"] * $the_Xfactor; // verhältnismäßiges Angleichen der Schnittfläche an die amtliche Fläche
            $absflaebuchd=($absflaebuch < 1 ? rtrim(number_format($absflaebuch,2,",","."),"0") : number_format($absflaebuch,0,",",".")); // Display-Format dazu
			echo "\n\t<td class='fla'><span>".$absflaebuchd." m²</span></td>"; 
			echo "\n\t<td class='fla'><span title='geometrisch berechnet, reduziert und geodätisch gerundet: ".$realflaeche_geodaetisch_gerundetd." m²'>".$amtlicheflaeched." m²</span></td>";
			echo "\n\t<td class='nwlink noprint'>";
				echo "\n\t\t<a target='_blank' href='alkisfsnw.php?gkz=".$gkz."&amp;gmlid=".$row["gml_id"];
					echo "' title='Flurstücksnachweis'>Flurstück ";
					echo "\n\t\t\t<img src='ico/Flurstueck_Link.ico' width='16' height='16' alt=''>";
				echo "\n\t\t</a>";
			echo "\n\t</td>";
		echo "\n</tr>";
	}
echo "\n</table>";

if ($fscnt == 40) {
	echo "<p>... und weitere Flurstücke (Limit 40 erreicht).</0>";
}

?>

</body>
</html>
