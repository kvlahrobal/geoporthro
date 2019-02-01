<?php
function fzerleg($fs) {
/*	Flurstückskennzeichen (20) zerlegen als lesbares Format (wie im Balken):
	Dies FS-Kennz-Format wird auch als Eingabe in der Navigation akzeptiert 
   ....*....1....*....2
   ll    fff     nnnn
     gggg   zzzzz    __
*/
	$fst=rtrim($fs,"_");	
	$zer=substr ($fst, 2, 4)."-".ltrim(substr($fst, 6, 3), "0")."-<b>".ltrim(substr($fst, 9, 5),"0");
	$nenn=ltrim(substr($fst, 14), "0");
	if ($nenn != "") {$zer.="/".$nenn;}
	$zer.="</b>";
	return $zer; 
}

function gemkg_name($gkey) {
// Schluessel wird uebergeben, Name in DB nachschlagen
	global $con;
	$sql = "SELECT bezeichnung FROM aaa_ogr.ax_gemarkung g WHERE g.gemarkungsnummer= $1 ;";
	$v=array($gkey);
	$res=pg_prepare("", $sql);
	$res=pg_execute("", $v);
	if (!$res) {echo "\n<p class='err'>Fehler bei Gemarkung.</p>";}
	$zgmk=0;
	while($row = pg_fetch_array($res)) { // eigentlich nur EINE
		$gmkg=$row["bezeichnung"];
		$zgmk++;
	}
	if ($zgmk == 0) {
		echo "\n<p class='err'>Gemarkung ".$gkey." ist unbekannt.</p>";
		return;
	}
	return $gmkg;
}

//session_start();
//import_request_variables("G"); // php 5.3 deprecated, php 5.4 entfernt
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
	<title>Flurstückshistorie</title>
	<link rel="stylesheet" type="text/css" href="alkisauszug.css">
	<link rel="stylesheet" type="text/css" href="css-treeview.css">
	<link rel="shortcut icon" type="image/x-icon" href="ico/Flurstueck_Historisch.ico">
	<style type='text/css' media='print'>
		.noprint {visibility: hidden;}
	</style>
</head>
<body>

<?php
$con = pg_connect("host=".$dbhost." port=" .$dbport." dbname=".$dbname." user=".$dbuser." password=".$dbpass);#." sslmode=".$sslmode);
if (!$con) echo "<p class='err'>Fehler beim Verbinden der DB</p>\n";
// if ($debug > 1) {echo "<p class='err'>DB=".$dbname.", user=".$dbuser."</p>";}

function vorgaenger($gmlid, $con) {
    // Vorgänger bestimmen
    $sql_vorgaenger = "
     SELECT
      array_agg(flurstueckskennzeichen) AS flurstueckskennzeichen
       FROM
       (SELECT DISTINCT unnest(zeigtaufaltesflurstueck) AS flurstueckskennzeichen FROM aaa_ogr.ax_fortfuehrungsfall WHERE zeigtaufaltesflurstueck != zeigtaufneuesflurstueck AND (SELECT flurstueckskennzeichen FROM aaa_ogr.ax_flurstueck WHERE endet IS NULL AND gml_id = $1) = ANY (zeigtaufneuesflurstueck)
        UNION SELECT flurstueckskennzeichen FROM aaa_ogr.ax_historischesflurstueckohneraumbezug WHERE (SELECT flurstueckskennzeichen FROM aaa_ogr.ax_flurstueck WHERE endet IS NULL AND gml_id = $1) = ANY (nachfolgerflurstueckskennzeichen)
        UNION SELECT flurstueckskennzeichen FROM aaa_ogr.ax_historischesflurstueckohneraumbezug WHERE (SELECT flurstueckskennzeichen FROM aaa_ogr.ax_flurstueck WHERE endet IS NOT NULL AND gml_id = $1 ORDER BY endet DESC LIMIT 1) = ANY (nachfolgerflurstueckskennzeichen)
        UNION SELECT flurstueckskennzeichen FROM aaa_ogr.ax_historischesflurstueckohneraumbezug WHERE (SELECT flurstueckskennzeichen FROM aaa_ogr.ax_historischesflurstueckohneraumbezug WHERE gml_id = $1) = ANY (nachfolgerflurstueckskennzeichen))
         AS tabelle";
    pg_prepare($con, "", $sql_vorgaenger);
    $res_vorgaenger = pg_execute($con, "", array($gmlid));
    if ($resultate = pg_fetch_array($res_vorgaenger)) {
        $stri = trim($resultate[0], "{}");
        $vorgaenger[0] = explode(",", $stri);
    }
    pg_free_result($res_vorgaenger);
    
    // Vorgänger von Duplikaten befreien und sortieren
    $vorgaenger[0] = array_unique($vorgaenger[0]);
    asort($vorgaenger[0]);

    return $vorgaenger;
}

function nachfolger($fskennz, $con) {
    // Nachfolger bestimmen
    $sql_nachfolger = "
     SELECT
      array_agg(flurstueckskennzeichen) AS flurstueckskennzeichen
       FROM
       (SELECT unnest(nachfolgerflurstueckskennzeichen) AS flurstueckskennzeichen FROM aaa_ogr.ax_historischesflurstueckohneraumbezug WHERE flurstueckskennzeichen = $1
        UNION SELECT DISTINCT unnest(zeigtaufneuesflurstueck) AS flurstueckskennzeichen FROM aaa_ogr.ax_fortfuehrungsfall WHERE zeigtaufaltesflurstueck != zeigtaufneuesflurstueck AND (SELECT flurstueckskennzeichen FROM aaa_ogr.ax_flurstueck WHERE endet IS NULL AND flurstueckskennzeichen = $1) = ANY (zeigtaufaltesflurstueck)
        UNION SELECT DISTINCT unnest(zeigtaufneuesflurstueck) AS flurstueckskennzeichen FROM aaa_ogr.ax_fortfuehrungsfall WHERE zeigtaufaltesflurstueck != zeigtaufneuesflurstueck AND (SELECT flurstueckskennzeichen FROM aaa_ogr.ax_flurstueck WHERE endet IS NOT NULL AND flurstueckskennzeichen = $1 ORDER BY endet DESC LIMIT 1) = ANY (zeigtaufaltesflurstueck)
        UNION SELECT DISTINCT unnest(zeigtaufneuesflurstueck) AS flurstueckskennzeichen FROM aaa_ogr.ax_fortfuehrungsfall WHERE zeigtaufaltesflurstueck != zeigtaufneuesflurstueck AND (SELECT flurstueckskennzeichen FROM aaa_ogr.ax_historischesflurstueckohneraumbezug WHERE flurstueckskennzeichen = $1) = ANY (zeigtaufaltesflurstueck))
         AS tabelle";
    pg_prepare($con, "", $sql_nachfolger);
    $res_nachfolger = pg_execute($con, "", array($fskennz));
    if ($resultate = pg_fetch_array($res_nachfolger)) {
        $stri = trim($resultate[0], "{}");
        $nachfolger[0] = explode(",", $stri);
    }
    pg_free_result($res_nachfolger);
    
    // Nachfolger von Duplikaten befreien und sortieren
    $nachfolger[0] = array_unique($nachfolger[0]);
    asort($nachfolger[0]);

    return $nachfolger;
}

// Such-Parameter bekommen? Welche?
if ($gmlid != "") { // Ja, die GML wurde uebergeben
	$parmtyp="GML";
	$parmval=$gmlid;
	$whereclause="WHERE gml_id= $1 ";
	$v = array($gmlid);
} else {	// Alternativ: das Flurstücks-Kennzeichen wurde übergeben
	if ($fskennz != "") {
		$parmtyp="Flurstückskennzeichen";
		$parmval=$fskennz;
		$whereclause="WHERE flurstueckskennzeichen= $1 "; // hinten auffuellen mit _ auf 20 Stellen
		$v = array($fskennz);
	} else { // Pfui!
		$parmtyp="";
		echo "<p class='err'>Parameter 'gmlid' oder 'fskennz' fehlt.</p>";
	}
}

if ($parmtyp != "") { // einer der beiden erlaubten Fälle
	// UNION-Abfrage auf 3ähnliche Tabellen, darin aber immer nur 1 Treffer.
	$felder="endet, array_remove(art, 'urn:adv:fachdatenverbindung:AA_Antrag') AS art, name, gml_id, gemarkung_land AS land, flurnummer, zaehler, nenner, flurstueckskennzeichen, amtlicheflaeche, zeitpunktderentstehung, gemarkungsnummer, angabenzumabschnittflurstueck, ";

	$sqlu = "SELECT 'a' AS ftyp, ".$felder."null::text[] AS nach, null::text[] AS vor ";
	$sqlu.="FROM aaa_ogr.ax_flurstueck f ".$whereclause." AND f.endet IS NULL ";
	$sqlu.="UNION ";
	$sqlu.= "SELECT 'h' AS ftyp, ".$felder."null::text[] AS nach, null::text[] AS vor ";
	$sqlu.="FROM aaa_ogr.ax_flurstueck f ".$whereclause." AND f.endet IS NOT NULL ";
	$sqlu.="UNION ";
	$sqlu.= "SELECT 'o' AS ftyp, ".$felder."nachfolgerflurstueckskennzeichen AS nach, vorgaengerflurstueckskennzeichen AS vor ";
	$sqlu.="FROM aaa_ogr.ax_historischesflurstueckohneraumbezug o ".$whereclause;
	$sqlu.="ORDER BY endet DESC LIMIT 1";
	
	$resu = pg_prepare("", $sqlu);
	$resu = pg_execute("", $v);
	if ($rowu = pg_fetch_array($resu)) {
		$ftyp=$rowu["ftyp"];
		$land=$rowu["land"];
		$gmkgnr=$rowu["gemarkungsnummer"];
		$flurnummer=$rowu["flurnummer"];
		$zaehler=$rowu["zaehler"];
		$nenner=$rowu["nenner"];
		$flstnummer=$zaehler;
		if ($nenner > 0) {$flstnummer.="/".$nenner;} // BruchNr
		$fskenn=$rowu["flurstueckskennzeichen"];
    $amtlicheflaeche=$rowu["amtlicheflaeche"]; // amtliche Fläche
    $amtlicheflaeched=($amtlicheflaeche < 1 ? rtrim(number_format($amtlicheflaeche,2,",","."),"0") : number_format($amtlicheflaeche,0,",",".")); // Display-Format dazu
		$name=$rowu["name"]; // in DB ein Array
		$arrn=explode(",", trim($name, "{}") ); // PHP-Array
        $fortfuehrungsbelegnummer=$rowu["name"]; // Fortführungsbelegnummer(n)
        $fortfuehrungsbelegnummer_array=explode(",", trim($fortfuehrungsbelegnummer, "{}") ); // PHP-Array mit Fortführungsbelegnummer(n)
		$gemkname= gemkg_name($gmkgnr);
        if (!empty($rowu["zeitpunktderentstehung"]))
            $entstehung_datum = strftime('%d.%m.%Y', strtotime($rowu["zeitpunktderentstehung"]));
        else
            $entstehung_datum = "unbekannt";
        $alb_datenarten = $rowu["art"]; // ALB-Datenarten
        $alb_datenarten = explode(",", trim(str_replace('"', '', $alb_datenarten), "{}")); // PHP-Array mit ALB-Datenarten
        $alb_daten = $rowu["name"]; // ALB-Daten
        $alb_daten = explode(",", trim(str_replace('"', '', $alb_daten), "{}")); // PHP-Array mit ALB-Daten
        $zusatzangaben = $rowu["angabenzumabschnittflurstueck"]; // Zusatzangaben
        $zusatzangaben = explode(",", trim($zusatzangaben, "{}")); // PHP-Array mit Zusatzangaben
        $vor=$rowu["vor"];
		$nach=$rowu["nach"];
		if ($gmlid == "") {$gmlid=$rowu["gml_id"];} // für selbst-link-Umschalter ueber footer
	} else {
		if ($debug > 1) {echo "<br><p class='err'>Fehler! Kein Treffer für ".$parmtyp." = '".$parmval."'</p><br>";}
		if ($debug > 2) {echo "<p class='dbg'>SQL=<br>".$sqlu."<br>$1=".$parmtyp." = '".$parmval."'</p>";}
	}
}

switch ($ftyp) { // Unterschiede Historisch/Aktuell
	case 'a': 
		$wert = "aktuell";
		$ico= "Flurstueck.ico";
		$cls= "kennzfs";	
	break;
	case 'h': 
		$wert = "historisch<br>mit Raumbezug";
		$ico= "Flurstueck_Historisch.ico"; //
		$cls= "kennzfsh";
	break;
	case 'o': 
		$wert = "historisch<br>ohne Raumbezug";
		$ico= "Flurstueck_Historisch_oR.ico";
		$cls= "kennzfsh";
	break;
	default:
		$wert = "<b>nicht gefunden: ".$parmtyp." = '".$parmval."'</b>";
		$ico= "Flurstueck_Historisch.ico";
		$cls= "kennzfsh";
	break;
}

// Balken
echo "<p class='fshis'>Flurstück <span title='Flurstückskennzeichen in der offiziellen ALKIS-Notation'>".$fskenn."</span>&nbsp;</p>\n";
echo "\n<h2><img src='ico/".$ico."' width='16' height='16' alt=''> Flurstückshistorie</h2>\n";

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

if ($ftyp == "a") { // Aktuell -> Historie
	echo "\n<p class='nwlink noprint'>weitere Auskunft: ";
		echo "<a href='alkisfsnw.php?gkz=".$gkz."&amp;gmlid=".$gmlid;
			if ($idanzeige) {echo "&amp;id=j";}
			if ($showkey)   {echo "&amp;showkey=j";}
			echo "' title='Flurstücksnachweis'>Flurstück ";
			echo "<img src='ico/Flurstueck_Link.ico' width='16' height='16' alt=''>";
		echo "</a>";
}
echo "\n<hr class='thin'>";

echo "<table class='outer'>";
	echo "\n<tr>
		<td class='head'>Flurstück</td>
		<td class='head'>Vorgänger</td>
		<td class='head'>Nachfolger</td>
	</tr>"; // Head
	
	// Spalte 1: F l u r s t ü c k
	echo "\n<tr>\n\t<td>";
		echo "<img src='ico/".$ico."' width='16' height='16' alt=''> ".$wert;
		echo "<br><span title='amtliche Fläche (Buchfläche) des Flurstücks'>Fläche</span> <span class='flae'>".$amtlicheflaeched." m²</span>";
	echo "</td>";

	// Spalte 2: V o r g ä n g e r
    $vorgaenger = vorgaenger($gmlid, $con);
    echo "\n\t<td>";
    $i = 0;
    if (isset($vorgaenger[$i]) === true && strlen($vorgaenger[$i][0]) > 0) {
        foreach($vorgaenger[$i] AS $val) {
            echo "Flurstück <a title='Historie des direkten Vorgängerflurstücks' href='".$_SERVER['PHP_SELF']."?gkz=".$gkz."&amp;fskennz=".$val;
            if ($idanzeige) {echo "&amp;id=j";}
            if ($showkey)   {echo "&amp;showkey=j";}
            echo "'>".fzerleg($val)."</a><br>";
        }
    }
	else {
        echo "keine(r) im Datenbestand vorhanden";
    }
	echo"</td>";

	// Spalte 3: N a c h f o l g e r
    $nachfolger = nachfolger($fskennz, $con);
    echo "\n\t<td>";
    $j = 0;
    if (isset($nachfolger[$j]) === true && strlen($nachfolger[$i][0]) > 0) {
        foreach($nachfolger[$j] AS $val) {
            echo "Flurstück <a title='Historie des direkten Nachfolgerflurstücks' href='".$_SERVER['PHP_SELF']."?gkz=".$gkz."&amp;fskennz=".$val;
            if ($idanzeige) {echo "&amp;id=j";}
            if ($showkey)   {echo "&amp;showkey=j";}
            echo "'>".fzerleg($val)."</a><br>";
        }
    }
	else {
        echo "noch keine(r)";
    }
	echo"</td>\n</tr>";
	echo "</td>\n</tr>";
echo "\n</table>";


// F L U R S T Ü C K S A N T R Ä G E

// Entstehung:
// Abfrage für die Antragsnummer (nach ALKIS)
$sql ="SELECT DISTINCT a.kennzeichen AS antragsnummer ";
$sql.="FROM aaa_ogr.ax_fortfuehrungsfall f ";
$sql.="LEFT JOIN aaa_ogr.aa_antrag a ON a.identifier = fachdatenobjekt_uri[1] ";
$sql.="WHERE $1 = ANY (f.zeigtaufneuesflurstueck)";
$sql.="AND NOT ($1 = ANY (f.zeigtaufaltesflurstueck));";
$v = array($fskenn);
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
$v = array($fskenn);
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
$v = array($fskenn);
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
                // Achtung: bei Objekten in AX_HistorischesFlurstueckOhneRaumbezug muss die Angabe der Fortführung unterdrückt werden, falls dieselbe Belegnummer auch bei einem der Nachfolgerflurstücke vorkommt!
                if ($ftyp == 'o') {
                    $sql ="SELECT unnest(name) AS angabe FROM aaa_ogr.ax_historischesflurstueckohneraumbezug WHERE flurstueckskennzeichen = ANY($1) ";
                    $sql.="UNION SELECT unnest(name) AS angabe FROM aaa_ogr.ax_flurstueck WHERE flurstueckskennzeichen = ANY($1);";
                    $v = array($nach);
                    $res = pg_prepare("", $sql);
                    $res = pg_execute("", $v);
                    if (pg_num_rows($res) != 0) {
                        while ($row = pg_fetch_array($res)) {
                            if ($row["angabe"] == $alb_daten[$key]) {
                                unset($fortfuehrung_alb_belegnummer);
                            }
                        }
                    }
                }
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
                $gemarkungsnummer = substr($entstehung_riss, 0, 4);
                // alle vorhandenen PDF zum Riss ermitteln anhand der Indizes der Risse-PDF
                $curl = curl_init();
                $url = "https://geo.sv.rostock.de/risse/" . $gemarkungsnummer . "/PDFA/index";
                curl_setopt($curl, CURLOPT_URL, $url); 
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
                $curl_output = curl_exec($curl); 
                curl_close($curl);
                foreach (preg_split("/((\r?\n)|(\r\n?))/", $curl_output) as $datei) {
                    if (strpos($datei, $entstehung_riss) !== false)
                        echo "<a title='Riss öffnen (via HTTP)' href='https://geo.sv.rostock.de/risse/" . $gemarkungsnummer . "/PDFA/" . $datei . "' target='_blank'><img class='inline-img' src='https://geo.sv.rostock.de/download/graphiken/world.png' /></a> <a title='Riss öffnen (via Netzlaufwerk)' href='file:///K:/GDS/Risse/" . $gemarkungsnummer . "/PDFA/" . $datei . "' target='_blank'><img class='inline-img' src='https://geo.sv.rostock.de/download/graphiken/folder.png' /></a> " . str_replace(".pdf", "", $datei) . "<br>";
                }
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
                            $rissnummer = $fortfuehrung_datum_antragsnummer[3];
                            $gemarkungsnummer = substr($rissnummer, 0, 4);
                            // alle vorhandenen PDF zum Riss ermitteln anhand der Indizes der Risse-PDF
                            $curl = curl_init();
                            $url = "https://geo.sv.rostock.de/risse/" . $gemarkungsnummer . "/PDFA/index";
                            curl_setopt($curl, CURLOPT_URL, $url); 
                            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
                            $curl_output = curl_exec($curl); 
                            curl_close($curl);
                            foreach (preg_split("/((\r?\n)|(\r\n?))/", $curl_output) as $datei) {
                                if (strpos($datei, $rissnummer) !== false)
                                    echo "<a title='Riss öffnen (via HTTP)' href='https://geo.sv.rostock.de/risse/" . $gemarkungsnummer . "/PDFA/" . $datei . "' target='_blank'><img class='inline-img' src='https://geo.sv.rostock.de/download/graphiken/world.png' /></a> <a title='Riss öffnen (via Netzlaufwerk)' href='file:///K:/GDS/Risse/" . $gemarkungsnummer . "/PDFA/" . $datei . "' target='_blank'><img class='inline-img' src='https://geo.sv.rostock.de/download/graphiken/folder.png' /></a> " . str_replace(".pdf", "", $datei) . "<br>";
                            }
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


if ($debug > 1) {
	$z=1;
	while($rowu = pg_fetch_array($resu)) {
		$ftyp=$rowu["ftyp"];
		echo "<p class='dbg'>Mehr als EIN Eintrag gefunden: '".$ftyp."' (".$z.")</p>";
		$z++;
	}
}
?>

<form action=''>
	<div class='buttonbereich noprint'>
	<hr class='thick'>
		<a title="zurürck zur vorherigen Ansicht" href='javascript:history.back()'><img src="ico/zurueck.ico" width="16" height="16" alt="zurück"></a>&nbsp;
		<a title="drucken" href='javascript:window.print()'><img src="ico/print.ico" width="16" height="16" alt="Drucken"></a>&nbsp;
	</div>
</form>

<?php footer($gmlid, $_SERVER['PHP_SELF']."?"); ?>

</body>
</html>