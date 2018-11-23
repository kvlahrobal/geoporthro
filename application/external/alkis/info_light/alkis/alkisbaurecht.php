<?php
//session_start();
$cntget = extract($_GET);
require_once("alkis_conf_location.php");
if ($auth == "mapbender") {require_once($mapbender);}
include("alkisfkt.php"); // f. Footer
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
	<title>Bau-, Raum- oder Bodenordnungsrecht</title>
	<link rel="stylesheet" type="text/css" href="alkisauszug.css">
	<link rel="shortcut icon" type="image/x-icon" href="ico/Gericht.ico">
</head>
<body>

<?php
$con = pg_connect("host=".$dbhost." port=" .$dbport." dbname=".$dbname." user=".$dbuser." password=".$dbpass);#." sslmode=".$sslmode);
if (!$con) echo "<p class='err'>Fehler beim Verbinden der DB</p>\n";

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

if ($row = pg_fetch_array($res)) {
	$artfest=$row["adfkey"];  // Art der Festlegung, Key
	$verfnr=$row["rechtbez"]; // Verfahrens-Nummer
	$enam=$row["name"];
	$stellk=$row["stelle"]; // LEFT JOIN !
	$stellb=$row["stellbez"];
	$stella=$row["stellenart"];

	// Balken
	echo "<p class='recht'>Bau-, Raum- oder Bodenordnungsrecht ".$artfest."-".$verfnr."&nbsp;</p>\n";

	echo "\n<h2><img src='ico/Gericht.ico' width='16' height='16' alt=''> Bau-, Raum- oder Bodenordnungsrecht</h2>\n";

	echo "\n<table>";
    
        echo "\n<tr>";
			echo "\n\t<td><b>Festlegung</b></td>\n\t<td>";
			if ($showkey) {
				echo "<span class='key' title='Schlüssel der Festlegung'>(".$artfest.")</span> ";
			}
			echo "<span class='wichtig'>".$row["adfbez"]."</span></td>";
		echo "\n</tr>";

		if ($enam != "") {
			echo "\n<tr>";
				echo "\n\t<td><b>Eigenname des Gebietes</b></td>\n\t<td>".$enam."</td>";
			echo "\n</tr>";
		}

		if ($stellb != "") { // z.B. Umlegung mit und Baulast ohne Dienststelle
			echo "\n<tr>";
				echo "\n\t<td><b>Dienststelle</b></td>\n\t<td>";
					if ($showkey) {echo "<span class='key' title='Dienststellenschlüssel'>(".$stellk.")</span> ";}
					echo $stellb;
				echo "</td>";
			echo "\n</tr>";
		}

		if ($verfnr != "") {
			echo "\n<tr>";
				echo "\n\t<td><b>Verfahren</b></td>";
				echo "\n\t<td>".$verfnr."</td>";
				// if ($idanzeige) {linkgml($gkz, $gmlid, "Verfahren"); } // KEINE Bez.!
			echo "\n</tr>";
		}

		echo "\n<tr>";
			echo "\n\t<td><b>Fläche</b></td>";
			$flae=number_format($row["flae"],0,",",".")." m&#178;";
			echo "\n\t<td>".$flae."</td>";
		echo "\n</tr>";

	echo "\n</table>";
} else {
	echo "\n<p class='err'>Fehler! Kein Treffer bei gml_id=".$gmlid."</p>";
}
echo "\n<hr class='thick'>";
echo "\n<h2><img src='ico/Flurstueck.ico' width='16' height='16' alt=''> betroffene Flurstücke…</h2>\n";
echo "\n<p>…(ermittelt durch Verschneidung der Geometrien; nach Größe absteigend sortiert)</p>";

$sql ="SELECT f.gml_id, f.gemarkungsnummer, f.flurnummer, f.zaehler, f.nenner, f.amtlicheflaeche, f.realflaeche AS fsgeomflae, ";
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
            $fsbuchflae=$row["amtlicheflaeche"]; // amtliche Fl. aus DB-Feld
            $fsgeomflae=$row["fsgeomflae"]; // aus Geometrie ermittelte Fläche
            $fsbuchflaed=number_format($fsbuchflae,0,",",".") . " m&#178;"; // Display-Format dazu
            $fsgeomflaed=number_format($fsgeomflae,0,",",".") . " m&#178;";
            $schnittflae=number_format($row["schnittflae"],0,",",".") . " m&#178;";
			echo "\n\t<td class='fla'><span title='geometrisch berechnet'>".$schnittflae."</span></td>"; 
			echo "\n\t<td class='fla'><span title='geometrisch berechnet: ".$fsgeomflaed."'>".$fsbuchflaed."</span></td>";
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
	echo "<p>... und weitere Flurstücke (Limit 40 erreicht).</p>";
}

pg_close($con);
echo <<<END

<form action=''>
	<div class='buttonbereich noprint'>
	<hr>
		<a title="zurück" href='javascript:history.back()'><img src="ico/zurueck.ico" width="16" height="16" alt="zurück" /></a>&nbsp;
		<a title="Drucken" href='javascript:window.print()'><img src="ico/print.ico" width="16" height="16" alt="Drucken" /></a>&nbsp;
	</div>
</form>
END;

footer($gmlid, $_SERVER['PHP_SELF']."?", "");

?>

</body>
</html>
