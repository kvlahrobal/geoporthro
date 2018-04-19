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
	<title>Gebäudenachweis</title>
	<link rel="stylesheet" type="text/css" href="alkisauszug.css">
	<link rel="shortcut icon" type="image/x-icon" href="ico/Haus.ico">
	<style type='text/css' media='print'>
		.noprint {visibility: hidden;}
	</style>
</head>
<body>
<?php
$con = pg_connect("host=".$dbhost." port=" .$dbport." dbname=".$dbname." user=".$dbuser." password=".$dbpass);#." sslmode=".$sslmode);
if (!$con) echo "<p class='err'>Fehler beim Verbinden der DB</p>\n";

// Flurstueck
$sqlf ="SELECT f.name, f.gemarkung_land AS land, f.flurnummer, f.zaehler, f.nenner, f.flurstueckskennzeichen, f.amtlicheflaeche, st_area(f.wkb_geometry) AS fsgeomflae, f.zeitpunktderentstehung, g.gemarkungsnummer, g.bezeichnung ";
$sqlf.="FROM aaa_ogr.ax_flurstueck f ";
$sqlf.="LEFT JOIN aaa_ogr.ax_gemarkung g ON f.gemarkungsnummer = g.gemarkungsnummer ";
$sqlf.="WHERE f.endet IS NULL AND g.endet IS NULL AND f.gml_id = $1;";
$v=array($gmlid);
$resf=pg_prepare("", $sqlf);
$resf=pg_execute("", $v);
if (!$resf) {
	echo "\n<p class='err'>Fehler bei Flurstücksdaten.</p>\n";
	if ($debug > 2) {echo "<p class='err'>SQL=<br>".$sqlf."<br>$1 = gml_id = '".$gmlid."'</p>";}
}

if ($rowf = pg_fetch_array($resf)) {
	$gemkname=htmlentities($rowf["bezeichnung"], ENT_QUOTES, "UTF-8");
    $flurstueckskennzeichen=$rowf["flurstueckskennzeichen"];
	$land=$rowf["land"];
	$gmkgnr=$rowf["gemarkungsnummer"];
	$flurnummer=$rowf["flurnummer"];
	$flstnummer=$rowf["zaehler"];
	$nenner=$rowf["nenner"];
    $fsbuchflae=$rowf["amtlicheflaeche"]; // amtliche Fl. aus DB-Feld
	$fsgeomflae=$rowf["fsgeomflae"]; // aus Geometrie ermittelte Fläche
	$fsbuchflaed=number_format($fsbuchflae,0,",",".") . " m&#178;"; // Display-Format dazu
	$fsgeomflaed=number_format($fsgeomflae,0,",",".") . " m&#178;";
	if ($nenner > 0) { // BruchNr
		$flstnummer.="/".$nenner;
	}
} else {
	echo "<p class='err'>Fehler! Kein Treffer fuer gml_id=".$gmlid."</p>";
}

// Balken
echo "<p class='geb'>Flurstück <span title='Flurstückskennzeichen in der offiziellen ALKIS-Notation'>".$flurstueckskennzeichen."</span>&nbsp;</p>\n";

echo "\n<h2><img src='ico/Flurstueck.ico' width='16' height='16' alt=''> Flurstück</h2>\n";

// Kennzeichen in Rahmen
echo "\n<table class='outer'>\n<tr>\n<td>";
	echo "\n\t<table class='kennzfs'>\n\t<tr>";
		echo "\n\t\t<td class='head'>Gemarkung</td>\n\t\t<td class='head'>Flur</td>\n\t\t<td class='head'>Flurstück</td>\n\t</tr>";
		echo "\n\t<tr>\n\t\t<td title='Gemarkungsname'>";
		if ($showkey) {
			echo "<span class='key' title='Gemarkungsschlüssel'>".$land.$gmkgnr."</span><br>";
		}
		echo $gemkname."&nbsp;</td>";
		echo "\n\t\t<td title='Flurnummer'>".$flurnummer."</td>";
		echo "\n\t\t<td title='Flurstücksnummer in der Notation: Zähler/Nenner'><span class='wichtig'>".$flstnummer."</span></td>\n\t</tr>";
	echo "\n\t</table>";
echo "\n</td>\n<td>";

// Links zu anderen Nachweisen
echo "\n\t<p class='nwlink noprint'>";
	echo "\n\t\t<a href='alkisfsnw.php?gkz=".$gkz."&amp;gmlid=".$gmlid;
	if ($idanzeige) {echo "&amp;id=j";}
	if ($showkey)   {echo "&amp;showkey=j";}
	echo "&amp;eig=n' title='Flurstücksnachweis'>Flurstück <img src='ico/Flurstueck_Link.ico' width='16' height='16' alt=''></a>";
echo "\n\t</p>";
if ($idanzeige) {linkgml($gkz, $gmlid, "Flurstück"); }
echo "\n\t</td>\n</tr>\n</table>";
// Ende Seitenkopf

// Flurstuecksflaeche
echo "\n<p class='fsd' title='amtliche Fläche (Buchfläche) des Flurstücks'>Fläche: <span title='geometrisch berechnet: ".$fsgeomflaed."' class='flae'>".$fsbuchflaed."</span></p>\n";

pg_free_result($resf);

echo "\n<hr class='thick'>";

echo "\n<h2><img src='ico/Haus.ico' width='16' height='16' alt=''> Gebäude…</h2>";
echo "<p>…auf oder am Flurstück (ermittelt durch Verschneidung der Geometrien)</p>";

// G e b a e u d e
$sqlg ="SELECT g.gml_id, g.name[1] AS name, g.bauweise, g.gebaeudefunktion, bg.beschreibung AS bauweise_beschreibung, gf.beschreibung AS bezeichner, g.zustand, zg.beschreibung AS bzustand, ";
$sqlg.="round(st_area(g.wkb_geometry)::numeric,2) AS gebflae, ";
$sqlg.="round(st_area(ST_Intersection(g.wkb_geometry,f.wkb_geometry))::numeric,2) AS schnittflae, ";
$sqlg.="st_within(g.wkb_geometry,f.wkb_geometry) as drin ";
$sqlg.="FROM aaa_ogr.ax_flurstueck f, aaa_ogr.ax_gebaeude g ";
$sqlg.="JOIN aaa_ogr.ax_gebaeudefunktion gf ON g.gebaeudefunktion = gf.wert ";
$sqlg.="LEFT JOIN aaa_ogr.ax_bauweise_gebaeude bg ON g.bauweise = bg.wert ";
$sqlg.="LEFT JOIN aaa_ogr.ax_zustand_gebaeude zg ON g.zustand = zg.wert ";
$sqlg.="WHERE f.endet IS NULL AND g.endet IS NULL AND f.gml_id= $1 AND g.wkb_geometry && f.wkb_geometry AND st_intersects(g.wkb_geometry,f.wkb_geometry) IS TRUE ";
$sqlg.="ORDER BY schnittflae DESC;";
$v=array($gmlid);
$resg=pg_prepare("", $sqlg);
$resg=pg_execute("", $v);
if (!$resg) {
	echo "\n<p class='err'>Keine Gebäude ermittelt.</p>\n";
	if ($debug > 2) {echo "<p class='err'>SQL=<br>".$sqlg."<br>$1 = gml_id = '".$gmlid."'</p>";}
}
$gebnr=0;
echo "\n<table class='geb'>";
	// T-Header
	echo "\n<tr>\n";
		echo "\n\t<td class='head' title='Name des Gebäudes'>Name</td>";
		echo "\n\t<td class='head fla' title='Grundflächeanteil des Gebäudes auf dem Flurstück'>Grundfläche</td>";
		echo "\n\t<td class='head'>&nbsp;</td>";
		echo "\n\t<td class='head' title='Funktion des Gebäudes'>Funktion</td>";
		echo "\n\t<td class='head' title='Bauweise des Gebäudes'>Bauweise</td>";
		echo "\n\t<td class='head' title='Zustand des Gebäudes'>Zustand</td>";
		echo "\n\t<td class='head nwlink' title='Lagebezeichnung'>Lage</td>";
		echo "\n\t<td class='head nwlink' title='Verknüpfungen zu den vollständigen Gebäudedaten'>Haus</td>";
	echo "\n</tr>";
	// T-Body
	while($rowg = pg_fetch_array($resg)) {
		$gebnr = $gebnr + 1;
// ++ ToDo: Die Zeilen abwechselnd verschieden einfärben, Angrenzend anders einfärben 
		$ggml=$rowg["gml_id"];
		$gebflsum = $gebflsum + $rowg["schnittflae"];
		$skey=$rowg["lage"]; // Strassenschluessel		
		$gnam=$rowg["name"];
		$gzus=$rowg["zustand"];
		$gzustand=$rowg["bzustand"];

		echo "\n<tr>";
			echo "\n\t<td>";
				if ($gnam != "") {echo $gnam."<br>";}
			echo "\n\t</td>";

			if ($rowg["drin"] == "t") { // 3 komplett enthalten
				echo "\n\t<td class='fla'>".$rowg["schnittflae"]." m&#178;</td>"; 
				echo "\n\t<td>&nbsp;</td>";
			} else {
	       	if ($rowg["schnittflae"] == "0.00") { // angrenzend
					echo "\n\t<td class='fla'>&nbsp;</td>";
					echo "\n\t<td>angrenzend</td>";
				} else { // Teile enthalten
					echo "\n\t<td class='fla'>".$rowg["schnittflae"]." m&#178;</td>";
					echo "\n\t<td>(von ".$rowg["gebflae"]." m&#178;)</td>";
				}
			}
			echo "\n\t<td>";
			if ($showkey) {echo "<span class='key' title='Schlüssel der Funktion'>".$rowg["gebaeudefunktion"]."</span>&nbsp;";}
			echo $rowg["bezeichner"]."</td>";

			echo "\n\t<td>";
			if ($showkey) {echo "<span class='key' title='Schlüssel der Bauweise'>".$rowg["bauweise"]."</span>&nbsp;";}
			echo $rowg["bauweise_beschreibung"]."&nbsp;</td>";

			echo "\n\t<td>";
			if ($showkey) {echo "<span class='key' title='Schlüssel des Zustands'>".$gzus."</span>&nbsp;";}
			echo $gzustand."&nbsp;</td>";

			echo "\n\t<td class='nwlink noprint'>";

			// 0 bis N Lagebezeichnungen mit Haus- oder Pseudo-Nummer, alle in ein TD zu EINEM Gebäude
            // HAUPTgebäude
            $sqll ="SELECT 'm' AS ltyp, l.gml_id, s.lage, s.bezeichnung, l.hausnummer, '' AS laufendenummer ";
            $sqll.="FROM aaa_ogr.ax_gebaeude g "; 
            $sqll.="JOIN aaa_ogr.ax_lagebezeichnungmithausnummer l ON l.gml_id = ANY(g.zeigtauf) ";
            $sqll.="JOIN aaa_ogr.ax_lagebezeichnungkatalogeintrag s ON l.kreis=s.kreis AND l.gemeinde=s.gemeinde AND l.lage=s.lage ";
            $sqll.="WHERE g.gml_id = $1 AND g.endet IS NULL AND l.endet IS NULL AND s.endet IS NULL ";
            $sqll.="UNION ";
            // oder NEBENgebäude
            $sqll.="SELECT 'p' AS ltyp, l.gml_id, s.lage, s.bezeichnung, l.pseudonummer AS hausnummer, l.laufendenummer ";
            $sqll.="FROM aaa_ogr.ax_gebaeude g "; 
            $sqll.="JOIN aaa_ogr.ax_lagebezeichnungmitpseudonummer l ON l.gml_id = g.hat ";
            $sqll.="JOIN aaa_ogr.ax_lagebezeichnungkatalogeintrag s ON l.kreis=s.kreis AND l.gemeinde=s.gemeinde AND l.lage=s.lage ";
            $sqll.="WHERE g.gml_id = $1 AND g.endet IS NULL AND l.endet IS NULL AND s.endet IS NULL ";
            $sqll.="ORDER BY bezeichnung, hausnummer ";
			$v = array($ggml);
			$resl = pg_prepare("", $sqll);
			$resl = pg_execute("", $v);
			if (!$resl) {
				echo "\n<p class='err'>Fehler bei Lage mit HsNr.</p>\n";
				if ($debug > 2) {echo "<p class='dbg'>SQL=<br>".$sqll."<br>$1 = gml_id = '".$gmlid."'</p>";}
			}
			while($rowl = pg_fetch_array($resl)) { // LOOP: Lagezeilen
				$ltyp=$rowl["ltyp"]; // Lagezeilen-Typ
				$skey=$rowl["lage"]; // Str.-Schluessel
				$snam=htmlentities($rowl["bezeichnung"], ENT_QUOTES, "UTF-8"); // -Name
				$hsnr=$rowl["hausnummer"];
				$hlfd=$rowl["laufendenummer"];
				$gmllag=$rowl["beziehung_zu"];
				if ($ltyp == "p") {
					$lagetitl="Lagebezeichnung Nebengebäude";
					$lagetxt="Nebengebäude Nr. ".$hlfd;
				} else {
					$lagetitl="Lagebezeichnung mit Hausnummer";
					$lagetxt=$snam."&nbsp;".$hsnr;
				}
				echo "\n\t\t<img src='ico/Lage_mit_Haus.ico' width='16' height='16' alt=''>&nbsp;";
				if ($showkey) {echo "<span class='key' title='Straßenschlüssel'>(".$skey.")</span>&nbsp;";}			
				echo "\n\t\t<a title='".$lagetitl."' href='alkislage.php?gkz=".$gkz."&amp;gmlid=".$gmllag."&amp;ltyp=".$ltyp;
					if ($idanzeige) {echo "&amp;id=j";}
					if ($showkey)   {echo "&amp;showkey=j";}
				echo "'>".$lagetxt."</a>";
				if ($idanzeige) {linkgml($gkz, $gmllag, "Lage"); }
				echo "<br>";
			} // Ende Loop Lagezeilen m.H.
            pg_free_result($resl);
			echo "\n\t</td>";

			echo "\n\t<td class='nwlink noprint'>";
				echo "\n\t\t<a title='vollständige Gebäudedaten' href='alkishaus.php?gkz=".$gkz."&amp;gmlid=".$ggml;
				if ($idanzeige) {echo "&amp;id=j";}
				if ($showkey)   {echo "&amp;showkey=j";}
				echo "'><img src='ico/Haus.ico' width='16' height='16' alt=''></a>";
			echo "\n\t</td>";

		echo "\n</tr>";
	}
// Footer
	if ($gebnr == 0) {
		echo "\n</table>";
		echo "<p class='err'><br>Keine Gebäude auf diesem Flurstück.<br>&nbsp;</p>";
	} else {
		echo "\n<tr>";
			echo "\n\t<td><b>Summe</b></td>"; // 1
            echo "\n\t<td class='fla sum'>".number_format($gebflsum,0,",",".")." m&#178;</td>";
			echo "\n\t<td>&nbsp;</td>"; // 3
			echo "\n\t<td>&nbsp;</td>"; // 4
			echo "\n\t<td>&nbsp;</td>"; // 5
			echo "\n\t<td>&nbsp;</td>"; // 6
			echo "\n\t<td>&nbsp;</td>"; // 7
		echo "\n</tr>";
	echo "\n</table>";
	$unbebaut = number_format(($fsbuchflae - $gebflsum),0,",",".") . " m&#178;";
	echo "\n<p>amtliche Fläche (Buchfläche) des Flurstücks abzüglich Gebäudegrundfläche(n): <b>".$unbebaut."</b></p><br>";
}
pg_free_result($resg);
?>

<form action=''>
	<div class='buttonbereich noprint'>
	<hr class='thick'>
		<a title="zurück zur vorherigen Ansicht" href='javascript:history.back()'><img src="ico/zurueck.ico" width="16" height="16" alt="zurück"></a>&nbsp;
		<a title="drucken" href='javascript:window.print()'><img src="ico/print.ico" width="16" height="16" alt="Drucken"></a>
	</div>
</form>

<?php footer($gmlid, $_SERVER['PHP_SELF']."?", ""); ?>

</body>
</html>
