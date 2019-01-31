<?php
//session_start();
$cntget = extract($_GET);
require_once("alkis_conf_location.php");
if ($auth == "mapbender") {require_once($mapbender);}
include("alkisfkt.php");
if ($id == "j") {$idanzeige=true;} else {$idanzeige=false;}
$keys = isset($_GET["showkey"]) ? $_GET["showkey"] : "n";
if ($keys == "j") {$showkey=true;} else {$showkey=false;}
if ($allfld == "j") {$allefelder=true;} else {$allefelder=false;}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
	<meta name="author" content="b600352" >
	<meta http-equiv="cache-control" content="no-cache">
	<meta http-equiv="pragma" content="no-cache">
	<meta http-equiv="expires" content="0">
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<title>Gebäudedaten</title>
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

// // G e b a e u d e
$sqlg ="SELECT g.gml_id, g.name[1] AS name, g.bauweise, g.gebaeudefunktion, g.anzahlderoberirdischengeschosse AS aog, g.anzahlderunterirdischengeschosse AS aug, ";
$sqlg.="g.lagezurerdoberflaeche, g.dachgeschossausbau, g.zustand, g.weiteregebaeudefunktion, g.dachform, g.hochhaus, g.objekthoehe, g.geschossflaeche, g.grundflaeche, g.umbauterraum, g.baujahr[1] AS baujahr, g.dachart, ";
$sqlg.="bg.beschreibung AS bauweise_beschreibung, gf.beschreibung AS bfunk, zg.beschreibung AS bzustand, ";
$sqlg.="df.beschreibung AS bdach, round(st_area(g.wkb_geometry)::numeric,2) AS gebflae ";
$sqlg.="FROM aaa_ogr.ax_gebaeude g ";
$sqlg.="JOIN aaa_ogr.ax_gebaeudefunktion gf ON gf.wert = g.gebaeudefunktion ";
$sqlg.="LEFT JOIN aaa_ogr.ax_bauweise_gebaeude bg ON bg.wert = g.bauweise ";
$sqlg.="LEFT JOIN aaa_ogr.ax_dachform df ON df.wert = g.dachform ";
$sqlg.="LEFT JOIN aaa_ogr.ax_zustand_gebaeude zg ON zg.wert = g.zustand ";
$sqlg.="WHERE g.endet IS NULL AND g.gml_id= $1 ";
$v = array($gmlid);
$resg = pg_prepare("", $sqlg);
$resg = pg_execute("", $v);
if (!$resg) {
	echo "\n<p class='err'>Fehler bei Gebäude.<br>".pg_last_error()."</p>\n";
	if ($debug > 2) {echo "<p class='dbg'>SQL=<br>".$sqlg."<br>$1 = gml_id = '".$gmlid."'</p>";}
}

// Balken
echo "<p class='geb'>Gebäude&nbsp;</p>\n"; // +++ Kennzeichen = ?

echo "\n<h2><img src='ico/Haus.ico' width='16' height='16' alt=''> Gebäude</h2>\n";

// Kennzeichen in Rahmen 
// - Welches Kennzeichen zum Haus ?
if ($idanzeige) {linkgml($gkz, $gmlid, "Haus"); }
// Umschalter: auch leere Felder ausgeben?
echo "<p class='nwlink noprint'>";
echo "<a class='nwlink' href='".$_SERVER['PHP_SELF']."?gkz=".$gkz."&amp;gmlid=".$gmlid;
	if ($showkey) {echo "&amp;showkey=j";} else {echo "&amp;showkey=n";}
	if ($idanzeige) {echo "&amp;id=j";} else {echo "&amp;id=n";}
	if ($allefelder) {echo "&amp;allfld=n'>nur Felder mit Inhalt anzeigen";} 
	else {echo "&amp;allfld=j'>auch leere Felder anzeigen";}
echo "</a></p>";

while($rowg = pg_fetch_array($resg)) { // Als Schleife, kann aber nur EIN Haus sein.
	$gebnr++;
	echo "\n<table class='geb'>";
	echo "\n<tr>\n";
		echo "\n\t<td class='head' title=''>Attribut</td>";
		echo "\n\t<td class='head' title=''>Wert</td>";
	echo "\n</tr>";

	$aog=$rowg["aog"];
	$aug=$rowg["aug"];
	$hoh=$rowg["hochhaus"];
	$nam=$rowg["name"]; // Gebaeude-Name
	$bfunk=$rowg["bfunk"];
	$baw=$rowg["bauweise"];
	$bbauw=$rowg["bauweise_beschreibung"];
	$ofl=$rowg["lagezurerdoberflaeche"];
	$dga=$rowg["dachgeschossausbau"];
	$zus=$rowg["zustand"];
	$zustand=$rowg["bzustand"];
	$wgf=$rowg["weiteregebaeudefunktion"];
	$daf=$rowg["dachform"];
	$dach=$rowg["bdach"];
	$hho=$rowg["objekthoehe"];
	$gfl=$rowg["geschossflaeche"];
  $gfld=($gfl < 1 ? rtrim(number_format($gfl,2,",","."),"0") : number_format($gfl,0,",",".")); // Display-Format dazu
	$grf=$rowg["grundflaeche"];
  $grfd=($grf < 1 ? rtrim(number_format($grf,2,",","."),"0") : number_format($grf,0,",",".")); // Display-Format dazu
	$ura=$rowg["umbauterraum"];
	$bja=$rowg["baujahr"];
	$daa=$rowg["dachart"];

	if (($nam != "") OR $allefelder) {
		echo "\n<tr>";
			echo "\n\t<td title='Name des Gebäudes'>Name</td>";
			echo "\n\t<td>";
			echo $nam."</td>";
		echo "\n</tr>";
	}

	// 0 bis N Lagebezeichnungen mit Haus- oder Pseudo-Nummer
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

	$v = array($gmlid);
	$resl = pg_prepare("", $sqll);
	$resl = pg_execute("", $v);
	if (!$resl) {
		echo "\n<p class='err'>Fehler bei Lage mit HsNr.</p>\n";
		if ($debug > 2) {echo "<p class='dbg'>SQL=<br>".$sqll."<br>$1 = gml_id = '".$gmlid."'</p>";}
	}
	$zhsnr=0;
	while($rowl = pg_fetch_array($resl)) { // LOOP: Lagezeilen
		$zhsnr++;
		$ltyp=$rowl["ltyp"]; // Lagezeilen-Typ
		$skey=$rowl["lage"]; // Str.-Schluessel
		$snam=htmlentities($rowl["bezeichnung"], ENT_QUOTES, "UTF-8"); // -Name
		$hsnr=$rowl["hausnummer"];
		$hlfd=$rowl["laufendenummer"];
		$gmllag=$rowl["gml_id"];

			if ($zhsnr == 1) {
				echo "\n<tr>\n\t<td title='Adresse des Gebäudes'>Adresse</td>";
				echo "\n\t<td>";
			}
			echo "\n\t\t<img src='ico/Lage_mit_Haus.ico' width='16' height='16' alt=''>&nbsp;";
			if ($showkey) {echo "<span class='key' title='Straßenschlüssel'>(".$skey.")</span>&nbsp;";}			
			echo "\n\t\t<a title='Lagebezeichnung mit Hausnummer' href='alkislage.php?gkz=".$gkz."&amp;gmlid=".$gmllag."&amp;ltyp=".$ltyp;
				if ($idanzeige) {echo "&amp;id=j";}
			echo "'>";
				echo $snam."&nbsp;".$hsnr;
				if ($ltyp == "p") { echo ", lfd.Nr ".$hlfd;}
			echo "</a>";
			if ($idanzeige) {linkgml($gkz, $gmllag, "Lage"); }
			echo "<br>";
	} // Ende Loop Lagezeilen m.H.

	if ($zhsnr > 0) {
		echo "\n\t</td>\n</tr>";
	}

		echo "\n<tr>";
			echo "\n\t<td title='Funktion des Gebäudes'>Funktion</td>";
			echo "\n\t<td>";
			if ($showkey) {echo "<span class='key' title='Schlüssel der Funktion'>".$rowg["gebaeudefunktion"]."</span>&nbsp;";}
			echo $bfunk."</td>";
		echo "\n</tr>";

	if ($baw != "" OR $allefelder) {
		echo "\n<tr>";
			echo "\n\t<td title='Bauweise des Gebäudes'>Bauweise</td>";
			echo "\n\t<td>";
			if ($showkey) {echo "<span class='key' title='Schlüssel der Bauweise'>".$baw."</span>&nbsp;";}
			echo $bbauw."</td>";
		echo "\n</tr>";
	}

	if ($aog != "" OR $allefelder) {
		echo "\n<tr>";
			echo "\n\t<td title='Anzahl der oberirdischen Geschosse'>Geschosse (oben)</td>";
			echo "\n\t<td>".$aog."</td>";
		echo "\n</tr>";
	}

	if ($aug != "" OR $allefelder) {
		echo "\n<tr>";
			echo "\n\t<td title='Anzahl der unterirdischen Geschosse'>Geschosse (unten)</td>";
			echo "\n\t<td>".$aug."</td>";
		echo "\n</tr>";
	}

	if ($hoh != "" OR $allefelder) {
		echo "\n<tr>";
			echo "\n\t<td title='Ein Hochhaus ist ein Gebäude, das nach Höhe und Ausprägung als Hochhaus zu bezeichnen ist. Für Gebäude im Geschossbau gilt dies in der Regel ab acht oberirdischen Geschossen, für andere Gebäude ab einer Gebäudehöhe von 22 Metern.'>Hochhaus</td>";
			echo "\n\t<td>".$hoh."</td>";
		echo "\n</tr>";
	}

	if ($ofl != "" OR $allefelder) {
		echo "\n<tr>";
			echo "\n\t<td title='Die Lage zur Erdoberfläche ist die Angabe der relativen Lage des Gebäudes zur Erdoberfläche. Diese Attributart wird nur bei nicht-ebenerdigen Gebäuden geführt.'>Lage zur Erdoberfläche</td>";
			echo "\n\t<td>";
			if ($showkey) {echo "<span class='key' title='Schlüssel der Lage zur Erdoberfläche'>".$ofl."</span>&nbsp;";}
			switch ($ofl) {
				case 1200: echo "unter der Erdoberfläche"; break;
				// "Unter der Erdoberfläche" bedeutet, dass sich das Gebäude unter der Erdoberfläche befindet
				case 1400: echo "aufgeständert"; break;
				// "Aufgeständert" bedeutet, dass ein Gebäude auf Stützen steht
				case "": echo "&nbsp;"; break;
				default: echo "** unbekannte Lage zur Erdoberfläche '".$ofl."' **"; break;
			}
			echo "&nbsp;</td>";
		echo "\n</tr>";
	}

	if ($dga != "" OR $allefelder) { // keine Schluesseltabelle in DB
		echo "\n<tr>";
			echo "\n\t<td title='Der Dachgeschossausbau ist ein Hinweis auf den Ausbau oder die Ausbaufähigkeit des Dachgeschosses.'>Dachgeschossausbau</td>";
			echo "\n\t<td>";
			if ($showkey) {echo "<span class='key' title='Schlüssel des Dachgeschossausbaus'>".$dga."</span>&nbsp;";}
			switch ($dga) {
				case 1000: echo "Nicht ausbaufähig"; break;
				case 2000: echo "Ausbaufähig"; break;
				case 3000: echo "Ausgebaut"; break;
				case 4000: echo "Ausbaufähigkeit unklar"; break;
				case "": echo "&nbsp;"; break;
				default: echo "** Unbekannter Wert Dachgeschossausbau '".$dga."' **"; break;
			}
			echo "</td>";
		echo "\n</tr>";
	}

	if ($zus != "" OR $allefelder) {
		echo "\n<tr>";
			echo "\n\t<td title='Der Zustand beschreibt die Beschaffenheit oder die Betriebsbereitschaft des Gebäudes. Diese Attributart wird nur dann geführt, wenn der Zustand des Gebäudes vom nutzungsfähigen Zustand abweicht.'>Zustand</td>";
			echo "\n\t<td>";
			if ($showkey) {echo "<span class='key' title='Schlüssel des Zustands'>".$zus."</span>&nbsp;";}
			echo $zustand."</td>";
		echo "\n</tr>";
	}

	if ($wgf != "" OR $allefelder) {
		echo "\n<tr>";
			echo "\n\t<td title='Weitere Gebäudefunktionen sind jene Funktionen, die ein Gebäude neben der dominierenden Gebäudefunktion hat.'>weitere Gebäudefunktionen</td>";
			echo "\n\t<td>";

			if ($wgf != "") {
				// weiteregebaeudefunktion ist jetzt ein Array
				$wgflist=trim($wgf, "{}"); // kommagetrennte(?) Liste der Schluesselwerte
				//$wgfarr=explode(",", $wgflist);
				//for each ...
				$sqlw="SELECT wert, beschreibung AS bezeichner FROM aaa_ogr.ax_weitere_gebaeudefunktion WHERE wert in ( $1 ) ORDER BY wert;";
				$v = array($wgflist);
				$resw = pg_prepare("", $sqlw);
				$resw = pg_execute("", $v);
				if (!$resw) {
					echo "\n<p class='err'>Fehler bei Gebäude - weitere Funktion.</p>\n";
					if ($debug > 2) {echo "<p class='dbg'>SQL=<br>".$sqlw."<br>$1 = Werteliste = '".$wgflist."'</p>";}
				}
				$zw=0;
				while($roww = pg_fetch_array($resw)) { // LOOP: w.Funktion
					$wwert=$roww["wert"];
					$wbez=$roww["bezeichner"];
					if ($zw > 0) {echo ", ";} // Liste oder Zeile? echo "<br>"; 
					if ($showkey) {echo "<span class='key' title='Schlüssel der Funktion'>".$wwert."</span>&nbsp;";}
					echo $wbez;
					$zw++;
			   }
			}
			echo "</td>";
		echo "\n</tr>";
	}

	if ($daf != "" OR $allefelder) {
		echo "\n<tr>";
			echo "\n\t<td title='Dachform des Gebäudes'>Dachform</td>";
			echo "\n\t<td>";
			if ($showkey) {echo "<span class='key' title='Schlüssel der Dachform'>".$daf."</span>&nbsp;";}
			echo $dach."</td>";
		echo "\n</tr>";
	}

	if ($hho != "" OR $allefelder) {
		echo "\n<tr>";
			echo "\n\t<td title='Die Objekthöhe gibt die Höhendifferenz in Metern zwischen dem höchsten Punkt der Dachkonstruktion und der festgelegten Geländeoberfläche des Gebäudes an.'>Objekthöhe</td>";
			echo "\n\t<td>";
			echo $hho."</td>";
		echo "\n</tr>";
	}

	if ($gfl != "" OR $allefelder) {
		echo "\n<tr>";
			echo "\n\t<td title='Gesamtgeschossfläche des Gebäudes'>Geschossfläche</td>";
			echo "\n\t<td>";
			if ($gfl != "") {
				echo $gfld." m²";
			}
			echo "</td>";
		echo "\n</tr>";
	}

	if ($grf != "" OR $allefelder) {
		echo "\n<tr>";
			echo "\n\t<td title='Grundfläche des Gebäudes'>Grundfläche</td>";
			echo "\n\t<td>";
			if ($grf != "") {
				echo $grfd." m²";
			}
		echo "\n</tr>";
	}

	if ($ura != "" OR $allefelder) {
		echo "\n<tr>";
			echo "\n\t<td title='umbauter Raum des Gebäudes'>umbauter Raum</td>";
			echo "\n\t<td>";
			echo $ura."</td>";
		echo "\n</tr>";
	}

	if ($bja != "" OR $allefelder) {
		echo "\n<tr>";
			echo "\n\t<td title='Das Baujahr ist das Jahr der Fertigstellung oder der baulichen Veränderung des Gebäudes.'>Baujahr</td>";
			echo "\n\t<td>";
			echo $bja."</td>";
		echo "\n</tr>";
	}

	if ($daa != "" OR $allefelder) {
		echo "\n<tr>";
			echo "\n\t<td title='Die Dachart gibt die Art der Dacheindeckung an.'>Dachart</td>";
			echo "\n\t<td>";
			echo $daa."</td>";
		echo "\n</tr>";
	}

	echo "\n</table>";
}
if ($gebnr == 0) {echo "<p class='err'><br>Kein Gebäude gefunden<br>&nbsp;</p>";}
// ++ ToDo: Verschnitt mit FS

?>

<form action=''>
	<div class='buttonbereich noprint'>
	<hr class='thick'>
		<a title="zurück zur vorherigen Ansicht" href='javascript:history.back()'><img src="ico/zurueck.ico" width="16" height="16" alt="zurück zur vorherigen Ansicht"></a>&nbsp;
		<a title="drucken" href='javascript:window.print()'><img src="ico/print.ico" width="16" height="16" alt="Drucken"></a>&nbsp;
	</div>
</form>

<?php footer($gmlid, $_SERVER['PHP_SELF']."?", ""); ?>

</body>
</html>
