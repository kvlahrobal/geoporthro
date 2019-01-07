<?php
//session_start();
$cntget = extract($_GET);
require_once("alkis_conf_location.php");
if ($auth == "mapbender") {require_once($mapbender);}
include("alkisfkt.php");
if ($id == "j") {	$idanzeige=true;} else {$idanzeige=false;}
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
	<title>Eigentümernachweis</title>
	<link rel="stylesheet" type="text/css" href="alkisauszug.css">
	<link rel="shortcut icon" type="image/x-icon" href="ico/Eigentuemer_2.ico">
	<script type="text/javascript">
		function ALKISexport() {
			window.open(<?php echo "'alkisexport.php?gkz=".$gkz."&tabtyp=person&gmlid=".$gmlid."'"; ?>);
		}
	</script>
	<style type='text/css' media='print'>
		.noprint {visibility: hidden;}
	</style>
</head>
<body>

<?php
$con = pg_connect("host=".$dbhost." port=".$dbport." dbname=".$dbname." user=".$dbuser." password=".$dbpass);#." sslmode=".$sslmode);
// Balken
echo "<p class='nakennz'>Eigentümer&nbsp;</p>\n";

echo "\n<h2><img src='ico/Eigentuemer.ico' width='16' height='16' alt=''> Eigentümer</h2>\n";
if (!$con) "\n<p class='err'>Fehler beim Verbinden der DB</p>\n";

$sql="SELECT to_char(p.beginnt::date, 'DD.MM.YYYY') AS datum, p.nachnameoderfirma, p.anrede, p.vorname, p.geburtsname, p.geburtsdatum, p.namensbestandteil, p.akademischergrad, a.value AS anlass_bezeichnung, a.id AS anlass_schluessel, pa.kennzeichen AS antrag, pq.qualitaetsangabe ";
$sql.="FROM aaa_ogr.ax_person p ";
$sql.="LEFT JOIN aaa_ogr.aa_anlassart a ON lpad(p.anlass[1], 6, '0') = a.id ";
$sql.="LEFT JOIN aaa_ogr.aa_antrag pa ON pa.identifier = p.uri[1] AND pa.endet = (SELECT max(endet) FROM aaa_ogr.aa_antrag WHERE identifier = p.uri[1]) ";
$sql.="LEFT JOIN prozessiert.personen_qualitaetsangaben pq ON p.gml_id = pq.person_gml_id ";
$sql.="WHERE p.gml_id = $1 AND p.endet IS NULL;";

$v = array($gmlid);
$res = pg_prepare("", $sql);
$res = pg_execute("", $v);

if (!$res) {echo "\n<p class='err'>Fehler bei Zugriff auf Namensnummer</p>\n";}
if ($idanzeige) { linkgml($gkz, $gmlid, "Eigentümer"); }
if ($row = pg_fetch_array($res)) {
	$vor=htmlentities($row["vorname"], ENT_QUOTES, "UTF-8");
	$nam=htmlentities($row["nachnameoderfirma"], ENT_QUOTES, "UTF-8");
	$geb=htmlentities($row["geburtsname"], ENT_QUOTES, "UTF-8");
	$anrk=$row["anrede"];
	$anr=anrede($anrk);
	$nbest=$row["namensbestandteil"];
	$aka=$row["akademischergrad"];
	$anlass_bezeichnung=$row["anlass_bezeichnung"];
	$anlass_schluessel=$row["anlass_schluessel"];
	$datum=$row["datum"];
	$antrag=$row["antrag"];
	$qualitaetsangabe=$row["qualitaetsangabe"];
    
    if ($row["geburtsdatum"] != '')
        $geburtsdatum=strftime('%d.%m.%Y', strtotime($row["geburtsdatum"]));
    else
        $geburtsdatum='';

	echo "<table>\n";
		echo "\t<tr><td class='nhd'>Anrede:</td><td class='nam'>".$anr."&nbsp;</td></tr>\n";
		echo "\t<tr><td class='nhd'>akademischer Grad:</td><td class='nam'>".$aka."&nbsp;</td></tr>\n";
		echo "\t<tr><td class='nhd'>Nachname oder Firma:</td><td class='nam'>".$nam."</td></tr>\n";
		echo "\t<tr><td class='nhd'>Namensbestandteil:</td><td class='nam'>".$nbest."&nbsp;</td></tr>\n";
		echo "\t<tr><td class='nhd'>Vorname:</td><td class='nam'>".$vor."&nbsp;</td></tr>\n";
		echo "\t<tr><td class='nhd'>Geburtsname:</td><td class='nam'>".$geb."&nbsp;</td></tr>\n";
		echo "\t<tr><td class='nhd'>Geburtsdatum:</td><td class='nam'>".$geburtsdatum."&nbsp;</td></tr>\n";
        echo "\t<tr><td class='nhd'>Metadaten:</td>";
            echo "\t<td>Antrag:</td><td>".$antrag."&nbsp;</td></tr>\n";
            echo "\t<tr><td class='nhd'></td><td>Datum:</td><td>".$datum."&nbsp;</td></tr>\n";
            if ($showkey)
                echo "\t<tr><td class='nhd'></td><td>Anlass:</td><td><span class='key' title='Schlüssel der Anlassart'>(".$anlass_schluessel.")</span> ".$anlass_bezeichnung."&nbsp;</td></tr>\n";
            else
                echo "\t<tr><td class='nhd'></td><td>Anlass:</td><td>".$anlass_bezeichnung."&nbsp;</td></tr>\n";
            echo "\t<tr><td class='nhd'></td><td>Quelle:</td><td>".$qualitaetsangabe."&nbsp;</td></tr>\n";
	echo "\n</table>\n<hr class='thick'>\n";

	// A d r e s s e
	echo "\n<h3><img src='ico/Strasse_mit_Haus.ico' width='16' height='16' alt=''> Adresse</h3>\n";
	$sqla ="SELECT to_char(a.beginnt::date, 'DD.MM.YYYY') AS datum, a.gml_id, a.ort_post, a.postleitzahlpostzustellung AS plz, a.strasse, a.hausnummer, a.bestimmungsland, aaa.value AS anlass_bezeichnung, aaa.id AS anlass_schluessel, aa.kennzeichen AS antrag, aq.qualitaetsangabe ";
	$sqla.="FROM aaa_ogr.ax_anschrift a ";
    $sqla.="LEFT JOIN aaa_ogr.ax_person p ON a.gml_id = ANY(p.hat) ";
    $sqla.="LEFT JOIN aaa_ogr.aa_anlassart aaa ON lpad(a.anlass[1], 6, '0') = aaa.id ";
    $sqla.="LEFT JOIN aaa_ogr.aa_antrag aa ON aa.identifier = a.uri[1] AND aa.endet = (SELECT max(endet) FROM aaa_ogr.aa_antrag WHERE identifier = a.uri[1]) ";
    $sqla.="LEFT JOIN prozessiert.anschriften_qualitaetsangaben aq ON a.gml_id = aq.anschrift_gml_id ";
	$sqla.="WHERE p.gml_id = $1 AND p.endet IS NULL AND a.endet IS NULL ";
    // Es können redundante Adressen vorhanden sein, z.B. aus Migration, temporär aus LBESAS. Die letzte davon anzeigen.
	$sqla.="ORDER BY a.gml_id DESC ;";

	$v = array($gmlid);
	$resa = pg_prepare("", $sqla);
	$resa = pg_execute("", $v);
	if (!$resa) {
		echo "\n<p class='err'>Fehler bei Adressen</p>\n";
		if ($debug > 2) {	
			echo "<p class='err'>SQL=<br>".$sqla."<br>$1=gml(Person)= '".$gmlid."'</p>\n";
		}
	}

	$j=0;
	// Parameter $multiadress = j zeigt alle Adressen an
	while($rowa = pg_fetch_array($resa)) {
		$j++;
		if ($multiadress == "j" OR $j == 1) {
			$gmla=$rowa["gml_id"];
			$plz=$rowa["plz"]; // integer
      $land=htmlentities($rowa["bestimmungsland"], ENT_QUOTES, "UTF-8");
      if($plz == 0) {
        $plz="";
      } else if($land == "DEUTSCHLAND" or $land == "") {
        $plz=str_pad($plz, 5, "0", STR_PAD_LEFT);
      }
			$ort=htmlentities($rowa["ort_post"], ENT_QUOTES, "UTF-8");
			$str=htmlentities($rowa["strasse"], ENT_QUOTES, "UTF-8");
			$hsnr=$rowa["hausnummer"];
            $aanlass_bezeichnung=$rowa["anlass_bezeichnung"];
            $aanlass_schluessel=$rowa["anlass_schluessel"];
            $adatum=$rowa["datum"];
            $aantrag=$rowa["antrag"];
            $aqualitaetsangabe=$rowa["qualitaetsangabe"];
			if ($idanzeige) { linkgml($gkz, $gmla, "Adresse"); }

			echo "<table>\n";
				echo "\t<tr><td class='nhd'>Straße:</td><td class='nam'>".$str."</td></tr>\n";
				echo "\t<tr><td class='nhd'>Hausnummer:</td><td class='nam'>".str_replace(' ', '', $hsnr)."</td></tr>\n";
				echo "\t<tr><td class='nhd'>Postleitzahl:</td><td class='nam'>".$plz."</td></tr>\n";
				echo "\t<tr><td class='nhd'>Ort:</td><td class='nam'>".$ort."</td></tr>\n";
				echo "\t<tr><td class='nhd'>Land:</td><td class='nam'>".$land."</td></tr>\n";
                echo "\t<tr><td class='nhd'>Metadaten:</td>";
                    echo "\t<td>Antrag:</td><td>".$aantrag."&nbsp;</td></tr>\n";
                    echo "\t<tr><td class='nhd'></td><td>Datum:</td><td>".$adatum."&nbsp;</td></tr>\n";
                    if ($showkey)
                        echo "\t<tr><td class='nhd'></td><td>Anlass:</td><td><span class='key' title='Schlüssel der Anlassart'>(".$aanlass_schluessel.")</span> ".$aanlass_bezeichnung."&nbsp;</td></tr>\n";
                    else
                        echo "\t<tr><td class='nhd'></td><td>Anlass:</td><td>".$aanlass_bezeichnung."&nbsp;</td></tr>\n";
                    echo "\t<tr><td class='nhd'></td><td>Quelle:</td><td>".$aqualitaetsangabe."&nbsp;</td></tr>\n";
			echo "\n</table>\n<br>\n";

			// Name und Adresse Kompakt (im Rahmen) - Alles was man fuer ein Anschreiben braucht
			echo "<img src='ico/Namen.ico' width='16' height='16' alt='Brief' title='Anschrift'>"; // Symbol "Brief"
			echo "\n<div class='adr' title='Anschrift'>".$anr." ".$aka." ".$vor." ".$nbest." ".$nam."<br>";
			echo "\n".$str." ".str_replace(' ', '', $hsnr)."<br>";
			echo "\n".$plz." ".$ort."</div>";
		}
	}
	pg_free_result($resa);
	if ($j == 0) {
		echo "\n<p class='err'>Keine Adressen.</p>\n";
	} elseif ($j > 1) {
		echo "\n\t\t<p class='nwlink noprint'>";
		echo "\n\t\t\t<a href='".$_SERVER['PHP_SELF']. "?gkz=".$gkz."&amp;gmlid=".$gmlid;
		if ($idanzeige) {echo "&amp;id=j";}
		if ($showkey) {echo "&amp;showkey=j";}
		if ($multiadress == "j") {
			echo "&amp;multiadress=n' title='mehrfache Adressen unterdrücken'>erste Adresse ";
		} else {
			echo "&amp;multiadress=j' title='Adressen ggf. mehrfach vorhanden'>alle Adressen ";
		}
		echo "\n\t\t\t</a>";
		echo "\n\t\t</p>";
	}

	// *** G R U N D B U C H ***
	echo "\n<hr class='thick'>\n<h3><img src='ico/Grundbuch_zu.ico' width='16' height='16' alt=''> Grundbücher</h3>\n";
	// person <benennt< namensnummer >istBestandteilVon>                Buchungsblatt
	//                               >bestehtAusRechtsverhaeltnissenZu> namensnummer   (Nebenzweig/Sonderfälle?)
	$sqlg ="SELECT n.gml_id AS gml_n, n.laufendenummernachdin1421 AS lfd, n.zaehler, n.nenner, g.gml_id AS gml_g, ";
	$sqlg.="g.bezirk, g.buchungsblattnummermitbuchstabenerweiterung as nr, g.blattart, b.bezeichnung AS beznam ";
    $sqlg.="FROM aaa_ogr.ax_namensnummer n ";
    $sqlg.="JOIN aaa_ogr.ax_buchungsblatt g ON g.gml_id = n.istbestandteilvon ";
    $sqlg.="LEFT JOIN aaa_ogr.ax_buchungsblattbezirk b ON b.bezirk = g.bezirk AND b.schluessel_land = g.land ";
	$sqlg.="WHERE (CASE WHEN length(n.benennt) > 16 THEN substring(n.benennt from 4 for 16) ELSE benennt END) = $1 AND n.endet IS NULL AND g.endet IS NULL AND b.endet IS NULL ";
    $sqlg.="ORDER BY g.bezirk, g.buchungsblattnummermitbuchstabenerweiterung;";
	// buchungsblatt... mal mit und mal ohne fuehrende Nullen, bringt die Sortierung durcheinander

	$v = array($gmlid);
	$resg = pg_prepare("", $sqlg);
	$resg = pg_execute("", $v);

	if (!$resg) {
		echo "\n<p class='err'>Fehler bei Grundbuch</p>\n";
		if ($debug > 2) {
			echo "\n<p class='err'>SQL=".$sqlg."</p>\n";
		}
	}
	$j=0;
	echo "<table class='eig'>";
	echo "\n<tr>";
		echo "\n\t<td class='head'>Bezirk</td>";
		echo "\n\t<td class='head'>Blattart</td>";
		echo "\n\t<td class='head'>Blatt</td>";
		echo "\n\t<td class='head'>Namensnummer</td>";
		echo "\n\t<td class='head'>Anteil</td>";
		echo "\n\t<td class='head nwlink noprint' title='weitere Auskunft'>weitere Auskunft</td>";
	echo "\n</tr>";

	while($rowg = pg_fetch_array($resg)) {
		$gmln=$rowg["gml_n"];
		$gmlg=$rowg["gml_g"];
		$namnum=kurz_namnr($rowg["lfd"]);
		$zae=$rowg["zaehler"];
		$blattkey=$rowg["blattart"];
		$blattart=blattart($blattkey);

		echo "\n<tr>";

			echo "\n\t<td class='gbl'>"; // GB-Bezirk"
				if ($showkey) {
					echo "<span class='key' title='Grundbuchbezirksschlüssel'>".$rowg["bezirk"]."</span> ";
				}
				echo "<span title='Grundbuchbezirk'>".$rowg["beznam"]."</span>";
			echo "</td>";

			echo "\n\t<td class='gbl'>"; // Blattart
				if ($showkey) {
					echo "<span class='key' title='Grundbuchblattartschlüssel'>".$blattkey."</span> ";
				}
				echo "<span title='Grundbuchblattart'>".$blattart."</span>";
			echo "</td>";

			echo "\n\t<td class='gbl'>"; // Blatt
				echo "<span class='wichtig' title='Grundbuchblattnummer'>".ltrim($rowg["nr"], "0")."</span>";
				if ($idanzeige) {
					linkgml($gkz, $gmlg, "Blatt");
				}
			echo "</td>";

			echo "\n\t<td class='gbl'>"; // Namensnummer
				if ($namnum == "") {
					echo "&nbsp;";
				} else {
					echo "<span title='Namensnummer'>".$namnum."</span>";
				}
				if ($idanzeige) {
					linkgml($gkz, $gmln, "Namensnummer"); 
				}
			echo "</td>";

			echo "\n\t<td class='gbl'>"; // Anteil
				If ($zae == "") {
					echo "&nbsp;";
				} else {
					echo $zae."/".$rowg["nenner"]." Anteil";
				} 
			echo "</td>";

			echo "\n\t<td class='gbl'>";
				echo "\n\t\t<p class='nwlink noprint'>";
					echo "\n\t\t\t<a href='alkisbestnw.php?gkz=".$gkz."&amp;gmlid=".$gmlg;
						if ($idanzeige) {echo "&amp;id=j";}
						if ($showkey)   {echo "&amp;showkey=j";}
						echo "' title='Bestandsnachweis'>";
						echo $blattart;
					echo "\n\t\t\t<img src='ico/GBBlatt_link.ico' width='16' height='16' alt=''></a>";
				echo "\n\t\t</p>";
			echo "\n\t</td>";

		echo "\n</tr>";
		// +++ >bestehtAusRechtsverhaeltnissenZu> namensnummer ?
		// z.B. eine Namennummer "Erbengemeinschaft" zeigt auf Namensnummern mit Eigentümern
		$i++;
	}
	pg_free_result($resg);
	echo "</table>";
	if ($i == 0) {echo "\n<p class='err'>Kein Grundbuch.</p>\n";}
} else {
	echo "\n\t<p class='err'>Fehler! Kein Treffer für\n\t<a target='_blank' href='alkisrelationen.php?gkz=".$gkz."&amp;gmlid=".$gmlid."'>".$gmlid."</a>\n</p>\n\n";
}
?>

<form action=''>
	<div class='buttonbereich noprint'>
	<hr class='thick'>
		<a title="zurürck zur vorherigen Ansicht" href='javascript:history.back()'><img src="ico/zurueck.ico" width="16" height="16" alt="zurück"></a>&nbsp;
		<a title="drucken" href='javascript:window.print()'><img src="ico/print.ico" width="16" height="16" alt="Drucken"></a>&nbsp;
		<a title="als CSV-Datei exportieren" href='javascript:ALKISexport()'><img src="ico/download_fs.ico" width="32" height="16" alt="Export"></a>&nbsp;
	</div>
</form>

<?php footer($gmlid, $_SERVER['PHP_SELF']."?", ""); ?>

</body>
</html>