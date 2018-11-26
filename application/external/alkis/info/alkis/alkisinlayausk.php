<?php
//session_start();
$cntget = extract($_GET);
require_once("alkis_conf_location.php");
$keys = isset($_GET["showkey"]) ? $_GET["showkey"] : "n";
if ($keys == "j") {$showkey=true;} else {$showkey=false;}
if ($auth == "mapbender") {require_once($mapbender);}
include("alkisfkt.php");
$gmlid = isset($_GET["gmlid"]) ? $_GET["gmlid"] : 0;
echo <<<END
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
	<meta name="author" content="b600352" >
	<meta http-equiv="cache-control" content="no-cache">
	<meta http-equiv="pragma" content="no-cache">
	<meta http-equiv="expires" content="0">
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<link rel="stylesheet" type="text/css" href="alkisauszug.css">
	<link rel="shortcut icon" type="image/x-icon" href="ico/Flurstueck.ico">
	<title>Auskunft</title>
	<script type="text/javascript">
	function imFenster(dieURL) {
		var link = encodeURI(dieURL);
		window.open(link,'','left=10,top=10,width=680,height=800,resizable=yes,menubar=no,toolbar=no,location=no,status=no,scrollbars=yes');
	}
	</script>
</head>
<body>
END;
$a = $dbhost;
$con = pg_connect("host=".$dbhost." port=".$dbport." dbname=".$dbname." user=".$dbuser." password=".$dbpass);#." sslmode=".$sslmode);
if (!$con) {echo "<br>Fehler beim Verbinden der DB.\n<br>";}

// *** F L U R S T U E C K ***
$sql ="SELECT f.flurnummer, f.gemarkung_land AS land, f.flurstueckskennzeichen, f.zaehler, f.nenner, f.amtlicheflaeche, CASE WHEN round(f.realflaeche::numeric, 2)::text ~ '50$' AND round(f.realflaeche::numeric, 2) >= 1 THEN CASE WHEN (trunc(f.realflaeche)::int % 2) = 0 THEN trunc(f.realflaeche) ELSE round(round(f.realflaeche::numeric, 2)::numeric) END WHEN round(f.realflaeche::numeric, 2) < 1 THEN round(f.realflaeche::numeric, 2) ELSE round(f.realflaeche::numeric) END AS realflaeche_geodaetisch_gerundet, g.gemarkungsnummer, g.bezeichnung ";
$sql.="FROM aaa_ogr.ax_flurstueck f ";
$sql.="LEFT JOIN aaa_ogr.ax_gemarkung g ON f.gemarkungsnummer = g.gemarkungsnummer ";
$sql.="WHERE f.endet IS NULL AND g.endet IS NULL AND f.gml_id = $1;";
// Weiter joinen: g.stelle -> ax_dienststelle "Katasteramt"
$v = array($gmlid);
$res = pg_prepare("", $sql);
$res = pg_execute("", $v);
if (!$res) {
	echo "\n<p class='err'>Fehler bei Flurstuecksdaten.</p>\n";
	if ($debug > 2) {echo "<p class='err'>SQL=<br>".$sql."<br>$1 = gml_id = '".$gmlid."'</p>";}
}

if ($row = pg_fetch_array($res)) {
	$gemkname=htmlentities($row["bezeichnung"], ENT_QUOTES, "UTF-8");
    $flurstueckskennzeichen=$row["flurstueckskennzeichen"];
    $land=$row["land"];
	$gmkgnr=$row["gemarkungsnummer"];
	$flurnummer=$row["flurnummer"];
	$flstnummer=$row["zaehler"];
	$nenner=$row["nenner"];
	if ($nenner > 0) $flstnummer.="/".$nenner; // BruchNr
	$amtlicheflaeche=$row["amtlicheflaeche"]; // amtliche Fläche
	$amtlicheflaeched=($amtlicheflaeche < 1 ? rtrim(number_format($amtlicheflaeche,2,",","."),"0") : number_format($amtlicheflaeche,0,",",".")); // Display-Format dazu
	$realflaeche_geodaetisch_gerundet=$row["realflaeche_geodaetisch_gerundet"]; // geodätisch gerundeter Wert der realen Fläche
	$realflaeche_geodaetisch_gerundetd=($realflaeche_geodaetisch_gerundet < 1 ? rtrim(number_format($realflaeche_geodaetisch_gerundet,2,",","."),"0") : number_format($realflaeche_geodaetisch_gerundet,0,",",".")); // Display-Format dazu
} else {
	echo "<p class='err'>Kein Treffer fuer gml_id=".$gmlid."</p>";
}

echo "\n<h2><img src='ico/Flurstueck.ico' width='16' height='16' alt=''> Flurstück</h2>";

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
echo "\n\t<p class='nwlink'>weitere Auskunft:<br>";

// Flurstuecksnachweis (mit Eigentümer)
echo "\n\t\t<a href='javascript:imFenster(\"alkisfsnw.php?gkz=".$gkz."&amp;gmlid=".$gmlid."&amp;eig=j\")' ";
	echo "title='Flurstücksnachweis'>Flurstück&nbsp;";
	echo "<img src='ico/Flurstueck_Link.ico' width='16' height='16' alt=''>";
echo "</a><br>";

// Flurstückshistorie
echo "\n\t\t<a href='javascript:imFenster(\"alkisfshist.php?gkz=".$gkz."&amp;gmlid=".$gmlid."\")' ";
	echo "title='Flurstückshistorie'>Historie&nbsp;";
	echo "<img src='ico/Flurstueck_Historisch.ico' width='16' height='16' alt=''>";
echo "</a><br>";

// Gebaeude-NW zum FS
echo "\n\t\t<a href='javascript:imFenster(\"alkisgebaeudenw.php?gkz=".$gkz."&amp;gmlid=".$gmlid."\")' ";
	echo "title='Gebäudenachweis'>Gebäude&nbsp;";
	echo "<img src='ico/Haus.ico' width='16' height='16' alt=''>";
echo "</a>";

echo "\n\t</p>\n</td>";
pg_free_result($res);

// Lage MIT HausNr (Adresse)
$sql ="SELECT DISTINCT k.gml_id AS kgml, l.gml_id, k.bezeichnung, l.hausnummer ";
$sql.="FROM aaa_ogr.ax_flurstueck f ";
$sql.="JOIN aaa_ogr.ax_lagebezeichnungmithausnummer l ON l.gml_id = ANY(f.weistauf) ";
$sql.="LEFT JOIN aaa_ogr.ax_lagebezeichnungkatalogeintrag k ON l.lage = k.lage ";
$sql.="WHERE f.gml_id = $1 ";
$sql.="AND f.endet IS NULL AND l.endet IS NULL AND k.endet IS NULL ";
$sql.="ORDER BY k.bezeichnung, l.hausnummer;";
$v=array($gmlid);
$res=pg_prepare("", $sql);
$res=pg_execute("", $v);
if (!$res) {
	echo "<p class='err'>Fehler bei Lagebezeichnung mit Hausnummer.</p>";
	if ($debug > 2) {echo "<p class='err'>SQL=<br>".$sql."<br>$1 = gml_id = '".$gmlid."'</p>";}
}
$j=0;
$kgmlalt='';
while($row = pg_fetch_array($res)) {
	$sname = htmlentities($row["bezeichnung"], ENT_QUOTES, "UTF-8"); // Str.-Name
	echo "\n<tr>\n\t\n\t<td class='lr'>".$sname."&nbsp;".$row["hausnummer"]."</td>";
	echo "\n\t<td>\n\t\t<p class='nwlink noprint'>";
	$kgml=$row["kgml"]; // Wiederholung vermeiden
	if ($kgml != $kgmlalt) { // NEUE Strasse vor Lage
		$kgmlalt=$kgml; // Katalog GML-ID
		echo "\n\t\t\t<a title='Flurstücke an dieser Straße' ";
		echo "href='javascript:imFenster(\"alkisstrasse.php?gkz=".$gkz."&amp;gmlid=".$row["kgml"]."\")'>Straße ";
		echo "<img src='ico/Strassen.ico' width='16' height='16' alt='STRA'></a>";
	}
		echo "\n\t\t\t<a title='Flurstücke mit dieser Lagebezeichnung mit Hausnummer' ";
		echo "href='javascript:imFenster(\"alkislage.php?gkz=".$gkz."&amp;ltyp=m&amp;gmlid=".$row["gml_id"]."\")'>Lage ";
		echo "<img src='ico/Lage_mit_Haus.ico' width='16' height='16' alt='HAUS'></a>&nbsp;";
	echo "\n\t\t</p>\n\t</td>\n</tr>";
	$j++;
}
pg_free_result($res);
if ($j == 0) { // keine HsNr gefunden
	// Lage OHNE HausNr
	$sql ="SELECT DISTINCT k.gml_id AS kgml, l.gml_id, k.bezeichnung, l.unverschluesselt ";
    $sql.="FROM aaa_ogr.ax_flurstueck f ";
    $sql.="JOIN aaa_ogr.ax_lagebezeichnungohnehausnummer l ON l.gml_id = ANY(f.zeigtauf) ";
    $sql.="LEFT JOIN aaa_ogr.ax_lagebezeichnungkatalogeintrag k ON l.lage = k.lage ";
    $sql.="WHERE f.gml_id = $1 ";
    $sql.="AND f.endet IS NULL AND l.endet IS NULL AND k.endet IS NULL ";
    $sql.="ORDER BY k.bezeichnung, l.unverschluesselt;";
	$v=array($gmlid);
	$res=pg_prepare("", $sql);
	$res=pg_execute("", $v);
	if (!$res) {
		echo "<p class='err'>Fehler bei Lagebezeichnung</p>";
		if ($debug > 2) {echo "<p class='err'>SQL=<br>".$sql."<br>$1 = gml_id = '".$gmlid."'</p>";}
	}
	while($row = pg_fetch_array($res)) {
		$sname =htmlentities($row["bezeichnung"], ENT_QUOTES, "UTF-8"); // Str.-Name
		$gewann=htmlentities($row["unverschluesselt"], ENT_QUOTES, "UTF-8");
		echo "\n<tr>";
		if ($sname != "") { // Typ=Strasse
			echo "\n\t<td class='lr' title='Straße'>".$sname."&nbsp;</td>";
			$ico="Lage_an_Strasse.ico";
            $titel="Flurstücke mit dieser Lagebezeichnung ohne Hausnummer";
		} else {
			echo "\n\t<td class='lr' title='Gewann'>".$gewann."&nbsp;</td>";
			$ico="Lage_Gewanne.ico";
            $titel="Flurstücke mit dieser Lagebezeichnung Gewann";
		}
		echo "\n\t<td>\n\t\t<p class='nwlink noprint'>";
		$kgml=$row["kgml"]; // Wiederholung vermeiden
		if ($kgml != $kgmlalt) { // NEUE Strasse vor Lage-O
			$kgmlalt=$kgml; // Katalog GML-ID
			echo "\n\t\t\t<a title='Flurstücke an dieser Straße' ";
			echo "href='javascript:imFenster(\"alkisstrasse.php?gkz=".$gkz."&amp;gmlid=".$row["kgml"]."\")'>Straße ";
			echo "<img src='ico/Strassen.ico' width='16' height='16' alt='STRA'></a>";
		}
		echo "\n\t\t\t<a title='".$titel."' ";
		echo "href='javascript:imFenster(\"alkislage.php?gkz=".$gkz."&amp;ltyp=o&amp;gmlid=".$row["gml_id"]."\")'>Lage ";
		echo "<img src='ico/".$ico."' width='16' height='16' alt='OHNE'></a>&nbsp;";
		echo "\n\t\t</p>\n\t</td>\n</tr>";
	}
	pg_free_result($res);
}
echo "\n</table>\n";

// Flurstuecksflaeche
echo "\n<p class='fsd' title='amtliche Fläche (Buchfläche) des Flurstücks'>Fläche: <span title='geometrisch berechnet, reduziert und geodätisch gerundet: ".$realflaeche_geodaetisch_gerundetd." m²' class='flae'>".$amtlicheflaeched." m²</span></p>\n";

echo "\n<hr class='thick'>";

// *** G R U N D B U C H ***
echo "\n<h2><img src='ico/Grundbuch_zu.ico' width='16' height='16' alt=''> Grundbuch</h2>";
// ALKIS: FS --> bfs --> GS --> bsb --> GB.
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
				echo "\n\t<p class='nwlink'>weitere Auskunft:<br>";
                echo "\n\t\t<a href='javascript:imFenster(\"alkisbestnw.php?gkz=".$gkz."&amp;gmlid=".$rowg[0]."\")' ";
                echo "title='Bestandsnachweis'>";
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
		$n = eigentuemer($con, $gmlg, false, "imFenster"); // ohne Adresse
        if ($n == 0) {
            if ($blattkeyg == 1000) {
                echo "\n<p class='err'>Keine Eigentümer gefunden!</p>";
                linkgml($gkz, $gmlg, "Buchungsblatt");
            } else {
                echo "<hr style='height: 1px; color: #fff; background-color: #fff; border-top: 1px dotted #ffbbbb;'>";
                echo "\nBei fiktiven Blättern werden hier keine Namensnummern und Namen angezeigt!";
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
                echo "\n\t\t<a href='javascript:imFenster(\"alkisbestnw.php?gkz=".$gkz."&amp;gmlid=".$rowan["g_gml"]."\")' ";
                echo "title='Bestandsnachweis'>";
                echo $blattartan." <img src='ico/GBBlatt_link.ico' width='16' height='16' alt=''>";
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

?>
</body>
</html>