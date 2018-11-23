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
        <title>Flurstücksnachweis</title>
        <link rel="stylesheet" type="text/css" href="alkisauszug.css">
        <link rel="shortcut icon" type="image/x-icon" href="ico/Flurstueck.ico">
        <script type="text/javascript">
            function ALKISexport() {
                window.open(<?php echo "'alkisexport.php?gkz=".$gkz."&tabtyp=flurstueck&gmlid=".$gmlid."'"; ?>);
            }
        </script>
        <style type='text/css' media='print'>
            .noprint {
                visibility: hidden;
            }
        </style>
    </head>
    <body>

<?php
$con = pg_connect("host=".$dbhost." port=".$dbport." dbname=".$dbname." user=".$dbuser." password=".$dbpass);#." sslmode=".$sslmode);
if (!$con) echo "<p class='err'>Fehler beim Verbinden der DB</p>\n";

// Flurstückskennzeichen wurde alternativ zur gml_id übermittelt
if ($gmlid == "" AND $fskennz != "") {
	// Übergabeformat "llgggg-fff-zzzzz/nnn.nn" oder "gggg-ff-zzz/nn"
	$arr=explode("-", $fskennz, 4);
	$zgemkg=trim($arr[0]);
	if (strlen($zgemkg) == 20 AND $arr[1] == "") { // Oh, ist wohl schon das Datenbank-Feldformat 
		$fskzdb=$zgemkg;
	} else { // Nö, ist wohl eher ALB-Format
		// Das Kennzeichen auseinander nehmen. 
		if (strlen($zgemkg) == 6) {
			$land=substr($zgemkg, 0, 2);
			$zgemkg=substr($zgemkg, 2, 4);
		} else { // kein schöner Land ..
			$land='05'; // NRW, ToDo: Default-Land aus config
		}
		$zflur=str_pad($arr[1], 3 , "0", STR_PAD_LEFT); // Flur-Nr
		$zfsnr=trim($arr[2]); // Flurstücke-Nr
		$zn=explode("/", $zfsnr, 2); // Bruch?
		$zzaehler=str_pad(trim($zn[0]), 5 , "0", STR_PAD_LEFT);	
		$znenner=trim($zn[1]);
		if (trim($znenner, " 0.") == "") { // kein Bruch oder nur Nullen
			$znenner="____"; // in DB-Spalte mit Tiefstrich aufgefüllt
		} else {
			$zn=explode(".", $znenner, 2); // .00 wegwerfen
			$znenner=str_pad($zn[0], 4 , "0", STR_PAD_LEFT);
		}
		// nun die Teile stellengerecht wieder zusammen setzen		
		$fskzdb=$land.$zgemkg.$zflur.$zzaehler.$znenner.'__'; // FS-Kennz. Format Datenbank
	}
	// Feld flurstueckskennzeichen ist in DB indiziert
	// Format z.B.'052647002001910013__' oder '05264700200012______'
	$sql ="SELECT gml_id FROM aaa_ogr.ax_flurstueck WHERE flurstueckskennzeichen= $1 AND endet IS NULL ;";

	$v = array($fskzdb);
	$res = pg_prepare("", $sql);
	$res = pg_execute("", $v);
	if ($row = pg_fetch_array($res)) {
		$gmlid=$row["gml_id"];
	} else {
		echo "<p class='err'>Fehler! Kein Treffer für Flurstückskennzeichen='".$fskennz."' (".$fskzdb.")</p>";
	}
	pg_free_result($res);
}

// F L U R S T U E C K
$sql ="SELECT array_remove(f.art, 'urn:adv:fachdatenverbindung:AA_Antrag') AS art, f.name, f.flurnummer, f.zaehler, f.nenner, f.flurstueckskennzeichen, f.gemarkung_land AS land, f.regierungsbezirk, f.kreis, f.gemeinde, f.amtlicheflaeche, f.realflaeche AS fsgeomflae, f.zeitpunktderentstehung, f.stelle, f.kennungschluessel, f.angabenzumabschnittflurstueck, f.flaechedesabschnitts, ";
$sql.="g.gemarkungsnummer, g.bezeichnung ";
$sql.="FROM aaa_ogr.ax_flurstueck f ";
$sql.="LEFT JOIN aaa_ogr.ax_gemarkung g ON f.gemarkungsnummer = g.gemarkungsnummer ";
$sql.="WHERE f.endet IS NULL AND g.endet IS NULL AND f.gml_id = $1;";

$v = array($gmlid); // mit gml_id suchen
$res = pg_prepare("", $sql);
$res = pg_execute("", $v);
if (!$res) {
	echo "\n<p class='err'>Fehler bei Flurstuecksdaten</p>\n";
	if ($debug > 2) {echo "<p class='dbg'>SQL=<br>".$sql."<br>$1 = gml_id = '".$gmlid."'</p>";}
}
if ($row = pg_fetch_array($res)) {
	$gemkname=htmlentities($row["bezeichnung"], ENT_QUOTES, "UTF-8");
	$flurstueckskennzeichen=$row["flurstueckskennzeichen"];
	$land=$row["land"];
	$gmkgnr=$row["gemarkungsnummer"];
	$bezirk=$row["regierungsbezirk"];
	$kreis=$row["kreis"];
	$gemeinde=$row["gemeinde"];
	$flurnummer=$row["flurnummer"];
	$flstnummer=$row["zaehler"];
	$nenner=$row["nenner"];
	if ($nenner > 0) {$flstnummer.="/".$nenner;} // BruchNr
	$fsbuchflae=$row["amtlicheflaeche"]; // amtliche Fl. aus DB-Feld
	$fsgeomflae=$row["fsgeomflae"]; // aus Geometrie ermittelte Fläche
	$fsbuchflaed=number_format($fsbuchflae,0,",",".") . " m&#178;"; // Display-Format dazu
	$fsgeomflaed=number_format($fsgeomflae,0,",",".") . " m&#178;";
	if (!empty($row["zeitpunktderentstehung"]))
        $entstehung_datum = strftime('%d.%m.%Y', strtotime($row["zeitpunktderentstehung"]));
    else
        $entstehung_datum = "unbekannt";
	$alb_datenarten = $row["art"]; // ALB-Datenarten
	$alb_datenarten = explode(",", trim(str_replace('"', '', $alb_datenarten), "{}")); // PHP-Array mit ALB-Datenarten
	$alb_daten = $row["name"]; // ALB-Daten
	$alb_daten = explode(",", trim(str_replace('"', '', $alb_daten), "{}")); // PHP-Array mit ALB-Daten
    $zusatzangaben = $row["angabenzumabschnittflurstueck"]; // Zusatzangaben
    $zusatzangaben = explode(",", trim($zusatzangaben, "{}")); // PHP-Array mit Zusatzangaben
    $dienststelle=$row["stelle"]; // Dienststelle(n)
    $dienststelle_array=explode(",", trim($dienststelle, "{}") ); // PHP-Array mit Dienststelle(n)
    $kennungsschluessel=$row["kennungschluessel"]; // Kennungsschlüssel
    $kennungsschluessel_array=explode(",", trim($kennungsschluessel, "{}") ); // PHP-Array mit Kennungsschlüssel(n)
    $flaechedesabschnitts=$row["flaechedesabschnitts"]; // Fläche(n) des Abschnitts
    $flaechedesabschnitts_array=explode(",", trim($flaechedesabschnitts, "{}") ); // PHP-Array mit Fläche(n) des Abschnitts
} else {
	echo "<p class='err'>Fehler! Kein Treffer für gml_id=".$gmlid."</p>";
	if ($debug > 2) {echo "<p class='dbg'>SQL=<br>".$sql."<br>$1 = gml_id = '".$gmlid."'</p>";}
}
pg_free_result($res);

// Balken
echo "<p class='fsei'>Flurstück <span title='Flurstückskennzeichen in der offiziellen ALKIS-Notation'>".$flurstueckskennzeichen."</span>&nbsp;</p>\n";
echo "\n<h2><img src='ico/Flurstueck.ico' width='16' height='16' alt=''> Flurstücksnachweis</h2>\n";
echo "\n<table class='outer'>\n<tr>\n\t<td>"; // linke Seite
	// darin Tabelle Kennzeichen
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
    echo "\n\t</td>";
	if ($idanzeige) {linkgml($gkz, $gmlid, "Flurstück"); }
echo "\n</tr>\n</table>";
// Ende Seitenkopf


echo "\n<hr class='thin'>";
echo "\n<p class='nwlink noprint'>weitere Auskunft:</p>"; // oben rechts von der Tabelle
echo "\n<table class='fs'>";

// ** Gebietszugehörigkeit **
// eine Tabellenzeile mit der Gebietszugehoerigkeit eines Flurstuecks wird ausgegeben
// Schluessel "land" wird nicht verwendet, gibt es Bestaende wo das nicht einheitlich ist?
echo "\n<tr>\n\t<td class='ll' title='Gebietszugehörigkeiten des Flurstücks'><img src='ico/Gemeinde.ico' width='16' height='16' alt=''> <b>Gebiet</b></td>";

// Land
$sql="SELECT bezeichnung FROM aaa_ogr.ax_bundesland WHERE schluessel_land = $1 AND endet IS NULL"; 
$v = array($land);
$res = pg_prepare("", $sql);
$res = pg_execute("", $v);
if (!$res) {
	echo "\n<p class='err'>Fehler bei Land</p>\n";
	if ($debug > 2) {echo "<p class='dbg'>SQL=<br>".$sql."</p>";}
}
$row = pg_fetch_array($res);
$bnam = htmlentities($row["bezeichnung"], ENT_QUOTES, "UTF-8");
echo "\n\t<td class='lr'><i>Land</i></td><td class='lr'>";
if ($showkey) {
	echo "<span class='key' title='Landesschlüssel (= erste 2 Stellen des Regionalschlüssels)'>(".$land.")</span> ";
}
echo $bnam."</td><td width='80'>";  // Mindest-Breite der Spalte fuer die Links 
	// Link zur Flurstückshistorie (passt nicht ganz in die Zeile "Gemeinde", aber gut unter "weitere Auskunft")
	echo "\n<p class='nwlink noprint'>";
		echo "\n\t<a href='alkisfshist.php?gkz=".$gkz."&amp;gmlid=".$gmlid;
				if ($idanzeige) {echo "&amp;id=j";}
				if ($showkey)   {echo "&amp;showkey=j";}
			echo "' title='Flurstückshistorie'>Historie ";
			echo "<img src='ico/Flurstueck_Historisch.ico' width='16' height='16' alt=''>";
		echo "</a>";
	echo "\n</p>";
echo "</td></tr>";

// Kreis
$sql="SELECT bezeichnung FROM aaa_ogr.ax_kreisregion WHERE regierungsbezirk= $1 AND kreis= $2 AND endet IS NULL"; 
$v = array($bezirk,$kreis);
$res = pg_prepare("", $sql);
$res = pg_execute("", $v);
if (!$res) {
	echo "\n<p class='err'>Fehler bei Kreis</p>\n";
	if ($debug > 2) {echo "<p class='dbg'>SQL=<br>".$sql."</p>";}
}
$row = pg_fetch_array($res);
$knam = htmlentities($row["bezeichnung"], ENT_QUOTES, "UTF-8");
echo "<tr><td>&nbsp;</td><td><i>Kreis</i></td><td>";
if ($showkey) {
	echo "<span class='key' title='Kreisschlüssel (= erste 5 Stellen des Regionalschlüssels)'>(".$land.str_pad($kreis, 3, "0", STR_PAD_LEFT).")</span> ";
}
echo $knam."</td><td>&nbsp;</td></tr>";
pg_free_result($res);

// Gemeinde
$sql="SELECT bezeichnung FROM aaa_ogr.ax_gemeinde WHERE regierungsbezirk= $1 AND kreis= $2 AND gemeinde= $3 AND endet IS NULL";
$v = array($bezirk,$kreis,$gemeinde);
$res = pg_prepare("", $sql);
$res = pg_execute("", $v);
if (!$res) {
	echo "\n<p class='err'>Fehler bei Gemeinde</p>\n";
	if ($debug > 2) {echo "<p class='dbg'>SQL=<br>".$sql."</p>";}
}
$row = pg_fetch_array($res);
$gnam = htmlentities($row["bezeichnung"], ENT_QUOTES, "UTF-8");
echo "<tr><td>&nbsp;</td><td><i>Gemeinde</i></td><td>";
if ($showkey) {
	echo "<span class='key' title='Gemeindeschlüssel (= Regionalschlüssel)'>(".$land.str_pad($kreis, 3, "0", STR_PAD_LEFT).str_pad($gemeinde, 7, "0", STR_PAD_LEFT).")</span> ";
}
echo $gnam."</td><td>&nbsp;</td></tr>";
pg_free_result($res);

// Finanzamt
if (max($dienststelle_array) >= 4000 AND max($dienststelle_array) < 5000) {
    $sql_finanzamt ="SELECT stelle AS schluessel, bezeichnung FROM aaa_ogr.ax_dienststelle WHERE stelle::int = " . max($dienststelle_array) . " AND endet IS NULL LIMIT 1";
    $res_finanzamt = pg_query($con, $sql_finanzamt);
    $row = pg_fetch_array($res_finanzamt);
    echo "<tr><td>&nbsp;</td><td><i>Finanzamt</i></td><td>";
    if ($showkey) {
        echo "<span class='key' title='Dienststellenschlüssel'>(" . $row["schluessel"] . ")</span> ";
    }
    echo $row["bezeichnung"] . "</td><td>&nbsp;</td></tr>";
    pg_free_result($res_finanzamt);
}

// Forstamt
if (min($dienststelle_array) >= 2000 AND min($dienststelle_array) < 3000) {
    $sql_forstamt ="SELECT stelle AS schluessel, bezeichnung FROM aaa_ogr.ax_dienststelle WHERE stelle::int = " . min($dienststelle_array) . " AND endet IS NULL LIMIT 1";
    $res_forstamt = pg_query($con, $sql_forstamt);
    $row = pg_fetch_array($res_forstamt);
    echo "<tr><td>&nbsp;</td><td><i>Forstamt</i></td><td>";
    if ($showkey) {
        echo "<span class='key' title='Dienststellenschlüssel'>(" . $row["schluessel"] . ")</span> ";
    }
    echo $row["bezeichnung"] . "</td><td>&nbsp;</td></tr>";
    pg_free_result($res_forstamt);
}
// ENDE Gebietszugehörigkeit

// ** L a g e b e z e i c h n u n g **

// Lagebezeichnung Mit Hausnummer
// ax_flurstueck  >weistAuf>  AX_LagebezeichnungMitHausnummer
$sql ="SELECT DISTINCT l.gml_id, l.gemeinde, l.lage, l.hausnummer, k.bezeichnung ";
$sql.="FROM aaa_ogr.ax_flurstueck f ";
$sql.="JOIN aaa_ogr.ax_lagebezeichnungmithausnummer l ON l.gml_id = ANY(f.weistauf) ";
$sql.="LEFT JOIN aaa_ogr.ax_lagebezeichnungkatalogeintrag k ON l.lage = k.lage ";
$sql.="WHERE f.gml_id = $1 ";
$sql.="AND f.endet IS NULL AND l.endet IS NULL AND k.endet IS NULL ";
$sql.="ORDER BY l.gemeinde, l.lage, l.hausnummer;";
// Theoretisch JOIN notwendig über den kompletten Schlüssel bestehend aus land+regierungsbezirk+kreis+gemeinde+lage
// bei einem Sekundärbestand für eine Gemeinde oder einen Kreis reicht dies hier:

$v = array($gmlid);
$res = pg_prepare("", $sql);
$res = pg_execute("", $v);
if (!$res) {
	echo "<p class='err'>Fehler bei Lagebezeichnung mit Hausnummer</p>";
	if ($debug > 2) {echo "<p class='dbg'>SQL=<br>".$sql."</p>";}
}
$j=0;
while($row = pg_fetch_array($res)) {
	$sname = htmlentities($row["bezeichnung"], ENT_QUOTES, "UTF-8"); // Str.-Name
	echo "\n<tr>\n\t";
		if ($j == 0) {
			echo "<td class='ll' title='Adresse des Flurstücks'><img src='ico/Lage_mit_Haus.ico' width='16' height='16' alt=''> <b>Adresse</b></td>";
		} else {
			echo "<td>&nbsp;</td>";
		}
		echo "\n\t<td>&nbsp;</td>";
		echo "\n\t<td class='lr'>";
		if ($showkey) {
			echo "<span title='Straßenschlüssel' class='key'>(".$row["lage"].")</span>&nbsp;";
		}
		echo $sname."&nbsp;".$row["hausnummer"];
		if ($idanzeige) {linkgml($gkz, $row["gml_id"], "Lagebezeichnung mit Hausnummer");}
		echo "</td>";
		echo "\n\t<td>\n\t\t<p class='nwlink noprint'>";
			echo "\n\t\t\t<a title='Lagebezeichnung mit Hausnummer' href='alkislage.php?gkz=".$gkz."&amp;ltyp=m&amp;gmlid=".$row["gml_id"];
			if ($showkey) {echo "&amp;showkey=j";}
			echo "'>Lage ";
			echo "<img src='ico/Lage_mit_Haus.ico' width='16' height='16' alt=''></a>";
		echo "\n\t\t</p>\n\t</td>";
	echo "\n</tr>";
	$j++;
}
pg_free_result($res);
// Verbesserung: mehrere HsNr zur gleichen Straße als Liste?

// L a g e b e z e i c h n u n g   o h n e   H a u s n u m m e r  (Gewanne oder nur Strasse)
// ax_flurstueck  >zeigtAuf>  AX_LagebezeichnungOhneHausnummer
$sql ="SELECT l.gml_id, l.unverschluesselt, l.gemeinde, l.lage, k.bezeichnung ";
$sql.="FROM aaa_ogr.ax_flurstueck f ";
$sql.="JOIN aaa_ogr.ax_lagebezeichnungohnehausnummer l ON l.gml_id = ANY(f.zeigtauf) ";
$sql.="LEFT JOIN aaa_ogr.ax_lagebezeichnungkatalogeintrag k ON l.lage = k.lage ";
$sql.="WHERE f.gml_id = $1 ";
$sql.="AND f.endet IS NULL AND l.endet IS NULL AND k.endet IS NULL ";
$v = array($gmlid);
$res = pg_prepare("", $sql);
$res = pg_execute("", $v);
if (!$res) {
	echo "<p class='err'>Fehler bei Lagebezeichnung</p>";
	if ($debug > 2) {echo "<p class='dbg'>SQL=<br>".$sql."</p>";}
}
$j=0;
// Es wird auch eine Zeile ausgegeben, wenn kein Eintrag gefunden!
while($row = pg_fetch_array($res)) {
	$gewann = htmlentities($row["unverschluesselt"], ENT_QUOTES, "UTF-8");
	$skey=$row["lage"]; // Strassenschl.
	$lgml=$row["gml_id"]; // key der Lage
	if (!$gewann == "") {
		echo "\n<tr>";
			echo "\n\t<td class='ll' title='Gewann des Flurstücks'><img src='ico/Lage_Gewanne.ico' width='16' height='16' alt=''> <b>Gewann</b></td>";
			echo "\n\t<td></td>";
			echo "\n\t<td class='lr'>".$gewann."</td>";
			echo "\n\t<td>\n\t\t<p class='nwlink noprint'>";
				echo "\n\t\t\t<a title='Lagebezeichnung Gewann' href='alkislage.php?gkz=".$gkz."&amp;ltyp=o&amp;gmlid=".$lgml;
				if ($showkey) {echo "&amp;showkey=j";}				
				echo "'>\n\t\t\tLage <img src='ico/Lage_Gewanne.ico' width='16' height='16' alt=''></a>";
			echo "\n\t\t</p>\n\t</td>";
		echo "\n</tr>";
	}
	// Gleicher DB-Eintrag in zwei HTML-Zeilen, besser nur ein Link
	if ($skey > 0) {
		echo "\n<tr>";
			echo "\n\t<td class='ll' title='Straße des Flurstücks'><img src='ico/Lage_an_Strasse.ico' width='16' height='16' alt=''> <b>Straße</b></td>";
			echo "\n\t<td></td>";
			echo "\n\t<td class='lr'>";
			if ($showkey) {
				echo "<span title='Straßenschlüssel' class='key'>(".$skey.")</span>&nbsp;";
			}
			echo $row["bezeichnung"];
			if ($idanzeige) {linkgml($gkz, $lgml, "Lagebezeichnung ohne Hausnummer");}
			echo "</td>";
			echo "\n\t<td>\n\t\t<p class='nwlink noprint'>";
				echo "\n\t\t\t<a title='Lagebezeichnung Straße' href='alkislage.php?gkz=".$gkz."&amp;ltyp=o&amp;gmlid=".$lgml;
				if ($showkey) {echo "&amp;showkey=j";}				
				echo "'>\n\t\t\tLage <img src='ico/Lage_an_Strasse.ico' width='16' height='16' alt=''>\n\t\t\t</a>";
			echo "\n\t\t</p>\n\t</td>";
		echo "\n</tr>";
	}
	$j++;
}
pg_free_result($res);
// ENDE  L a g e b e z e i c h n u n g

// ** N U T Z U N G ** Gemeinsame Fläche von NUA und FS
// Tabellenzeilen (3 Spalten) mit tats. Nutzung zu einem FS ausgeben
$sql ="SELECT n.id, n.klasse, nk.mv, nk.nutzungsartenbereich, nk.nutzungsart, nk.wirtschaftsart, ";
$sql.="ST_Area(ST_Intersection(n.wkb_geometry, f.wkb_geometry)) AS schnittflaeche ";
$sql.="FROM aaa_ogr.ax_flurstueck f, prozessiert.nutzung n ";
$sql.="JOIN prozessiert.nutzungsarten na ON na.id = n.id ";
$sql.="LEFT JOIN prozessiert.nutzungsartenkatalog nk ON na.quelltabelle = nk.quelltabelle ";
$sql.="WHERE f.endet IS NULL AND f.gml_id= $1 AND n.wkb_geometry && f.wkb_geometry AND ST_Intersects(n.wkb_geometry, f.wkb_geometry) = TRUE ";
$sql.="AND ST_Area(ST_Intersection(n.wkb_geometry, f.wkb_geometry)) > 0.05 ";
$sql.="AND COALESCE(n.agt, 0) = COALESCE(nk.agt, 0) AND COALESCE(n.art, 0) = COALESCE(nk.art, 0) ";
$sql.="AND COALESCE(n.beb, 0) = COALESCE(nk.beb, 0) AND COALESCE(n.bkt, 0) = COALESCE(nk.bkt, 0) ";
$sql.="AND COALESCE(n.fgt, 0) = COALESCE(nk.fgt, 0) AND COALESCE(n.fkt, 0) = COALESCE(nk.fkt, 0) ";
$sql.="AND COALESCE(n.hyd, 0) = COALESCE(nk.hyd, 0) AND COALESCE(n.lgt, 0) = COALESCE(nk.lgt, 0) ";
$sql.="AND COALESCE(n.ofm, 0) = COALESCE(nk.ofm, 0) AND COALESCE(n.veg, 0) = COALESCE(nk.veg, 0) ";
$sql.="AND COALESCE(n.zus, 0) = COALESCE(nk.zus, 0) ";
$sql.="ORDER BY schnittflaeche DESC;";

$v = array($gmlid);
$res = pg_prepare("", $sql);
$res = pg_execute("", $v);
if (!$res) {
	echo "<p class='err'>Fehler bei Suche tats. Nutzung</p>\n";
	if ($debug > 2) {echo "<p class='dbg'>SQL=<br>".$sql."<br>$1 = gml_id = '".$gmlid."'</p>";}
}
$the_Xfactor=$fsbuchflae / $fsgeomflae; // geom. ermittelte Fläche auf amtl. Buchfläche angleichen
$j=0;
while($row = pg_fetch_array($res)) {
	$nutz_id=$row["nutz_id"];
	$class=$row["klasse"];
	$mv=$row["mv"];
	$nutzungsartenbereich=$row["nutzungsartenbereich"];
	$nutzungsart=$row["nutzungsart"];
	$wirtschaftsart=$row["wirtschaftsart"];
	$schnittflaeche=$row["schnittflaeche"];

	echo "\n<tr>\n\t";
		if ($j == 0) {
			echo "<td class='ll' title='tatsächliche Nutzung des Flurstücks'><img src='ico/Abschnitt.ico' width='16' height='16' alt=''> <b>Nutzung</b></td>";
		} else {
			echo "<td>&nbsp;</td>";
		}
		$absflaebuch = $schnittflaeche * $the_Xfactor; // angleichen geometrisch an amtliche Fläche
		$schnittflaeche = number_format($schnittflaeche,1,",",".") . " m&#178;"; // geometrisch
		$absflaebuch = number_format($absflaebuch,0,",",".") . " m&#178;"; // Abschnitt an Buchfläche angeglichen
		echo "\n\t<td class='fla'>".$absflaebuch."</td>";

		echo "\n\t<td class='lr'>";
            if ($showkey) {
                echo "<span class='key' title='Schlüssel der Nutzungsart'>(".$mv.")</span> ";
            }
            echo $nutzungsartenbereich . " – " . $nutzungsart . " (Wirtschaftsart: " . $wirtschaftsart . ")";
        echo "</td>";
        
		echo "\n\t<td>";
			switch ($nutzungsartenbereich) {
				case "Siedlung":   $ico = "Abschnitt.ico"; break;
				case "Verkehr":    $ico = "Strassen_Klassifikation.ico"; break;
				case "Vegetation": $ico = "Wald.ico"; break;
				case "Gewässer":   $ico = "Wasser.ico"; break;
				default:           $ico = "Abschnitt.ico"; break;
			}
			// Icon ist auch im Druck sichtbar, class='noprint' ?		
			echo "<p class='nwlink'><img title='".$nutzungsartenbereich."' src='ico/".$ico."' width='16' height='16' alt='NUA'></p>";
		echo "</td>";
	echo "\n</tr>";
	$j++;
}
pg_free_result($res);
// ENDE  N U T Z U N G

echo "\n<tr>"; // Summenzeile
	echo "\n\t<td class='ll' title='amtliche Fläche (Buchfläche) des Flurstücks'><b>Fläche</b></td>";
	echo "\n\t<td class='fla sum'>";
	echo "<span title='geometrisch berechnet: ".$fsgeomflaed."' class='flae'>".$fsbuchflaed."</span></td>";

	// Flaeche und Link auf Gebäude-Auswertung
	echo "\n\t<td>&nbsp;</td>\n\t<td>";
		echo "\n\t\t<p class='nwlink noprint'>"; // Gebaeude-Verschneidung
			echo "\n\t\t\t<a href='alkisgebaeudenw.php?gkz=".$gkz."&amp;gmlid=".$gmlid;
			if ($idanzeige) {echo "&amp;id=j";}
			if ($showkey) {echo "&amp;showkey=j";}
			echo "' title='Gebäudenachweis'>Gebäude <img src='ico/Haus.ico' width='16' height='16' alt=''></a>";
		echo "\n\t\t</p>";
	echo "\n\t</td>";
echo "\n</tr>";

// gesetzliche Klassifizierung
if ($flaechedesabschnitts_array[0] != '') {
    echo "\n<tr>\n\t";
    echo "<td class='ll' title='gesetzliche Klassifizierung'><b>gesetzl. Klass.</b></td>";
    echo "\n\t<td class='fla'>";
    foreach($flaechedesabschnitts_array AS $val) { // Zeile f. jedes Element des Array
        echo number_format(trim($val, '"'),0,",",".")." m&#178;"."<br>";
    }
    echo "</td>";
    echo "\n\t<td class='lr'>";
    foreach($kennungsschluessel_array AS $val) { // Zeile f. jedes Element des Array
        if ((substr($val, 0, 1) != 'L') AND (substr($val, 0, 1) != '9'))
            echo trim($val, '"')."<br>";
    }
    echo "</td>";
    echo "\n</tr>";
}


// BEGINN Hinweise zur gesetzlichen Klassifizierung des Flurstücks
// Bau-, Raum- oder Bodenordnungsrecht
$sql_boden ="SELECT a.wert, a.beschreibung AS art_verf, b.gml_id AS verf_gml, b.bezeichnung AS verf_bez, b.name AS verf_name, d.bezeichnung AS stelle_bez, d.stelle AS stelle_key ";
$sql_boden.="FROM aaa_ogr.ax_bauraumoderbodenordnungsrecht b JOIN aaa_ogr.ax_artderfestlegung_bauraumoderbodenordnungsrecht a ON a.wert = b.artderfestlegung ";
$sql_boden.="LEFT JOIN aaa_ogr.ax_dienststelle d ON b.stelle = d.stelle ";
$sql_boden.="WHERE b.endet IS NULL AND d.endet IS NULL AND ST_Within((SELECT wkb_geometry FROM aaa_ogr.ax_flurstueck WHERE gml_id = $1 AND endet IS NULL), b.wkb_geometry)";
pg_prepare($con, "bodenrecht_con", $sql_boden);
$res_bodenrecht = pg_execute($con, "bodenrecht_con", array($gmlid));

// Denkmalschutzrecht
$sql_denkmal ="SELECT a.wert, a.beschreibung FROM aaa_ogr.ax_denkmalschutzrecht b, aaa_ogr.ax_artderfestlegung_denkmalschutzrecht a, aaa_ogr.ax_flurstueck f WHERE f.endet IS NULL AND b.endet IS NULL AND f.gml_id = $1 AND a.wert = b.artderfestlegung AND ((ST_Area(ST_Intersection(f.wkb_geometry, b.wkb_geometry))::numeric / ST_area(f.wkb_geometry)::numeric) * 100::numeric)> 90";
pg_prepare($con, "denkmalrecht_con", $sql_denkmal);
$res_denkmalrecht = pg_execute($con, "denkmalrecht_con", array($gmlid));

// Forstrecht
$sql_forst ="SELECT a.wert, a.beschreibung FROM aaa_ogr.ax_forstrecht b, aaa_ogr.ax_artderfestlegung_forstrecht a, aaa_ogr.ax_flurstueck f WHERE f.endet IS NULL AND b.endet IS NULL AND f.gml_id = $1 AND a.wert = b.artderfestlegung AND ((ST_Area(ST_Intersection(f.wkb_geometry, b.wkb_geometry))::numeric / ST_area(f.wkb_geometry)::numeric) * 100::numeric)> 90";
pg_prepare($con, "forstrecht_con", $sql_forst);
$res_forstrecht = pg_execute($con, "forstrecht_con", array($gmlid));

// Natur-, Umwelt- oder Bodenschutzrecht
$sql_natur ="SELECT a.wert, a.beschreibung FROM aaa_ogr.ax_naturumweltoderbodenschutzrecht b, aaa_ogr.ax_artderfestlegung_naturumweltoderbodenschutzrecht a, aaa_ogr.ax_flurstueck f WHERE f.endet IS NULL AND b.endet IS NULL AND f.gml_id = $1 AND a.wert = b.artderfestlegung AND ((ST_Area(ST_Intersection(f.wkb_geometry, b.wkb_geometry))::numeric / ST_area(f.wkb_geometry)::numeric) * 100::numeric)> 90";
pg_prepare($con, "naturrecht_con", $sql_natur);
$res_naturrecht = pg_execute($con, "naturrecht_con", array($gmlid));

// Straßenrecht
$sql_strasse ="SELECT a.wert, a.beschreibung FROM aaa_ogr.ax_klassifizierungnachstrassenrecht b, aaa_ogr.ax_artderfestlegung_klassifizierungnachstrassenrecht a, aaa_ogr.ax_flurstueck f WHERE f.endet IS NULL AND b.endet IS NULL AND f.gml_id = $1 AND a.wert = b.artderfestlegung AND f.wkb_geometry && b.wkb_geometry AND ST_Intersects(f.wkb_geometry, b.wkb_geometry)";
pg_prepare($con, "strassenrecht_con", $sql_strasse);
$res_strassenrecht = pg_execute($con, "strassenrecht_con", array($gmlid));

// Wasserrecht
$sql_wasser ="SELECT a.wert, a.beschreibung FROM aaa_ogr.ax_klassifizierungnachwasserrecht b, aaa_ogr.ax_artderfestlegung_klassifizierungnachwasserrecht a, aaa_ogr.ax_flurstueck f WHERE f.endet IS NULL AND b.endet IS NULL AND f.gml_id = $1 AND a.wert = b.artderfestlegung AND ((ST_Area(ST_Intersection(f.wkb_geometry, b.wkb_geometry))::numeric / ST_area(f.wkb_geometry)::numeric) * 100::numeric)> 90 UNION SELECT a.wert, a.beschreibung FROM aaa_ogr.ax_anderefestlegungnachwasserrecht b, aaa_ogr.ax_artderfestlegung_anderefestlegungnachwasserrecht a, aaa_ogr.ax_flurstueck f WHERE f.endet IS NULL AND b.endet IS NULL AND f.gml_id = $1 AND a.wert = b.artderfestlegung AND ((ST_Area(ST_Intersection(f.wkb_geometry, b.wkb_geometry))::numeric / ST_area(f.wkb_geometry)::numeric) * 100::numeric)> 90";
pg_prepare($con, "wasserrecht_con", $sql_wasser);
$res_wasserrecht = pg_execute($con, "wasserrecht_con", array($gmlid));

// sonstiges Recht
$sql_sonstiges ="SELECT a.wert, a.beschreibung FROM aaa_ogr.ax_sonstigesrecht b, aaa_ogr.ax_artderfestlegung_sonstigesrecht a, aaa_ogr.ax_flurstueck f WHERE f.endet IS NULL AND b.endet IS NULL AND f.gml_id = $1 AND a.wert = b.artderfestlegung AND ((ST_Area(ST_Intersection(f.wkb_geometry, b.wkb_geometry))::numeric / ST_area(f.wkb_geometry)::numeric) * 100::numeric)> 90";
pg_prepare($con, "sonstigesrecht_con", $sql_sonstiges);
$res_sonstigesrecht = pg_execute($con, "sonstigesrecht_con", array($gmlid));

// Überschrift
if (pg_num_rows($res_bodenrecht) > 0 OR pg_num_rows($res_denkmalrecht) > 0 OR pg_num_rows($res_forstrecht) > 0 OR pg_num_rows($res_naturrecht) > 0 OR pg_num_rows($res_strassenrecht) > 0 OR pg_num_rows($res_wasserrecht) > 0 OR pg_num_rows($res_sonstigesrecht) > 0) {
    echo "\n<tr>";
    echo "\n\t<td colspan=4 title='Hinweise zur gesetzlichen Klassifizierung des Flurstücks'><h6><img src='ico/Hinweis.ico' width='16' height='16' alt=''> ";
    echo "gesetzliche Klassifizierung</td></h6>";
    echo "\n</tr>";
}

// Bau-, Raum- oder Bodenordnungsrecht
if (pg_num_rows($res_bodenrecht) > 0) {
    while ($row = pg_fetch_array($res_bodenrecht)) { // 3 Zeilen je Verfahren

        // Zeile 1 - kommt immer, darum hier den Link
        echo "\n<tr>";
            echo "\n\t<td title='Bau-, Raum- oder Bodenordnungsrecht'>Bodenrecht:</td>";
            echo "\n\t<td>Festlegung</td>"; // "Art der Festlegung" zu lang
            echo "\n\t<td>";
                if ($showkey) {echo "<span class='key' title='Schlüssel der Festlegung'>(".$row['wert'].")</span> ";}
                echo $row['art_verf'];
            echo "</td>";
            echo "\n\t<td>";
            // LINK:
            echo "\n\t\t<p class='nwlink noprint'>";
                echo "\n\t\t\t<a href='alkisbaurecht.php?gkz=".$gkz."&amp;gmlid=".$row['verf_gml'];
                if ($idanzeige) {echo "&amp;id=j";}
                if ($showkey) {echo "&amp;showkey=j";}
                echo "' title='Bau-, Raum- oder Bodenordnungsrecht'>Recht <img src='ico/Gericht.ico' width='16' height='16' alt=''></a>";
            echo "\n\t\t</p>";			
            echo "</td>";
        echo "\n</tr>";

        // Zeile 2
        $dstell=$row['stelle_key']; // LEFT JOIN
        if ($dstell != "") { // Kann auch leer sein
            echo "\n<tr>";
                echo "\n\t<td>&nbsp;</td>";
                echo "\n\t<td>Dienststelle</td>";
                echo "\n\t<td>";
                    if ($showkey) {echo "<span title='Dienststellenschlüssel' class='key'>(".$dstell.")</span> ";}
                    echo $row['stelle_bez'];
                echo "</td>";
                echo "\n\t<td>&nbsp;</td>";
            echo "\n</tr>";
        }

        // Zeile 3
        $vbez=$row['verf_bez']; // ist nicht immer gefüllt
        $vnam=$row['verf_name']; // noch seltener
        if ($vbez != "") {
            echo "\n<tr>";
                echo "\n\t<td>&nbsp;</td>\n\t<td>Verfahren</td>";
                echo "\n\t<td>";
                    if ($vnam == "") {
                        echo $vbez; // nur die Nummer
                    } else {	// Name oder beides
                        if ($showkey) {echo "<span class='key' title='Schlüssel des Verfahrens'>(".$vbez.")</span> ";}
                        echo $vnam;
                    }
                echo "</td>";
                echo "\n\t<td>&nbsp;</td>";
            echo "\n</tr>";
        }
    }
}

// Denkmalschutzrecht
if (pg_num_rows($res_denkmalrecht) > 0) {
    while ($row = pg_fetch_array($res_denkmalrecht)) {
        echo "\n<tr>\n\t";
        echo "<td class='ll' title='Denkmalschutzrecht'>Denkmalrecht:</td>";
        echo "\n\t<td>Festlegung</td>";
        echo "\n\t<td class='lr'>";
        if ($showkey) {echo "<span class='key' title='Schlüssel der Festlegung'>(".$row['wert'].")</span> ";}
        echo $row['beschreibung'];
        echo "</td>";
        echo "\n</tr>";
    }
}
pg_free_result($res_denkmalrecht);

// Forstrecht
if (pg_num_rows($res_forstrecht) > 0) {
    while ($row = pg_fetch_array($res_forstrecht)) {
        echo "\n<tr>\n\t";
        echo "<td class='ll' title='Forstrecht'>Forstrecht:</td>";
        echo "\n\t<td>Festlegung</td>";
        echo "\n\t<td class='lr'>";
        if ($showkey) {echo "<span class='key' title='Schlüssel der Festlegung'>(".$row['wert'].")</span> ";}
        echo $row['beschreibung'];
        echo "</td>";
        echo "\n</tr>";
    }
}
pg_free_result($res_forstrecht);

// Natur-, Umwelt- oder Bodenschutzrecht
if (pg_num_rows($res_naturrecht) > 0) {
    while ($row = pg_fetch_array($res_naturrecht)) {
        echo "\n<tr>\n\t";
        echo "<td class='ll' title='Natur-, Umwelt- oder Bodenschutzrecht'>Naturrecht:</td>";
        echo "\n\t<td>Festlegung</td>";
        echo "\n\t<td class='lr'>";
        if ($showkey) {echo "<span class='key' title='Schlüssel der Festlegung'>(".$row['wert'].")</span> ";}
        echo $row['beschreibung'];
        echo "</td>";
        echo "\n</tr>";
    }
}
pg_free_result($res_naturrecht);

// Straßenrecht
if (pg_num_rows($res_strassenrecht) > 0) {
    while ($row = pg_fetch_array($res_strassenrecht)) {
        echo "\n<tr>\n\t";
        echo "<td class='ll' title='Straßenrecht'>Straßenrecht:</td>";
        echo "\n\t<td>Festlegung</td>";
        echo "\n\t<td class='lr'>";
        if ($showkey) {echo "<span class='key' title='Schlüssel der Festlegung'>(".$row['wert'].")</span> ";}
        echo $row['beschreibung'];
        echo "</td>";
        echo "\n</tr>";
    }
}
pg_free_result($res_strassenrecht);

// Wasserrecht
if (pg_num_rows($res_wasserrecht) > 0) {
    while ($row = pg_fetch_array($res_wasserrecht)) {
        echo "\n<tr>\n\t";
        echo "<td class='ll' title='Wasserrecht'>Wasserrecht:</td>";
        echo "\n\t<td>Festlegung</td>";
        echo "\n\t<td class='lr'>";
        if ($showkey) {echo "<span class='key' title='Schlüssel der Festlegung'>(".$row['wert'].")</span> ";}
        echo $row['beschreibung'];
        echo "</td>";
        echo "\n</tr>";
    }
}
pg_free_result($res_wasserrecht);

// sonstiges Recht
if (pg_num_rows($res_sonstigesrecht) > 0) {
    while ($row = pg_fetch_array($res_sonstigesrecht)) {
        echo "\n<tr>\n\t";
        echo "<td class='ll' title='sonstiges Recht'>sonstiges Recht:</td>";
        echo "\n\t<td>Festlegung</td>";
        echo "\n\t<td class='lr'>";
        if ($showkey) {echo "<span class='key' title='Schlüssel der Festlegung'>(".$row['wert'].")</span> ";}
        echo $row['beschreibung'];
        echo "</td>";
        echo "\n</tr>";
    }
}
pg_free_result($res_sonstigesrecht);
// ENDE Hinweise zur gesetzlichen Klassifizierung des Flurstücks


// BEGINN Hinweise zu einer strittigen Grenze des Flurstücks
// Überschrift
$sql_strittig = "SELECT gml_id FROM aaa_ogr.ax_besondereflurstuecksgrenze WHERE endet IS NULL AND 1000 = ANY(artderflurstuecksgrenze) AND ST_touches((SELECT wkb_geometry FROM aaa_ogr.ax_flurstueck WHERE endet IS NULL AND gml_id = $1),wkb_geometry);";
pg_prepare($con, "strittigeGrenze", $sql_strittig);
$res_strittigeGrenze = pg_execute($con, "strittigeGrenze", array($gmlid));
if (pg_num_rows($res_strittigeGrenze) > 0) {
    echo "\n<tr>";
    echo "\n\t<td colspan=4 title='Hinweise zu einer strittigen Grenze des Flurstücks'><h6><img src='ico/Hinweis.ico' width='16' height='16' alt=''> ";
    echo "strittige Grenze</td></h6>";
    echo "\n</tr>";
    echo "\n<tr>";
    echo "\n<td>Strittige Grenze:</td>";
    echo "<td colspan=2>Mindestens eine Flurstücksgrenze ist als <b>strittig</b> zu bezeichnen. Sie kann nicht festgestellt werden, weil die Beteiligten sich nicht über den Verlauf einigen. Nach sachverständigem Ermessen der Katasterbehörde ist anzunehmen, dass das Liegenschaftskataster nicht die rechtmäßige Grenze nachweist.</td>";
    echo "\n<td>&nbsp;</td>";
    echo "\n</tr>";
}
// ENDE Hinweise zu einer strittigen Grenze des Flurstücks


echo "\n</table>";


// F L U R S T Ü C K S A N T R Ä G E

// Entstehung:
// Abfrage für die Antragsnummer (nach ALKIS)
$sql ="SELECT DISTINCT a.kennzeichen AS antragsnummer ";
$sql.="FROM aaa_ogr.ax_fortfuehrungsfall f ";
$sql.="LEFT JOIN aaa_ogr.aa_antrag a ON a.identifier = fachdatenobjekt_uri[1] ";
$sql.="WHERE $1 = ANY (f.zeigtaufneuesflurstueck)";
$sql.="AND NOT ($1 = ANY (f.zeigtaufaltesflurstueck));";
$v = array($flurstueckskennzeichen);
$res = pg_prepare("", $sql);
$res = pg_execute("", $v);
$entstehung_antragsnummer = "";
if ($row = pg_fetch_array($res)) {
    $entstehung_antragsnummer = $row["antragsnummer"];
} else {
    $entstehung_antragsnummer = "unbekannt";
    // Abfrage für die Antragsnummer (vor ALKIS)
    if (!empty($alb_datenarten)) {
        foreach ($alb_datenarten as $key => $alb_datenart) {
            if (strpos($alb_datenart, '5040') !== false) {
                if (strpos($alb_daten[$key], 'K') !== false) {
                    $entstehung_antragsnummer = trim (substr($alb_daten[$key], -6));
                    break;
                }
            }
        }
    }
}
pg_free_result($res);

// Entstehung:
// Abfrage für die Belegnummer (vor ALKIS)
if (!empty($alb_datenarten)) {
    foreach ($alb_datenarten as $key => $alb_datenart) {
        if (strpos($alb_datenart, '5020') !== false) {
            if (strpos($alb_daten[$key], '/ ') === false) {
                $entstehung_alb_belegnummer = $alb_daten[$key];
                break;
            }
        }
    }
}


// Entstehung:
// Abfrage für die Anlässe (nach ALKIS)
$sql ="SELECT f.anlass_id, a.value AS anlass_name ";
$sql.="FROM (SELECT DISTINCT lpad(unnest(ueberschriftimfortfuehrungsnachweis), 6, '0') AS anlass_id FROM aaa_ogr.ax_fortfuehrungsfall WHERE $1 = ANY (zeigtaufneuesflurstueck) AND NOT ($1 = ANY (zeigtaufaltesflurstueck))) AS f ";
$sql.="LEFT JOIN aaa_ogr.aa_anlassart a ON a.id = f.anlass_id ";
$sql.="ORDER BY anlass_id;";
$v = array($flurstueckskennzeichen);
$res = pg_prepare("", $sql);
$res = pg_execute("", $v);
$entstehung_anlaesse = array();
if (pg_num_rows($res) != 0) {
    while ($row = pg_fetch_array($res)) {
        array_push($entstehung_anlaesse, array($row["anlass_id"], $row["anlass_name"]));
    }
}
pg_free_result($res);

// Entstehung:
// Abfrage für den Riss (nach ALKIS)
$sql ="SELECT riss ";
$sql.="FROM prozessiert.auftrag_riss ";
$sql.="WHERE auftrag_georg = $1 OR auftrag_hybrid = $1 OR auftrag_lah = $1 ";
$sql.="ORDER BY riss;";
$v = array($entstehung_antragsnummer);
$res = pg_prepare("", $sql);
$res = pg_execute("", $v);
if ($row = pg_fetch_array($res)) {
    $entstehung_riss = $row["riss"];
} else {
    $entstehung_riss = "unbekannt";
}
pg_free_result($res);

// Fortführungen:
// Abfrage für Daten und Antragsnummern (nach ALKIS)
$sql ="SELECT DISTINCT f.gml_id, f.beginnt::date AS datum, a.kennzeichen AS antragsnummer ";
$sql.="FROM aaa_ogr.ax_fortfuehrungsfall f ";
$sql.="LEFT JOIN aaa_ogr.aa_antrag a ON a.identifier = fachdatenobjekt_uri[1] ";
$sql.="WHERE $1 = ANY (f.zeigtaufneuesflurstueck) ";
$sql.="AND $1 = ANY (f.zeigtaufaltesflurstueck) ";
$sql.="ORDER BY datum;";
$v = array($flurstueckskennzeichen);
$res = pg_prepare("", $sql);
$res = pg_execute("", $v);
$fortfuehrung_daten_antragsnummern = array();
if (pg_num_rows($res) != 0) {
    while ($row = pg_fetch_array($res)) {
        // innere Abfrage für die Anlässe (nach ALKIS)
        $sqli ="SELECT f.anlass_id, a.value AS anlass_name ";
        $sqli.="FROM (SELECT DISTINCT lpad(unnest(ueberschriftimfortfuehrungsnachweis), 6, '0') AS anlass_id FROM aaa_ogr.ax_fortfuehrungsfall WHERE gml_id = $1) AS f ";
        $sqli.="LEFT JOIN aaa_ogr.aa_anlassart a ON a.id = f.anlass_id ";
        $sqli.="ORDER BY anlass_id;";
        $vi = array($row["gml_id"]);
        $resi = pg_prepare("", $sqli);
        $resi = pg_execute("", $vi);
        $fortfuehrung_anlaesse = array();
        if (pg_num_rows($resi) != 0) {
            while ($rowi = pg_fetch_array($resi)) {
                array_push($fortfuehrung_anlaesse, array($rowi["anlass_id"], $rowi["anlass_name"]));
            }
        }
        pg_free_result($resi);
        
        // innere Abfrage für den Riss (nach ALKIS)
        $sqlj ="SELECT riss ";
        $sqlj.="FROM prozessiert.auftrag_riss ";
        $sqlj.="WHERE auftrag_georg = $1 OR auftrag_hybrid = $1 OR auftrag_lah = $1 ";
        $sqlj.="ORDER BY riss;";
        $vj = array($row["antragsnummer"]);
        $resj = pg_prepare("", $sqlj);
        $resj = pg_execute("", $vj);
        if ($rowj = pg_fetch_array($resj)) {
            $fortfuehrung_riss = $rowj["riss"];
        } else {
            $fortfuehrung_riss = "kein Riss verknüpft";
        }
        pg_free_result($resj);
        
        array_push($fortfuehrung_daten_antragsnummern, array(strftime('%d.%m.%Y', strtotime($row["datum"])), $row["antragsnummer"], $fortfuehrung_anlaesse, $fortfuehrung_riss));
    }
}
pg_free_result($res);

// Fortführungen:
// Abfrage für die Belegnummer (vor ALKIS)
if (!empty($alb_datenarten)) {
    foreach ($alb_datenarten as $key => $alb_datenart) {
        if (strpos($alb_datenart, '5030') !== false) {
            if (strpos($alb_daten[$key], '/ ') === false) {
                $fortfuehrung_alb_belegnummer = $alb_daten[$key];
                break;
            }
        }
    }
}

echo "\n<hr class='thick'>";
echo "\n<br>";
echo "\n<table class='outer'>";
	echo "\n<tr>";
		echo "\n\t<td>";
            echo "\n<h2><img src='ico/Text.ico' width='16' height='16' alt=''> Flurstücksanträge</h2>";
		echo "\n\t</td>";
		echo "\n\t<td>";
		echo "\n\t</td>";
	echo "\n</tr>";
    // Kopfzeile
    echo "\n\t<tr>";
        echo "\n\t\t<td></td>";
        echo "\n\t\t<td class='head'>Datum und/oder Belegnummer</td>";
        echo "\n\t\t<td class='head'>Antragsnummer</td>";
        echo "\n\t\t<td class='head'>Anlass</td>";
        echo "\n\t\t<td class='head'>Riss</td>";
    echo "\n\t</tr>";
    // Entstehung
    echo "\n\t<tr>";
        echo "\n\t\t<td class='fett " . (!empty($fortfuehrung_daten_antragsnummern || isset($fortfuehrung_alb_belegnummer)) ? "headlight" : "") . "'>Entstehung</td>";
        echo "\n\t\t<td class='" . (!empty($fortfuehrung_daten_antragsnummern || isset($fortfuehrung_alb_belegnummer)) ? "headlight" : "") . "'>" . $entstehung_datum . (isset($entstehung_alb_belegnummer) ? " <span title='Jahr der Flurstücksentstehung/laufende Nummer der Fortführung-Schlüssel der Fortführungsart'>(" . $entstehung_alb_belegnummer . ")</span>" : "") . "</td>";
        echo "\n\t\t<td class='" . (!empty($fortfuehrung_daten_antragsnummern || isset($fortfuehrung_alb_belegnummer)) ? "headlight" : "") . "'>" . $entstehung_antragsnummer . "</td>";
        echo "\n\t\t<td class='" . (!empty($fortfuehrung_daten_antragsnummern || isset($fortfuehrung_alb_belegnummer)) ? "headlight" : "") . "'>";
            if (!empty($entstehung_anlaesse)) {
                foreach ($entstehung_anlaesse as $entstehung_anlass) {
                    if ($showkey) {
                        echo "<span class='key' title='Schlüssel der Anlassart'>(" . $entstehung_anlass[0] . ")</span> ";
                    }
                    echo $entstehung_anlass[1] . "<br/>";
                }
            } else {
                echo "unbekannt";
            }
        echo "</td>";
        echo "\n\t\t<td class='" . (!empty($fortfuehrung_daten_antragsnummern || isset($fortfuehrung_alb_belegnummer)) ? "headlight" : "") . "'>";
            if ($entstehung_riss != "unbekannt") {
                echo "<a title='Riss öffnen (via HTTP)' href='https://geo.sv.rostock.de/risse/" . substr($entstehung_riss, 0, 4) . "/PDFA/" . $entstehung_riss . "-101.pdf' target='_blank'><img class='inline-img' src='https://geo.sv.rostock.de/download/graphiken/world.png' /></a> <a title='Riss öffnen (via Netzlaufwerk)' href='file:///K:/GDS/Risse/" . substr($entstehung_riss, 0, 4) . "/PDFA/" . $entstehung_riss . "-101.pdf' target='_blank'><img class='inline-img' src='https://geo.sv.rostock.de/download/graphiken/folder.png' /></a> " . $entstehung_riss . "<br>";
            } else {
                echo $entstehung_riss;
            }
        echo "</td>";
    echo "\n\t</tr>";
    // Fortführungen
    if (!empty($fortfuehrung_daten_antragsnummern) || isset($fortfuehrung_alb_belegnummer)) {
        if (isset($fortfuehrung_alb_belegnummer)) {
            echo "\n\t<tr>";
                echo "\n\t\t<td class='fett'>Fortführungen</td>";
                echo "\n\t\t<td title='Jahr der Flurstücksentstehung/laufende Nummer der Fortführung-Schlüssel der Fortführungsart'>" . $fortfuehrung_alb_belegnummer . "</td>";
                echo "\n\t\t<td>unbekannt</td>";
                echo "\n\t\t<td>unbekannt</td>";
                echo "\n\t\t<td>unbekannt</td>";
            echo "\n\t</tr>";
        }
        if (!empty($fortfuehrung_daten_antragsnummern)) {
            $i = 0;
            foreach ($fortfuehrung_daten_antragsnummern as $fortfuehrung_datum_antragsnummer) {
                echo "\n\t<tr>";
                    if ($i == 0 && !isset($fortfuehrung_alb_belegnummer)) {
                        echo "\n\t\t<td class='fett'>Fortführungen</td>";
                    } else {
                        echo "\n\t\t<td class='fett'></td>";
                    }
                    $i++;
                    echo "\n\t\t<td>" . $fortfuehrung_datum_antragsnummer[0] . "</td>";
                    echo "\n\t\t<td>" . $fortfuehrung_datum_antragsnummer[1] . "</td>";
                    echo "\n\t\t<td>";
                        if (!empty($fortfuehrung_datum_antragsnummer[2])) {
                            foreach ($fortfuehrung_datum_antragsnummer[2] as $fortfuehrung_anlass) {
                                if ($showkey) {
                                    echo "<span class='key' title='Schlüssel der Anlassart'>(" . $fortfuehrung_anlass[0] . ")</span> ";
                                }
                                echo $fortfuehrung_anlass[1] . "<br/>";
                            }
                        } else {
                            echo "unbekannt";
                        }
                    echo "</td>";
                    echo "\n\t\t<td>";
                        if ($fortfuehrung_datum_antragsnummer[3] != "kein Riss verknüpft") {
                            echo "<a title='Riss öffnen (via HTTP)' href='https://geo.sv.rostock.de/risse/" . substr($fortfuehrung_datum_antragsnummer[3], 0, 4) . "/PDFA/" . $fortfuehrung_datum_antragsnummer[3] . "-101.pdf' target='_blank'><img class='inline-img' src='https://geo.sv.rostock.de/download/graphiken/world.png' /></a> <a title='Riss öffnen (via Netzlaufwerk)' href='file:///K:/GDS/Risse/" . substr($fortfuehrung_datum_antragsnummer[3], 0, 4) . "/PDFA/" . $fortfuehrung_datum_antragsnummer[3] . "-101.pdf' target='_blank'><img class='inline-img' src='https://geo.sv.rostock.de/download/graphiken/folder.png' /></a> " . $fortfuehrung_datum_antragsnummer[3] . "<br>";
                        } else {
                            echo $fortfuehrung_datum_antragsnummer[3];
                        }
                    echo "</td>";
                echo "\n\t</tr>";
            }
        }
    }
echo "\n</table>\n";

// Zusatzangaben (Informationen ursprünglich aus ALB)
if ($zusatzangaben[0] != '' || (!empty($alb_datenarten) && (in_array('http://www.lverma-mv.de/_fdv#5010', $alb_datenarten) || in_array('http://www.lverma-mv.de/_fdv#5040', $alb_datenarten)))) {
    echo "\n<br>";
    echo "\n<br>";
    echo "\n<h5><img src='ico/Hinweis.ico' width='16' height='16' alt=''> Zusatzangaben (Informationen ursprünglich aus ALB)</h5>";
    if ($zusatzangaben[0] != '') {
        $i = 0;
        foreach($zusatzangaben AS $zusatzangabe) {
            if ($i > 0) {
                echo "\n<br>";
            }
            $i++;
            echo trim($zusatzangabe, '"');
        }
    }
    if (!empty($alb_datenarten)) {
        $schon = false;
        foreach ($alb_datenarten as $key => $alb_datenart) {
            if (strpos($alb_datenart, '5010') !== false) {
                if ($zusatzangaben[0] != '') {
                    echo "\n<br>";
                }
                echo $alb_daten[$key];
                $schon = true;
                break;
            }
        }
        foreach ($alb_datenarten as $key => $alb_datenart) {
            if (strpos($alb_datenart, '5040') !== false) {
                if ($schon === true || ($zusatzangaben[0] != '' && $schon === false)) {
                    echo "\n<br>";
                }
                echo $alb_daten[$key];
                break;
            }
        }
    }
}

// ENDE F L U R S T Ü C K S A N T R Ä G E


// G R U N D B U C H
echo "\n<hr class='thick'>";
echo "\n<br>";
echo "\n<table class='outer'>";
	echo "\n<tr>";
		echo "\n\t<td>";
            echo "\n<h2><img src='ico/Grundbuch_zu.ico' width='16' height='16' alt=''> Grundbuch</h2>";
		echo "\n\t</td>";
		echo "\n\t<td>";
			echo "\n\t\t<p class='nwlink noprint'>";
				echo "\n\t\t\t<a href='".$_SERVER['PHP_SELF']. "?gkz=".$gkz."&amp;gmlid=".$gmlid;
				if ($idanzeige) { echo "&amp;id=j";}
				if ($showkey)   {echo "&amp;showkey=j";}
			echo "\n\t\t</p>";
		echo "\n\t</td>";
	echo "\n</tr>";
echo "\n</table>\n";

// B U C H U N G S S T E L L E N  zum FS (istGebucht)
$sql ="SELECT DISTINCT s.gml_id, s.buchungsart, s.laufendenummer as lfd, s.zaehler, s.nenner, ";
$sql.="s.nummerimaufteilungsplan as nrpl, s.beschreibungdessondereigentums as sond, a.beschreibung AS bart, s.beginnt ";
$sql.="FROM aaa_ogr.ax_buchungsstelle s ";
$sql.="JOIN aaa_ogr.ax_flurstueck f ON f.istgebucht = s.gml_id ";
$sql.="LEFT JOIN aaa_ogr.ax_buchungsart_buchungsstelle a ON a.wert = s.buchungsart ";
$sql.="WHERE f.endet IS NULL AND s.endet IS NULL AND f.gml_id = $1";
$sql.="ORDER BY s.beginnt DESC LIMIT 1;";
$v = array($gmlid);
$ress = pg_prepare("", $sql);
$ress = pg_execute("", $v);
if (!$ress) {
	echo "\n<p class='err'>Keine Buchungsstelle.</p>\n";
	if ($debug > 2) {echo "<p class='dbg'>SQL=<br>".$sql."<br>$1 = gml_id = '".$gmlid."'</p>";}
}
$bs=0; // Z.Buchungsstelle
while($rows = pg_fetch_array($ress)) {
	$gmls=$rows["gml_id"]; // gml b-Stelle
	$lfd=$rows["lfd"]; // BVNR

	// B U C H U N G S B L A T T  zur Buchungsstelle (istBestandteilVon)
	$sql ="SELECT b.gml_id, b.bezirk, b.buchungsblattnummermitbuchstabenerweiterung as blatt, b.blattart, ";
    $sql.="z.bezeichnung ";
    $sql.="FROM aaa_ogr.ax_buchungsblatt b ";
    $sql.="JOIN aaa_ogr.ax_buchungsstelle s ON s.istbestandteilvon = b.gml_id ";
    $sql.="LEFT JOIN aaa_ogr.ax_buchungsblattbezirk z ON z.bezirk = b.bezirk ";
    $sql.="WHERE b.endet IS NULL AND s.endet IS NULL AND z.endet IS NULL AND s.gml_id = $1";
    $sql.="ORDER BY b.bezirk, b.buchungsblattnummermitbuchstabenerweiterung;";
	$v = array($gmls);
	$resg = pg_prepare("", $sql);
	$resg = pg_execute("", $v);
	if (!$resg) {
		echo "\n<p class='err'>Kein Buchungsblatt.</p>\n";
		if ($debug > 2) {echo "<p class='dbg'>SQL=<br>".$sql."<br>$1 = gml_id = '".$gmls."'</p>";}
	}
	$bl=0; // Z.Blatt
	while($rowg = pg_fetch_array($resg)) {
		$gmlg=$rowg["gml_id"];
		$beznam=$rowg["bezeichnung"];
		$blattkeyg=$rowg["blattart"];
		$blattartg=blattart($blattkeyg);

		echo "\n<table class='outer'>";
		echo "\n<tr>"; // 1 row only
			echo "\n\t<td>"; // Outer linke Spalte:

				// Rahmen mit GB-Kennz
				if ($blattkeyg == 1000) {
					echo "\n\t<table class='kennzgb'>";
				}else {
					echo "\n\t<table class='kennzgbf'>"; // dotted
				}
					echo "\n\t<tr>\n\t\t<td class='head'>Bezirk</td>";
						echo "\n\t\t<td class='head'>Blatt</td>";
						echo "\n\t\t<td class='head'>BVNR</td>";
						echo "\n\t\t<td class='head'>Buchungsart</td>";
					echo "\n\t</tr>";
					echo "\n\t<tr>";
						echo "\n\t\t<td title='Grundbuchbezirk'>";
							if ($showkey) {
								echo "<span class='key' title='Grundbuchbezirksschlüssel'>".$land.$rowg["bezirk"]."</span><br>";
							}
						echo $beznam."&nbsp;</td>";

						echo "\n\t\t<td title='Grundbuchblattnummer'><span class='wichtig'>".ltrim($rowg["blatt"], "0")."</span></td>";

						echo "\n\t\t<td title='Bestandsverzeichnisnummer (laufende Nummer)'>".$rows["lfd"]."</td>";

						echo "\n\t\t<td title='Buchungsart'>";
							if ($showkey) {
								echo "<span class='key' title='Buchungsartschlüssel'>".$rows["buchungsart"]."</span><br>";
							}
						echo $rows["bart"]."</td>";
					echo "\n\t</tr>";
				echo "\n\t</table>";

				// Miteigentumsanteil
				if ($rows["zaehler"] <> "") {
					echo "\n<p class='ant'>".$rows["zaehler"]."/".$rows["nenner"]."&nbsp;Anteil am Flurstück</p>";
				}
			echo "\n</td>";

			echo "\n<td>"; // Outer rechte Spalte: NW-Links
				if ($idanzeige) {
					linkgml($gkz, $gmls, "Buchungsstelle");
					echo "<br>";
					linkgml($gkz, $gmlg, "Buchungsblatt");
				}
				echo "\n\t<p class='nwlink noprint'>weitere Auskunft:<br>";
					echo "\n\t\t<a href='alkisbestnw.php?gkz=".$gkz."&amp;gmlid=".$gmlg."#bvnr".$lfd;
						if ($idanzeige) {echo "&amp;id=j";}
						if ($showkey)   {echo "&amp;showkey=j";}
						echo "' title='Bestandsnachweis'>";
						echo $blattartg." <img src='ico/GBBlatt_link.ico' width='16' height='16' alt=''>";
					echo "</a>";
				echo "\n\t</p>";
			echo "\n</td>";
		echo "\n</tr>";
		echo "\n</table>";
        

		// +++ Weitere Felder ausgeben? BeschreibungDesUmfangsDerBuchung
		if ($rows["sond"] != "") {
			echo "<p class='sond' title='Sondereigentum'>Verbunden mit dem Sondereigentum…<br>…".$rows["sond"]."</p>";
		}
		if ($rows["nrpl"] != "") {
			echo "<p class='nrap' title='Nummer im Aufteilungsplan'>Nummer <span class='wichtig'>".$rows["nrpl"]."</span> im Aufteilungsplan.</p>";
		}

		// E I G E N T U E M E R, zum GB
		// Person <-benennt< AX_Namensnummer  >istBestandteilVon-> AX_Buchungsblatt
        $n = eigentuemer($con, $gmlg, false, ""); // ohne Adresse
        if ($n == 0) {
            if ($blattkeyg == 1000) {
                echo "\n<p class='err'>Keine Eigentümer gefunden!</p>";
                linkgml($gkz, $gmlg, "Buchungsblatt");
            } else {
                echo "<hr style='height: 1px; color: #fff; background-color: #fff; border-top: 1px dotted #ffbbbb;'>";
            }
		}
		$bl++;
	}
	if ($bl == 0) {
		echo "\n<p class='err'>Kein Buchungsblatt gefunden.</p>";
		echo "\n<p class='err'>Parameter: gml_id= ".$gmls.", Beziehung='istBestandteilVon'</p>";
		linkgml($gkz, $gmls, "Buchungstelle");
	}

	// Buchungstelle  >an>  Buchungstelle  >istBestandteilVon>  BLATT  ->  Bezirk
	$sql ="SELECT sf.gml_id AS s_gml, sf.buchungsart, sf.laufendenummer as lfd, ";
	$sql.="sf.zaehler, sf.nenner, sf.nummerimaufteilungsplan as nrpl, sf.beschreibungdessondereigentums as sond, ";
	$sql.="b.gml_id AS g_gml, b.bezirk, b.buchungsblattnummermitbuchstabenerweiterung as blatt, b.blattart, ";
	$sql.="z.bezeichnung, a.beschreibung AS bart ";
    $sql.="FROM aaa_ogr.ax_buchungsstelle sb ";
    $sql.="JOIN aaa_ogr.ax_buchungsstelle sf ON sb.gml_id = ANY(sf.an) ";
    $sql.="JOIN aaa_ogr.ax_buchungsblatt b ON b.gml_id = sf.istbestandteilvon ";
    $sql.="LEFT JOIN aaa_ogr.ax_buchungsblattbezirk z ON z.bezirk = b.bezirk ";
    $sql.="LEFT JOIN aaa_ogr.ax_buchungsart_buchungsstelle a ON a.wert = sf.buchungsart ";
    $sql.="WHERE sb.endet IS NULL AND sf.endet IS NULL AND b.endet IS NULL AND z.endet IS NULL AND sb.gml_id = $1";
    $sql.="ORDER BY b.bezirk, b.buchungsblattnummermitbuchstabenerweiterung;";
	$v = array($gmls);
	$resan = pg_prepare("", $sql);
	$resan = pg_execute("", $v);
	if (!$resan) {
		echo "\n<p class='err'>Keine weiteren Buchungsstellen.</p>\n";
		if ($debug > 2) {echo "<p class='dbg'>SQL=<br>".$sql."<br>$1 = gml_id = '".$gmls."'</p>";}
	}
	$an=0; // Stelle an Stelle
	while($rowan = pg_fetch_array($resan)) {
		$beznam=$rowan["bezeichnung"];
		$blattkeyan=$rowan["blattart"]; // Schluessel von Blattart
		$blattartan=blattart($blattkeyan);
		echo "\n<table class='outer'>";
		echo "\n<tr>"; // 1 row only
			echo "\n<td>"; // outer linke Spalte
				// Rahmen mit Kennzeichen GB
				if ($blattkeyan == 1000) {
					echo "\n\t<table class='kennzgb'>";
				} else {
					echo "\n\t<table class='kennzgbf'>"; // dotted
				}
					echo "\n\t<tr>";
						echo "\n\t\t<td class='head'>Bezirk</td>";
						echo "\n\t\t<td class='head'>Blatt</td>";
						echo "\n\t\t<td class='head'>BVNR</td>";
						echo "\n\t\t<td class='head'>Buchungsart</td>";
					echo "\n\t</tr>";
					echo "\n\t<tr>";
						echo "\n\t\t<td title='Grundbuchbezirk'>";
						if ($showkey) {echo "<span class='key' title='Grundbuchbezirksschlüssel'>".$land.$rowan["bezirk"]."</span><br>";}
						echo $beznam."</td>";

						echo "\n\t\t<td title='Grundbuchblattnummer'><span class='wichtig'>".ltrim($rowan["blatt"], "0")."</span></td>";

						echo "\n\t\t<td title='Bestandsverzeichnisnummer (laufende Nummer)'>".$rowan["lfd"]."</td>";

						echo "\n\t\t<td title='Buchungsart'>";
							if ($showkey) {echo "<span class='key' title='Buchungsartschlüssel'>".$rowan["buchungsart"]."</span><br>";}
							echo $rowan["bart"];
						echo "</td>";
					echo "\n\t</tr>";
				echo "\n\t</table>";
				if ($rowan["zaehler"] <> "") {
					echo "\n<p class='ant'>".$rowan["zaehler"]."/".$rowan["nenner"]."&nbsp;Anteil am Flurstück</p>";
				}
			echo "\n</td>";
			echo "\n<td>"; // outer rechte Spalte
				if ($idanzeige) {
					linkgml($gkz, $rowan["s_gml"], "Buchungsstelle");
					echo "<br>";
					linkgml($gkz, $rowan["g_gml"], "Buchungsblatt");
				}
				echo "\n<br>";
				echo "\n\t<p class='nwlink'>";
					echo "\n\t\t<a href='alkisbestnw.php?gkz=".$gkz."&amp;gmlid=".$rowan["g_gml"];
						if ($idanzeige) {echo "&amp;id=j";}
						if ($showkey)   {echo "&amp;showkey=j";}
						echo "' title='Bestandsnachweis'>";
						echo $blattartan;
						echo " <img src='ico/GBBlatt_link.ico' width='16' height='16' alt=''>";
					echo "</a>";
				echo "\n\t</p>";
			echo "\n\t</td>";
		echo "\n</tr>";
		echo "\n</table>";

		//++ BeschreibungDesUmfangsDerBuchung?
		if ($rowan["nrpl"] != "") {
			echo "<p class='nrap' title='Nummer im Aufteilungsplan'>Nummer <span class='wichtig'>".$rowan["nrpl"]."</span> im Aufteilungsplan.</p>";
		}
		if ($rowan["sond"] != "") {
			echo "<p class='sond' title='Sondereigentum'>Verbunden mit dem Sondereigentum…<br>…".$rowan["sond"]."</p>";
		}
		$n = eigentuemer($con, $rowan["g_gml"], false, ""); // ohne Adresse
		$an++;	
	}
	pg_free_result($resan);
	$bs++;
}
pg_free_result($resg);
if ($bs == 0) {
	echo "\n<p class='err'>Keine Buchungstelle gefunden.</p>";
	linkgml($gkz, $gmlid, "Flurstück");
}
pg_close($con);
echo <<<END

<form action=''>
	<div class='buttonbereich noprint'>
	<hr class='thick'>
		<a title="zurürck zur vorherigen Ansicht" href='javascript:history.back()'><img src="ico/zurueck.ico" width="16" height="16" alt="zurück"></a>&nbsp;
		<a title="drucken" href='javascript:window.print()'><img src="ico/print.ico" width="16" height="16" alt="Drucken"></a>&nbsp;
		<a title="als CSV-Datei exportieren" href='javascript:ALKISexport()'><img src="ico/download_fs.ico" width="32" height="16" alt="Export"></a>&nbsp;
	</div>
</form>
END;

footer($gmlid, $_SERVER['PHP_SELF']."?", "&amp;eig=".$eig);

?>
    </body>
</html>
