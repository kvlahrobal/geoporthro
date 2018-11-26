<?php
//session_start();
$cntget = extract($_GET);
require_once("alkis_conf_location.php");
if ($auth == "mapbender") {require_once($mapbender);}
include("alkisfkt.php");
switch ($ltyp) {
	case "m": // "Mit HsNr"     = Hauptgebaeude
		$tnam = "ax_lagebezeichnungmithausnummer"; break;
	case "p": // "mit PseudoNr" = Nebengebaeude
		$tnam = "ax_lagebezeichnungmitpseudonummer";	break;
	case "o": //"Ohne HsNr"    = Gewanne oder Strasse
		$tnam = "ax_lagebezeichnungohnehausnummer"; break;
	default:
		$ltyp = "m";
		$tnam = "ax_lagebezeichnungmithausnummer"; break;
}
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
	<title>Lagebezeichnung</title>
	<link rel="stylesheet" type="text/css" href="alkisauszug.css">
	<link rel="shortcut icon" type="image/x-icon" href="ico/Lage_mit_Haus.ico">
	<style type='text/css' media='print'>
		.noprint {visibility: hidden;}
	</style>
</head>
<body>
<?php
$con = pg_connect("host=".$dbhost." port=" .$dbport." dbname=".$dbname." user=".$dbuser." password=".$dbpass);#." sslmode=".$sslmode);
if (!$con) echo "<p class='err'>Fehler beim Verbinden der DB</p>\n";

// L a g e b e z e i c h n u n g
$sql ="SELECT s.gml_id AS strgml, s.bezeichnung AS snam, b.bezeichnung AS bnam, r.bezeichnung AS rnam, k.bezeichnung AS knam, g.bezeichnung AS gnam, l.land, l.regierungsbezirk, l.kreis, l.gemeinde, l.lage, ";
switch ($ltyp) {
	case "m": // "Mit HsNr"
		$sql.="l.hausnummer ";
	break;
	case "p": // "mit PseudoNr"
		$sql.="l.pseudonummer, l.laufendenummer ";
	break;
	case "o": //"Ohne HsNr"
		$sql.="l.unverschluesselt ";
	break;
}
$sql.="FROM aaa_ogr.".$tnam." l "; // Left: Bei sub-Typ "Gewanne" von Typ "o" sind keine Schlüsselfelder gefüllt!
$sql.="LEFT JOIN aaa_ogr.ax_gemeinde g ON l.land=g.gemeindekennzeichen_land AND l.regierungsbezirk=g.regierungsbezirk AND l.kreis=g.kreis AND l.gemeinde=g.gemeinde ";
$sql.="LEFT JOIN aaa_ogr.ax_kreisregion k ON l.land=k.schluessel_land AND l.regierungsbezirk=k.regierungsbezirk AND l.kreis=k.kreis ";
$sql.="LEFT JOIN aaa_ogr.ax_regierungsbezirk r ON l.land=r.land AND l.regierungsbezirk=r.regierungsbezirk ";
$sql.="LEFT JOIN aaa_ogr.ax_bundesland b ON l.land=b.schluessel_land ";
$sql.="LEFT JOIN aaa_ogr.ax_lagebezeichnungkatalogeintrag s ON l.land=s.land AND l.regierungsbezirk=s.regierungsbezirk AND l.kreis=s.kreis AND l.gemeinde=s.gemeinde AND l.lage=s.lage ";
$sql.="WHERE l.gml_id= $1 AND l.endet IS NULL AND g.endet IS NULL AND k.endet IS NULL AND r.endet IS NULL AND b.endet IS NULL AND s.endet IS NULL;";
$v = array($gmlid);
$res = pg_prepare("", $sql);
$res = pg_execute("", $v);
if (!$res) {
	echo "\n<p class='err'>Fehler bei Lagebezeichnung.</p>\n";
	if ($debug > 2) {echo "<p class='err'>SQL=<br>".$sql."<br>$1 = gml_id = '".$gmlid."'</p>";}
}

if ($row = pg_fetch_array($res)) {
	$strgml=$row["strgml"]; // gml_id des Katalogeintrag Straße
	$regbez=$row["regierungsbezirk"];
	$land=$row["land"];
	$kreis=$row["kreis"];
	$knam=$row["knam"];
	$rnam=$row["rnam"];
	$bnam=$row["bnam"];
	$gem=$row["gemeinde"];
	$gnam=$row["gnam"];
	$lage=$row["lage"]; // Strassenschluessel
	$snam=$row["snam"]; //Strassennamen
	$unver=$row["unverschluesselt"]; // Gewanne
	
	switch ($ltyp) {
		case "m": // "Mit HsNr"
			$hsnr=$row["hausnummer"];
			$untertitel="Hauptgebäude mit Hausnummer";
			// Balken
			echo "<p class='lage'>Lagebezeichnung mit Hausnummer ".$snam." <span title='Straßenschlüssel'>(".$lage.")</span> ".$hsnr."&nbsp;</p>\n"; // Balken
			$osub="";
		break;
		case "p": // "mit PseudoNr"
			$pseu=$row["pseudonummer"];
			$lfd=$row["laufendenummer"];
			$untertitel="Nebengebäude mit Pseudonummer und laufender Nummer";
			echo "<p class='lage'>Lagebezeichnung Nebengebäude ".$snam." <span title='Straßenschlüssel'>(".$lage.")</span> ".$pseu."-".$lfd."&nbsp;</p>\n"; // Balken
			$osub="";
		break;
		case "o": // "Ohne HsNr"
			// 2 Unterarten bzw. Zeilen-Typen in der Tabelle
			if ($lage == "") {
				$osub="g"; // Sub-Typ Gewanne
				$untertitel="Gewann (unverschlüsselte Lage)";
				echo "<p class='lage'>Lagebezeichnung Gewann ".$unver."&nbsp;</p>\n"; // Balken
			} else {
				$osub="s"; // Sub-Typ Strasse (ohne HsNr)
				$untertitel="Straße ohne Hausnummer(n)";
				echo "<p class='lage'>Lagebezeichnung Straße ".$snam." (".$lage.")&nbsp;</p>\n"; // Balken
			}
		break;
	}
} else {
	echo "<p class='err'>Fehler! Kein Treffer fuer gml_id=".$gmlid."</p>";
}

echo "\n<h2><img src='ico/Lage_mit_Haus.ico' width='16' height='16' alt='HAUS'> Lagebezeichnung</h2>\n";

echo "<p>".$untertitel."</p>";

echo "\n<table class='outer'>\n<tr>\n\t<td>"; 	// Tabelle Kennzeichen
	// ToDo: !! kleiner, wenn ltyp=0 und die Schluesselfelder leer sind
	echo "\n\t<table class='kennzla'>";
		echo "\n\t<tr>";
			if ($osub != "g") { // nicht bei Gewanne
				echo "\n\t\t<td class='head'>Land</td>";
				echo "\n\t\t<td class='head'>Kreis</td>";
				echo "\n\t\t<td class='head'>Gemeinde</td>";
				echo "\n\t\t<td class='head'>Straße</td>";
			}
			switch ($ltyp) {
				case "m": // "Mit HsNr"
					echo "\n\t\t<td class='head'>Hausnummer</td>";
				break;
				case "p": // "mit PseudoNr"
					echo "\n\t\t<td class='head'>Pseudonummer</td>";
					echo "\n\t\t<td class='head'>lfd.</td>";
				break;
				case "o": //"Ohne HsNr"
					if ($osub == "g") {
						echo "\n\t\t<td class='head'>unverschlüsselte Lage</td>";
					}
				break;
			}
		echo "\n\t</tr>";
		echo "\n\t<tr>";
			if ($osub != "g") { // nicht bei Gewanne
                echo "\n\t\t<td>";
				if ($showkey and $osub != "g") {echo "<span title='Landesschlüssel (= erste 2 Stellen des Regionalschlüssels)' class='key'>(".$land.")</span><br>";}
				echo $bnam."&nbsp;</td>";
                
				echo "\n\t\t<td>";
				if ($showkey and $osub != "g") {echo "<span title='Kreisschlüssel (= erste 5 Stellen des Regionalschlüssels)' class='key'>(".$land.str_pad($kreis, 3, "0", STR_PAD_LEFT).")</span><br>";}
				echo $knam."&nbsp;</td>";

				echo "\n\t\t<td>";
				if ($showkey and $osub != "g") {echo "<span title='Gemeindeschlüssel (= Regionalschlüssel)' class='key'>(".$land.str_pad($kreis, 3, "0", STR_PAD_LEFT).str_pad($gem, 7, "0", STR_PAD_LEFT).")</span><br>";}
				echo $gnam."&nbsp;</td>";

				echo "\n\t\t<td>";
				if ($showkey and $osub != "g") {echo "<span title='Straßenschlüssel' class='key'>(".$lage.")</span><br>";}
				if ($ltyp == "o") {
					echo "<span class='wichtig'>".$snam."</span>";
				} else {
					echo $snam;
				}	
				echo "&nbsp;</td>";
			}

			switch ($ltyp) {
				case "m":
					echo "\n\t\t<td title='Hausnummer (mit Hausnummerzusatz)'><span class='wichtig'>".$hsnr."</span></td>";
				break;
				case "p":
					echo "\n\t\t<td title='Pseudonummer (mit Pseudonummerzusatz)'>".$pseu."</td>";
					echo "\n\t\t<td title='laufende Nummer des Nebengebäudes'><span class='wichtig'>".$lfd."</span></td>";
				break;
				case "o":
					if ($osub == "g") {
						echo "\n\t\t<td title='Gewann'><span class='wichtig'>".$unver."</span></td>";
					}
				break;
			}
		echo "\n\t</tr>";
	echo "\n\t</table>";

	echo "\n\t</td>\n\t<td>";

	// Kopf Rechts: weitere Daten?
	if ($idanzeige) {linkgml($gkz, $gmlid, "Lage"); }

	if ($osub != "g") { // Link zu Strasse
		echo "\n\t\t<p class='nwlink noprint'>";
			echo "\n\t\t<a href='alkisstrasse.php?gkz=".$gkz."&amp;gmlid=".$strgml;
				if ($idanzeige) {echo "&amp;id=j";}
				if ($showkey)   {echo "&amp;showkey=j";}
			echo "' title='Flurstücke an dieser Straße'>Straße <img src='ico/Strassen.ico' width='16' height='16' alt=''></a>";
		echo "\n\t\t</p>";
	}

echo "\n\t</td>\n</tr>\n</table>";
// Ende Seitenkopf

echo "\n<hr class='thick'>";

// F L U R S T U E C K E
	// ax_Flurstueck  >weistAuf>  ax_LagebezeichnungMitHausnummer
	// ax_Flurstueck  >zeigtAuf>  ax_LagebezeichnungOhneHausnummer
if ($ltyp <> "p") { // Pseudonummer linkt nur Gebäude
	echo "\n\n<a name='fs'></a><h2><img src='ico/Flurstueck.ico' width='16' height='16' alt=''> Flurstücke…</h2>\n";
    echo "\n<p>…mit dieser Lagebezeichnung</p>";
    $sql="SELECT g.gemarkungsnummer, g.bezeichnung, ";
	$sql.="f.gml_id, f.flurnummer, f.gemarkung_land AS land, f.zaehler, f.nenner, f.amtlicheflaeche, CASE WHEN round(f.realflaeche::numeric, 2)::text ~ '50$' AND round(f.realflaeche::numeric, 2) >= 1 THEN CASE WHEN (trunc(f.realflaeche)::int % 2) = 0 THEN trunc(f.realflaeche) ELSE round(round(f.realflaeche::numeric, 2)::numeric) END WHEN round(f.realflaeche::numeric, 2) < 1 THEN round(f.realflaeche::numeric, 2) ELSE round(f.realflaeche::numeric) END AS realflaeche_geodaetisch_gerundet ";
	$sql.="FROM aaa_ogr.ax_flurstueck f ";
    $sql.="LEFT JOIN aaa_ogr.ax_gemarkung g ON f.gemarkung_land=g.schluessel_land AND f.gemarkungsnummer = g.gemarkungsnummer ";
    $sql.="WHERE g.endet IS NULL AND f.endet IS NULL AND ($1 = ANY(f.weistauf) OR $1 = ANY(f.zeigtauf))";
	$sql.="ORDER BY f.gemarkungsnummer, f.flurnummer, f.zaehler::int, f.nenner::int;";
	$v = array($gmlid);
	$resf = pg_prepare("", $sql);
	$resf = pg_execute("", $v);
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
        echo "\n\t<td class='head nwlink noprint' title='weitere Auskunft'>weitere Auskunft</td>";
	echo "\n</tr>";
	$j=0;
	while($rowf = pg_fetch_array($resf)) {
		$flur=$rowf["flurnummer"];
		$fskenn=$rowf["zaehler"]; // Bruchnummer
    $amtlicheflaeche=$rowf["amtlicheflaeche"]; // amtliche Fläche
    $amtlicheflaeched=($amtlicheflaeche < 1 ? rtrim(number_format($amtlicheflaeche,2,",","."),"0") : number_format($amtlicheflaeche,0,",",".")); // Display-Format dazu
    $realflaeche_geodaetisch_gerundet=$rowf["realflaeche_geodaetisch_gerundet"]; // geodätisch gerundeter Wert der realen Fläche
    $realflaeche_geodaetisch_gerundetd=($realflaeche_geodaetisch_gerundet < 1 ? rtrim(number_format($realflaeche_geodaetisch_gerundet,2,",","."),"0") : number_format($realflaeche_geodaetisch_gerundet,0,",",".")); // Display-Format dazu
		if ($rowf["nenner"] != "") {$fskenn.="/".$rowf["nenner"];}
		echo "\n<tr>";
			echo "\n\t<td>";
			if ($showkey) {echo "<span class='key' title='Gemarkungsschlüssel'>".$rowf["land"].$rowf["gemarkungsnummer"]."</span> ";}
			echo "<span title='Gemarkungsname'>".$rowf["bezeichnung"]."</td>";
			echo "\n\t<td><span title='Flurnummer'>".$flur."</span></td>";
			echo "\n\t<td><span title='Flurstücksnummer in der Notation: Zähler/Nenner' class='wichtig'>".$fskenn."</span>";
				if ($idanzeige) {linkgml($gkz, $rowf["gml_id"], "Flurstück");}
			echo "</td>";
			echo "\n\t<td class='fla'><span title='geometrisch berechnet, reduziert und geodätisch gerundet: ".$realflaeche_geodaetisch_gerundetd." m²'>".$amtlicheflaeched." m²</span></td>";
			echo "\n\t<td>\n\t\t<p class='nwlink noprint'>";
				echo "\n\t\t<a href='alkisfsnw.php?gkz=".$gkz."&amp;gmlid=".$rowf["gml_id"]."&amp;eig=n";
					if ($idanzeige) {echo "&amp;id=j";}
					if ($showkey)   {echo "&amp;showkey=j";}
				echo "' title='Flurstücksnachweis'>Flurstück <img src='ico/Flurstueck_Link.ico' width='16' height='16' alt=''></a>";
			echo "\n\t\t</p>\n\t</td>";
		echo "\n</tr>";
		$j++;
	}
	echo "\n</table>";
}

// G E B A E U D E
if ($ltyp <> "o") { // OhneHsNr linkt nur Flurst.
	echo "\n\n<a name='geb'></a><h2><img src='ico/Haus.ico' width='16' height='16' alt=''> Gebäude…</h2>";
	echo "\n<p>…mit dieser Lagebezeichnung</p>";
    $sql ="SELECT g.gml_id, g.gebaeudefunktion, g.name[1] AS name, g.bauweise, g.grundflaeche, g.zustand, ";
	$sql.="round(st_area(g.wkb_geometry)::numeric,2) AS flaeche, bg.beschreibung AS bauweise_beschreibung, gf.beschreibung AS bezeichner ";
	$sql.="FROM aaa_ogr.ax_gebaeude g ";
	$sql.="JOIN aaa_ogr.ax_gebaeudefunktion gf ON gf.wert = g.gebaeudefunktion ";
	$sql.="LEFT JOIN aaa_ogr.ax_bauweise_gebaeude bg ON bg.wert = g.bauweise ";
    $sql.="WHERE g.endet IS NULL AND (g.hat = $1 OR $1 = ANY(g.zeigtauf))";
	$v = array($gmlid);
	$res = pg_prepare("", $sql);
	$res = pg_execute("", $v);
	if (!$res) {
		echo "<p class='err'>Fehler bei Gebaeude.</p>\n";
		if ($debug > 2) {echo "<p class='err'>SQL=<br>".$sql."<br>$1 = gml_id = '".$gmlid."'</p>";}
	}
	echo "\n<table class='geb'>";
	echo "\n<tr>"; // T-Header
		echo "\n\t<td class='head' title='Name des Gebäudes'>Name</td>";
		echo "\n\t<td class='head fla' title='Grundflächeanteil des Gebäudes auf dem Flurstück'>Grundfläche</td>";
		echo "\n\t<td class='head' title='Funktion des Gebäudes'>Funktion</td>";
		echo "\n\t<td class='head' title='Bauweise des Gebäudes'>Bauweise</td>";
		echo "\n\t<td class='head' title='Zustand des Gebäudes'>Zustand</td>";
		echo "\n\t<td class='head nwlink' title='Verknüpfungen zu den vollständigen Gebäudedaten'>Haus</td>";
	echo "\n</tr>";
	// T-Body
	$i=0;
	while($row = pg_fetch_array($res)) {
		$ggml=$row["gml_id"];
		$gfla=$row["flaeche"];
    $gflad=($gfla < 1 ? rtrim(number_format($gfla,2,",","."),"0") : number_format($gfla,0,",",".")); // Display-Format dazu
		echo "\n\t<tr>";

			echo "<td>";
				if ($idanzeige) {linkgml($gkz, $ggml, "Gebäude");}
				// +++ Hausnummer / Adresse ???
			echo $row["name"]."</td>";
			echo "<td class='fla'>".$gflad." m²</td>";
			echo "<td>";
			if ($showkey) {echo "<span class='key' title='Schlüssel der Funktion'>".$row["gebaeudefunktion"]."</span> ";}
			echo $row["bezeichner"]."</td>";
			echo "<td>";
				if ($showkey) {echo "<span class='key' title='Schlüssel der Bauweise'>".$row["bauweise"]."</span> ";}
			echo $row["bauweise_beschreibung"]."</td>";

			echo "<td>".$row["zustand"]."</td>"; // +++ Entschlüsseln

			echo "\n\t<td class='nwlink noprint'>";
				echo "<a title='Hausdaten' href='alkishaus.php?gkz=".$gkz."&amp;gmlid=".$ggml;
				if ($idanzeige) {echo "&amp;id=j";}
				echo "'><img src='ico/Haus.ico' width='16' height='16' alt=''></a>";
			echo "</td>";

		echo "</tr>";
	}
	echo "\n</table>";
}

?>

<form action=''>
	<div class='buttonbereich noprint'>
	<hr class='thick'>
		<a title="zurück zur vorherigen Ansicht" href='javascript:history.back()'><img src="ico/zurueck.ico" width="16" height="16" alt="zurück"></a>&nbsp;
		<a title="drucken" href='javascript:window.print()'><img src="ico/print.ico" width="16" height="16" alt="Drucken"></a>&nbsp;
	</div>
</form>

<?php footer($gmlid, $_SERVER['PHP_SELF']."?", "&amp;ltyp=".$ltyp); ?>

</body>
</html>
