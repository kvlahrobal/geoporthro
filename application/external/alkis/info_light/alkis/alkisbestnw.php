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
	<title>Bestandsnachweis</title>
	<link rel="stylesheet" type="text/css" href="alkisauszug.css">
	<link rel="shortcut icon" type="image/x-icon" href="ico/Grundbuch.ico">
	<script type="text/javascript">
		function ALKISexport() {
			window.open(<?php echo "'alkisexport.php?gkz=".$gkz."&tabtyp=grundbuch&gmlid=".$gmlid."'"; ?>);
		}
	</script>
	<style type='text/css' media='print'>
		.noprint {visibility: hidden;}
	</style>
</head>
<body>
<?php
$con = pg_connect("host=".$dbhost." port=".$dbport." dbname=".$dbname." user=".$dbuser." password=".$dbpass);#." sslmode=".$sslmode);
if (!$con) echo "<p class='err'>Fehler beim Verbinden der DB</p>\n";

// G R U N D B U C H
// Direkter JOIN zwischen den "ax_buchungsblattbezirk" und "ax_dienststelle".
// Ueber Feld "gehoertzu|ax_dienststelle_schluessel|land" und "stelle".
//	Bei JOIN ueber alkis_beziehungen entgegen Dokumentation keine Verbindung gefunden.
$sql ="SELECT g.gml_id, g.bezirk, g.buchungsblattnummermitbuchstabenerweiterung AS nr, g.blattart, "; // GB-Blatt
$sql.="b.gml_id, b.bezirk, b.bezeichnung AS beznam, "; // Bezirk
$sql.="a.gml_id, a.land, a.bezeichnung, a.stelle, a.stellenart "; // Amtsgericht
$sql.="FROM aaa_ogr.ax_buchungsblatt g ";
$sql.="LEFT JOIN aaa_ogr.ax_buchungsblattbezirk b ON g.land=b.schluessel_land AND g.bezirk=b.bezirk ";  // BBZ
$sql.="LEFT JOIN aaa_ogr.ax_dienststelle a ON b.schluessel_land = a.land AND b.stelle = a.stelle ";
$sql.="WHERE g.endet IS NULL AND b.endet IS NULL AND a.endet IS NULL AND g.gml_id= $1 ";
$sql.="AND a.stellenart=1000;"; // Amtsgericht

$v = array($gmlid);
$res = pg_prepare("", $sql);
$res = pg_execute("", $v);

if (!$res) {
	echo "<p class='err'>Fehler bei Grundbuchdaten.</p>";
	if ($debug > 2) {echo "<p class='dbg'>SQL=<br>".$sql."<br>$1 = gml_id = '".$gmlid."'</p>";}
}
if ($row = pg_fetch_array($res)) {
	$blattkey=$row["blattart"]; // Schluessel
	$blattart=blattart($blattkey);
    $blattnummer=ltrim($row["nr"], "0");
	echo "<p class='gbkennz'>Bestand <span title='Buchungsblattkennzeichen in der offiziellen ALKIS-Notation'>13".$row["bezirk"].str_pad($blattnummer, 7 , "0", STR_PAD_LEFT)."</span>&nbsp;</p>\n"; // Balken
	echo "\n<h2><img src='ico/Grundbuch.ico' width='16' height='16' alt=''> Grundbuch</h2>";
	echo "\n<table class='outer'>\n<tr>\n\t<td>"; // Kennzeichen im Rahmen
		if ($blattkey == 1000) {
			echo "\n\t<table class='kennzgb'>";
		} else {
			echo "\n\t<table class='kennzgbf'>"; // dotted
		}
			echo "\n\t<tr>";
				echo "\n\t\t<td class='head'>Amt</td>";
				echo "\n\t\t<td class='head'>Bezirk</td>";
				echo "\n\t\t<td class='head'>Blatt</td>";
			echo "\n\t</tr>\n\t<tr>";
				echo "\n\t\t<td>";
				if ($showkey) {
					echo "<span class='key' title='Grundbuchamtsschlüssel'>".$row["stelle"]."</span><br>";
				}
				echo "<span title='Grundbuchamt'>".dienststellenart($row["stellenart"])." ".htmlentities($row["bezeichnung"], ENT_QUOTES, "UTF-8")."</span></td>";
				echo "\n\t\t<td>";
				if ($showkey) {
					echo "<span class='key' title='Grundbuchbezirksschlüssel'>13".$row["bezirk"]."</span><br>";
				}
				echo "<span title='Grundbuchbezirk'>".htmlentities($row["beznam"], ENT_QUOTES, "UTF-8")."</span></td>";
				echo "\n\t\t<td><span title='Grundbuchblattnummer' class='wichtig'>".$blattnummer."</span></td>";
			echo "\n\t</tr>";
		echo "\n\t</table>";

		echo "\n\n\t</td>\n\t<td>";
		if ($idanzeige) {linkgml($gkz, $gmlid, "Buchungsblatt");}
	echo "\n\t</td>\n</tr>\n</table>";
}

// Vorab pruefen, ob Sonderfall "Rechte an .." vorliegt.
if ($blattkey == 1000) { // Blatt
	$sql ="SELECT count(sf.laufendenummer) AS anzahl ";
    $sql.="FROM aaa_ogr.ax_buchungsstelle sb ";
    $sql.="JOIN aaa_ogr.ax_buchungsstelle sf ON sb.gml_id = ANY(sf.an) ";
    $sql.="JOIN aaa_ogr.ax_buchungsblatt b ON b.gml_id = sf.istbestandteilvon ";
    $sql.="WHERE sb.endet IS NULL AND sf.endet IS NULL AND b.endet IS NULL AND b.gml_id = $1;";
    $v=array($gmlid);
	$res=pg_prepare("", $sql);
	$res=pg_execute("", $v);
	if (!$res) echo "<p class='err'>Fehler bei Suche nach Buchungen.</p>\n";
	$row=pg_fetch_array($res);
	$anz=$row["anzahl"];
	//echo "<p>Zeilen : ".$anz." zu Blattart ".$blattkey."</p>";
} else { // 2000: Katasterblatt, 3000: Pseudoblatt, 5000: Fiktives Blatt
	$anz=0;
}
if ($anz > 0) {
	echo "\n<hr class='thick'>\n\n<h2><img src='ico/Flurstueck.ico' width='16' height='16' alt=''> Rechte und Flurstücke</h2>";
	echo "\n<table class='fs'>";
	echo "\n<tr>"; // 2 Kopfzeilen
		echo "\n\t<td class='head headlight' title='Bestandsverzeichnisnummer (laufende Nummer)'><span class='wichtig'>BVNR</span></td>";
		echo "\n\t<td class='dien'>herrschende Buchungsart</td>";
		echo "\n\t<td class='dien'>Anteil</td>";
		echo "\n\t<td class='dien'>Bezirk</td>";
		echo "\n\t<td class='dien'>Blatt</td>";
		echo "\n\t<td class='dien'>BVNR fiktiv</td>";
		echo "\n\t<td class='dien'>Buchungsart</td>";
		echo "\n\t<td>&nbsp;</td>";
	echo "\n</tr>";
} else {
	echo "\n<hr class='thick'>\n\n<h2><img src='ico/Flurstueck.ico' width='16' height='16' alt=''> Flurstücke</h2>";
	echo "\n<table class='fs'>";
}

echo "\n<tr>";
	echo "\n\t<td class='head right'>BVNR</td>";
	echo "\n\t<td class='head right'>Buchungsart</td>";
	echo "\n\t<td class='head right'>Anteil</td>";
	echo "\n\t<td class='head right'>Gemarkung</td>";
	echo "\n\t<td class='head right'>Flur</td>";
	echo "\n\t<td class='head right'><span class='wichtig'>Flurstück</span></td>";
	echo "\n\t<td class='head fla' title='amtliche Fläche (Buchfläche) des Flurstücks'>Fläche</td>"; // 7
	echo "\n\t<td class='head nwlink noprint' title='weitere Auskunft'>weitere Auskunft</td>";
echo "\n</tr>";

// Blatt ->  B u c h u n g s s t e l l e
// ax_buchungsblatt <istBestandteilVon< ax_buchungsstelle 
$sql ="SELECT DISTINCT s.gml_id, s.buchungsart, s.laufendenummer AS lfd, s.beschreibungdesumfangsderbuchung AS udb, ";
$sql.="s.zaehler, s.nenner, s.nummerimaufteilungsplan AS nrap, s.beschreibungdessondereigentums AS sond, a.beschreibung as bart, s.beginnt ";
$sql.="FROM aaa_ogr.ax_buchungsstelle s ";
$sql.="JOIN aaa_ogr.ax_buchungsblatt b ON b.gml_id = s.istbestandteilvon ";
$sql.="LEFT JOIN aaa_ogr.ax_buchungsart_buchungsstelle a ON a.wert = s.buchungsart ";
$sql.="WHERE s.endet IS NULL AND b.endet IS NULL AND b.gml_id = $1";
$sql.="ORDER BY s.zaehler, lfd;";
$v=array($gmlid);
$res=pg_prepare("", $sql);
$res=pg_execute("", $v);

if (!$res) {
	echo "<p class='err'>Fehler bei Buchung.</p>\n";
	if ($debug > 2) {echo "<p class='dbg'>SQL=<br>".$sql."<br>$1 = gml_id = '".$gmlid."'</p>";}
}
$i=0;
while($row = pg_fetch_array($res)) {
	$lfdnr  = $row["lfd"];
	$bvnr   = str_pad($lfdnr, 4, "0", STR_PAD_LEFT);
	$gml_bs = $row["gml_id"]; // id der buchungsstelle
	$ba     = $row["bart"]; // Buchungsart aus Schluesseltabelle

	if ($row["zaehler"] == "") {
		$anteil = "";
	} else {
		$anteil = $row["zaehler"]."/".$row["nenner"];
	}
	// F l u r s t u e c k s d a t e n  zur direkten Buchungsstelle
   $j = bnw_fsdaten($con, $lfdnr, $gml_bs, $ba, $anteil, true); // return = Anzahl der FS

	if ($j == 0) { //  k e i n e  Flurstuecke gefunden (Miteigentumsnteil usw.)
		// Bei "normalen" Grundstuecken wurden Flurstuecksdaten gefunden und ausgegeben.
		// Bei Miteigentumsanteil, Erbbaurecht usw. muss nach weiteren Buchungsstellen gesucht werden:
		//  Buchungsstelle >an/zu> (andere)Buchungsstelle >istBestandTeilVon>  "FiktivesBlatt (ohne) Eigentuemer"

		// andere Buchungsstellen
		//  ax_buchungsstelle  >zu>  ax_buchungsstelle (des gleichen Blattes)
		//  ax_buchungsstelle  >an>  ax_buchungsstelle (anderes Blatt, z.B Erbbaurecht an)

		// aktuelles Blatt (herrschendes GB) hat Recht "an" fiktives Blatt (dienendes GB-Blatt)
		// a n d e r e  Buchungsstelle
        $sql ="SELECT sb.gml_id, sb.buchungsart, sb.laufendenummer AS lfd, sb.beschreibungdesumfangsderbuchung AS udb, ";
		$sql.="CASE WHEN sb.gml_id = ANY(sf.an) THEN 'an' WHEN sb.gml_id = ANY(sf.zu) THEN 'zu' WHEN sb.gml_id = ANY(sf.durch) THEN 'durch' END AS beziehungsart, sb.nummerimaufteilungsplan AS nrap, sb.beschreibungdessondereigentums AS sond, a.beschreibung AS bart ";
        $sql.="FROM aaa_ogr.ax_buchungsstelle sb ";
        $sql.="JOIN aaa_ogr.ax_buchungsstelle sf ON sb.gml_id = ANY(sf.an) OR sb.gml_id = ANY(sf.zu) OR sb.gml_id = ANY(sf.durch) ";
        $sql.="LEFT JOIN aaa_ogr.ax_buchungsart_buchungsstelle a ON a.wert = sb.buchungsart ";
        $sql.="WHERE sb.endet IS NULL AND sf.endet IS NULL AND sf.gml_id = $1";
        $sql.="ORDER BY sb.laufendenummer;";
        $v=array($gml_bs);
		$resan=pg_prepare("", $sql);
		$resan=pg_execute("", $v);
		//$resan=pg_query($con,$sql);
		if (!$resan) {
			echo "<p class='err'>Fehler bei 'andere Buchungsstelle'.</p>\n";
			if ($debug > 2) {echo "<p class='dbg'>SQL=<br>".$sql."</p>";}
		}
		$a=0; // count: andere BS
		$altbvnr=""; // Gruppenwechsel
		while($rowan = pg_fetch_array($resan)) {
			$lfdnran = $rowan["lfd"];		// BVNR an
			$gml_bsan= $rowan["gml_id"];	// id der buchungsstelle an
			$baan= $rowan["bart"];  		// Buchungsart an, entschluesselt

			// a n d e r e s   B l a t t  (an dem das aktuelle Blatt Rechte hat)
			// dienendes Grundbuch
            $sql ="SELECT b.gml_id, b.land, b.bezirk, b.buchungsblattnummermitbuchstabenerweiterung AS blatt, b.blattart, z.bezeichnung AS beznam ";
            $sql.="FROM aaa_ogr.ax_buchungsblatt b ";
            $sql.="JOIN aaa_ogr.ax_buchungsstelle s ON s.istbestandteilvon = b.gml_id ";
            $sql.="LEFT JOIN aaa_ogr.ax_buchungsblattbezirk z ON z.bezirk = b.bezirk ";
            $sql.="WHERE b.endet IS NULL AND s.endet IS NULL AND z.endet IS NULL AND s.gml_id = $1";
            $sql.="ORDER BY b.land, b.bezirk, b.buchungsblattnummermitbuchstabenerweiterung;";
			$v=array($gml_bsan);
			$fbres=pg_prepare("", $sql);
			$fbres=pg_execute("", $v);
			//$fbres=pg_query($con,$sql);
			if (!$fbres) {
				echo "<p class='err'>Fehler bei fiktivem Blatt.</p>\n";
				if ($debug > 2) {echo "<p class='dbg'>SQL=<br>".$sql."</p>";}			
			}
			$b=0;
			while($fbrow = pg_fetch_array($fbres)) { // genau 1
				$fbgml   = $fbrow["gml_id"];
				$fbland  = $fbrow["land"];
				$fbbez   = $fbrow["bezirk"];
				$fbblatt = $fbrow["blatt"];
				$fbbart  = blattart($fbrow["blattart"]);
				$beznam	= $fbrow["beznam"];
				$b++;
			}
			if ($b != 1) {
				echo "<p class='err'>Anzahl fiktive Blätter zu anderer Buchungstelle = ".$b."</p>";
			}

			// G r u n d b u c h d a t e n  zur  a n d e r e n  Buchungsstelle
			echo "\n<tr>";
				echo"\n\t<td>";
					if($bvnr == $altbvnr) {	// gleiches Grundstueck
						echo "&nbsp;"; // Anzeige unterdruecken
					} else {
						echo "<a name='bvnr".$lfdnr."'></a>"; // Sprungmarke
						echo "<span title='Bestandsverzeichnisnummer (laufende Nummer)' class='wichtig'>".$lfdnr."</span>"; // Sp.1 Erbbau BVNR
						if ($idanzeige) {linkgml($gkz, $gml_bs, "Buchungsstelle");}
						$altbvnr = $bvnr; // Gruppenwechsel merken
					}
				echo "</td>";
				echo "\n\t<td class='ndien'>"; // Sp.2 Buchung
					if ($showkey) {
						echo "<span title='Buchungsartschlüssel' class='key'>".$row["buchungsart"]."</span> ";
					}
				echo $ba." an</td>";
				echo "\n\t<td class='ndien' title='Anteil'>".$anteil."</td>"; // Sp.3 Anteil
				echo "\n\t<td class='ndien'>"; // Sp.4 Gemarkg. hier Bezirk
					if ($showkey) {
						echo "<span title='Grundbuchbezirksschlüssel' class='key'>13".$fbbez."</span> ";
					}
					echo "<span title='Grundbuchbezirk'>".$beznam."</span> ";
				echo "</td>"; // Sp.4 hier Bezirk
				echo "\n\t<td class='ndien'>"; // Sp. 5 Blatt
					echo "<span title='Grundbuchblattnummer'>".ltrim($fbblatt, "0")."</span> ";
					if ($idanzeige) {
						linkgml($gkz, $fbgml, "Buchungsblatt");
					}
				echo "</td>";
				echo "\n\t<td class='ndien'>"; // BVNR
					echo "<span title='Bestandsverzeichnisnummer fiktiv (laufende Nummer)'>".$lfdnran."</span> ";
					if ($idanzeige) {
						linkgml($gkz, $gml_bsan, "Buchungsstelle");
					}

				echo "</td>"; 
				echo "\n\t<td class='ndien'>"; // Sp.7 Buchungsart
					if ($showkey) {
						echo "<span title='Buchungsartschlüssel' class='key'>".$rowan["buchungsart"]."</span> ";
					}
					echo "<span title='Buchungsart'>".$baan." </span>";
				echo "</td>";
				echo "\n\t<td>";  // Sp.8 Link ("an" oder "zu" ?)
					echo "<p class='nwlink'>".$rowan["beziehungsart"];
					echo " <a href='alkisbestnw.php?gkz=".$gkz."&amp;gmlid=".$fbgml;
						if ($idanzeige) {echo "&amp;id=j";}
						if ($showkey)   {echo "&amp;showkey=j";}
						echo "#bvnr".$lfdnran; // Sprungmarke auf der Seite
						echo "' title='Bestandsnachweis des dienenden Blattes'>";
						echo $fbbart;
					echo " <img src='ico/GBBlatt_link.ico' width='16' height='16' alt=''></a></p>";
				echo "</td>";
			echo "\n</tr>"; 

			// F l u r s t u e c k s d a t e n  zur  a n d e r e n  Buchungsstelle
			// Buchungsart wird nur in erster Zeile ausgegeben, hier leer
		   $aj = bnw_fsdaten($con, "", $gml_bsan, "", $anteil, false); // return = Anzahl der FS
		   		   
			// +++ Gibt es ueberhaupt Sondereigentum beim fiktiven Blatt??
			if ($rowan["nrap"] != "") {
				echo "\n<tr>";
					echo "\n\t<td class='sond' colspan=8>Nr. im Aufteilungsplan: ".$rowan["nrap"]."</td>";
				echo "\n</tr>";
			}
			if ($rowan["sond"] != "") {
				echo "\n<tr>";
					echo "\n\t<td class='sond' colspan=8>Verbunden mit dem Sondereigentum an: ".$rowan["sond"]."</td>";
				echo "\n</tr>";
			}
			$a++;
		}
		if ($a == 0) {
			echo "\n<tr>";
				echo "\n\t<td><span class='wichtig'>".$lfdnr."</span>";
				if ($idanzeige) {
					linkgml($gkz, $gml_bs, "Buchungsstelle");
				}
				echo "</td>";
				echo "\n\t<td colspan=7>";
					echo "<p class='warn'>Flurstücke zur BVNR ".$lfdnr." nicht im Datenbestand.</p>";
				echo "</td>";
			echo "\n</tr>";
		}
	}
	$i++; 
	if ($row["nrap"] != "") { // Nummer im Aufteilungsplan
		echo "\n<tr>";
			echo "\n\t<td class='nrap' colspan=8>Nummer <span class='wichtig'>".$row["nrap"]."</span> im Aufteilungsplan.</td>";
		echo "\n</tr>";
	}
	if ($row["sond"] != "") { // Sondereigentumsbeschreibung
		echo "\n<tr>";
			echo "\n\t<td class='sond' colspan=8>Verbunden mit dem Sondereigentum an: ".$row["sond"]."</td>";
		echo "\n</tr>";
	}
} // Ende Buchungsstelle
echo "\n</table>";

if ($i == 0) {
	echo "\n<p class='err'>Keine Buchung gefunden.</p>\n";
	linkgml($gkz, $gmlid, "Buchungsblatt");
}
	// b e r e c h t i g t e  Grundbuecher (Buchungsblatt) 
	// mit Recht "an"/"zu" dem aktuellen fiktiven GB

	// bf              vf          sf       vs   sb                 vb            bb
	// Blatt   <istBestandteilVon< Stelle  <an<  Stelle      >istBestandteilVon>  Blatt
	// Fiktiv                      Fiktiv  <zu<  Berechtigt                       Berechtigt
	$sql ="SELECT bb.gml_id, bb.land, bb.bezirk, bb.buchungsblattnummermitbuchstabenerweiterung AS blatt, bb.blattart, ";
    $sql.="CASE WHEN sf.gml_id = ANY(sb.an) THEN 'an' WHEN sf.gml_id = ANY(sb.zu) THEN 'zu' WHEN sf.gml_id = ANY(sb.durch) THEN 'durch' END AS beziehungsart, ";
    $sql.="sf.gml_id AS gml_s, sf.laufendenummer AS lfdnr, sf.buchungsart, ba.beschreibung AS bart, ";
    $sql.="bz.bezeichnung AS beznam, ag.bezeichnung, ag.stelle, ag.stellenart ";
    $sql.="FROM aaa_ogr.ax_buchungsstelle sb ";
    $sql.="JOIN aaa_ogr.ax_buchungsstelle sf ON sf.gml_id = ANY(sb.an) OR sf.gml_id = ANY(sb.zu) OR sf.gml_id = ANY(sb.durch) ";
    $sql.="JOIN aaa_ogr.ax_buchungsblatt bb ON sb.istbestandteilvon = bb.gml_id ";
    $sql.="LEFT JOIN aaa_ogr.ax_buchungsblattbezirk bz ON bz.bezirk = bb.bezirk ";
    $sql.="LEFT JOIN aaa_ogr.ax_dienststelle ag ON bz.schluessel_land = ag.land AND bz.stelle = ag.stelle ";
    $sql.="LEFT JOIN aaa_ogr.ax_buchungsart_buchungsstelle ba ON ba.wert = sb.buchungsart ";
    $sql.="WHERE sb.endet IS NULL AND sf.endet IS NULL AND bb.endet IS NULL AND bz.endet IS NULL AND sf.istbestandteilvon = $1 ";
    $sql.="ORDER BY bb.land, bb.bezirk, bb.buchungsblattnummermitbuchstabenerweiterung;";
	$v = array($gmlid);
	$resb = pg_prepare("", $sql);
	$resb = pg_execute("", $v);
	if (!$resb) {
		echo "<p class='err'>Fehler bei 'andere Berechtigte Blätter:'<br>".$sql."</p>\n";
		if ($debug > 2) {echo "<p class='dbg'>SQL=<br>".$sql."<br>$1 = gml_id = '".$gmlid."'</p>";}
	}
	$b=0; // count: Blaetter
	while($rowb = pg_fetch_array($resb)) {
		if ($b == 0) { // Ueberschrift und Tabelle nur ausgeben, wenn etwas gefunden wurde
			echo "\n<h3><img src='ico/Grundbuch_zu.ico' width='16' height='16' alt=''> Berechtigte Grundbücher</h3>\n";
			echo "\n<table class='outer'>";
			echo "\n<tr>"; // Tabelle Kopf
				echo "\n\t<td class='head'>Amt</td>";
				echo "\n\t<td class='head'>Bezirk</td>";
				echo "\n\t<td class='head'>Blatt</td>";
				echo "\n\t<td class='head'>BVNR</td>"; // Neu
				echo "\n\t<td class='head'>Buchungsart</td>"; // Neu
				echo "\n\t<td class='head nwlink noprint'>weitere Auskunft</td>";
			echo "\n</tr>";
		}
		$gml_b=$rowb["gml_id"];		// id des berechtigten Blattes
		$gml_s=$rowb["gml_s"];		// id der berechtigten Buchungsstelle
		$blart=$rowb["blattart"];
		$buch=$rowb["buchungsart"]; // Buchungsart Stelle berechtigt
		$bart=$rowb["bart"];			// Buchungsart entschluesselt
		$lfdnr=$rowb["lfdnr"];

		echo "\n<tr>";
			echo "\n\t<td>"; // Amtsgericht
				if ($showkey) {
					echo "<span class='key' title='Grundbuchamtsschlüssel'>".$rowb["stelle"]."</span> ";
				}
				echo "<span title='Grundbuchamt'>".dienststellenart($rowb["stellenart"])." ".$rowb["bezeichnung"]."</span>";
			echo "</td>";
			echo "\n\t<td>";
				if ($showkey) {
					echo "<span class='key' title='Grundbuchbezirksschlüssel'>13".$rowb["bezirk"]."</span> ";
				}
				echo "<span title='Grundbuchbezirk'>".$rowb["beznam"]."</span>";
			echo "</td>";
			echo "\n\t<td><span class='wichtig' title='Grundbuchblattnummer'>".ltrim($rowb["blatt"], "0")."</span>";
				if ($idanzeige) {linkgml($gkz, $gml_b, "Buchungsblatt");}
			echo "</td>";
			echo "\n\t<td><span title='Bestandsverzeichnisnummer (laufende Nummer)'>".$lfdnr."</span>";
				if ($idanzeige) {linkgml($gkz, $gml_s, "Buchungsstelle");}
			echo "</td>";
			echo "\n\t<td>";
				if ($showkey) {
					echo "<span class='key' title='Buchungsartschlüssel'>".$buch."</span> ";
				}
				echo "<span title='Buchungsart'>".$bart."</span> ";
			echo "</td>";
			echo "\n\t<td>";
				echo "\n\t\t<p class='nwlink'>";
			//	echo $rowb["beziehungsart"]." "; // "an"/"zu" ?
				echo "\n\t\t\t<a href='alkisbestnw.php?gkz=".$gkz."&amp;gmlid=".$gml_b."#bvnr".$lfdnr;
					if ($idanzeige) {echo "&amp;id=j";}
					if ($showkey)   {echo "&amp;showkey=j";}
					// echo "' title='Bestandsnachweis des berechtigten Blattes ".$rowb["beziehungsart"]." ".$blattart."'>";
					echo "' title='Bestandsnachweis des herrschenden Blattes'>";
					echo blattart($blart);
					echo " \n\t\t\t<img src='ico/GBBlatt_link.ico' width='16' height='16' alt=''></a>";
				echo "\n\t\t</p>";
			echo "</td>";
		echo "\n</tr>";
		$b++;
	}
	if ($b == 0) {
		if ($blattkey > 2000 ) { // Warnung nicht bei Blatt 1000 und Katasterblatt 2000
			echo "<p class='err'>Keine berechtigten Blätter zu ".$blattart." (".$blattkey.") gefunden.</p>";
		}
	} else {
		echo "\n</table>";
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