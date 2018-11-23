<?php
function footer($gmlid, $link, $append) {
	// Einen Seitenfuss ausgeben.
	// Den URL-Parameter "&id=j/n" und "&showkey=j/n" in allen Kombinationen umschalten lassen.
	// Die Parameter &gkz= und &gmlid= kommen in allen Modulen einheitlich vor

	// Der Parameter $append wird angehaengt wenn gefuellt
	//  Anwendung: &ltyp=m/p/o bei Lage
	global $gkz, $idumschalter, $idanzeige, $showkey, $hilfeurl;

	$customer=$_SESSION["mb_user_description"];
	$username=$_SESSION["mb_user_name"];
	echo "\n<div class='confbereich noprint'>";
	echo "\n<table class='outer'>\n<tr>";

	// Spalte 1: Info Benutzerkennung
    if (substr($username, 0, 4) != 'anon') 
        echo "\n\t<td class='foot footl'>angemeldet als: <span class='italic' title='Benutzerkennung'>".$username."</span></td>";
	// Spalte 2: Umschalter
	echo "\n\t<td class='foot footr'>";
		$mylink ="\n\t\t<a href='".$link."gkz=".$gkz."&amp;gmlid=".$gmlid.$append;
		if ($showkey) {$mykey = "&amp;showkey=j";} else {$mykey = "&amp;showkey=n";}
		if ($idumschalter) { // fuer Entwicklung ODER Test
			if ($idanzeige) {$myid = "&amp;id=j";} else {$myid = "&amp;id=n";}

			// Umschalter nur ausgeben, wenn in conf gesetzt
			if ($idanzeige) { // Umschalten ID ein/aus
				echo $mylink.$mykey."&amp;id=n' title='ohne Verfolgung der ALKIS-Beziehungen'>";
				echo "<img src='ico/Beziehung_link.ico' width='16' height='16' alt=''> ID aus</a>";
			} else {
				echo $mylink.$mykey."&amp;id=j' title='mit Verfolgung der ALKIS-Beziehungen'>";
				echo "<img src='ico/Beziehung_link.ico' width='16' height='16' alt=''> ID ein</a>";
			}
			echo " | ";
		} else { // keinen ID-Umschalter
			$myid = "";
		}

		if ($showkey) { // // Umschalten Schlüssel ein/aus
			echo $mylink.$myid."&amp;showkey=n' title='Verschlüsselungen nicht anzeigen'>Schlüssel ausblenden</a>";
		} else {
			echo $mylink.$myid."&amp;showkey=j' title='Verschlüsselungen anzeigen'>Schlüssel einblenden</a>";
		}
	echo "\n\t</td>";

	// Spalte 3
	echo "\n</tr>\n</table>\n</div>\n";
	return 0;
}

function linkgml($gkz, $gml, $typ)  {
	// Einen Link zur Verfolgung der Beziehungen mit dem Modul alkisrelationen.php
	$kurzid=substr($gml, 12); // ID in Anzeige kuerzen (4 Zeichen), der Anfang ist immer gleich
	echo "\n\t\t<a target='_blank' title='ID ".$typ."' class='gmlid noprint' ";
	echo "href='alkisrelationen.php?gkz=".$gkz."&amp;gmlid=".$gml."&amp;otyp=".$typ."'>";
	echo "<img src='ico/Beziehung_link.ico' width='16' height='16' alt=''>".$kurzid."</a>";
	return 0;
}

function kurz_namnr($lang) {
	// Namensnummer kuerzen. Nicht benoetigte Stufen der Dezimalklassifikation abschneiden
	$kurz=str_replace(".00","",$lang); // leere Stufen (nur am Ende)
	$kurz=str_replace("0000","",$kurz); // ganz leer (am Anfang)
	$kurz=ltrim($kurz, "0"); // fuehrende Nullen am Anfang
	$kurz=str_replace(".0",".",$kurz); // fuehrende Null jeder Stufe
	return $kurz;
}

function bnw_fsdaten($con, $lfdnr, $gml_bs, $ba, $anteil, $bvnraus) {
/*	Bestandsnachweis - Flurstuecksdaten
	Die Tabellenzeilen mit den Flurstuecksdaten zu einer Buchungsstelle im Bestandsnachweis ausgeben.
	Die Funktion wird je einmal aufgerufen für die Buchungen direkt auf dem GB (Normalfall).
	Weiterere Aufrufe ggf. bei Erbbaurecht für die mit "an" verknuepften Buchungsstellen.
	Table-Tag und Kopfzeile im aufrufenden Programm. 
*/
	global $gkz, $idanzeige, $showkey;
	// F L U R S T U E C K
	$sql="SELECT g.gemarkungsnummer, g.bezeichnung, ";
	$sql.="f.gml_id, f.flurnummer, f.zaehler, f.nenner, f.gemarkung_land AS land, f.regierungsbezirk, f.kreis, f.gemeinde, f.amtlicheflaeche, f.realflaeche AS fsgeomflae ";
	$sql.="FROM aaa_ogr.ax_flurstueck f ";
    $sql.="LEFT JOIN aaa_ogr.ax_gemarkung g ON f.gemarkung_land=g.schluessel_land AND f.gemarkungsnummer = g.gemarkungsnummer ";
    $sql.="WHERE g.endet IS NULL AND f.endet IS NULL AND f.istgebucht = $1";
	$sql.="ORDER BY f.gemarkungsnummer, f.flurnummer, f.zaehler::int, f.nenner::int;";
	$v = array($gml_bs);
	$resf = pg_prepare("", $sql);
	$resf = pg_execute("", $v);

	if (!$resf) {echo "<p class='err'>Fehler bei Flurstück</p>\n";}

	if($bvnraus) { // nur bei direkten Buchungen die lfdNr ausgeben
		$bvnr=str_pad($lfdnr, 4, "0", STR_PAD_LEFT);
	}
	$altlfdnr="";
	$j=0;
	while($rowf = pg_fetch_array($resf)) {
		$flur=$rowf["flurnummer"];

/*		$fskenn=str_pad($rowf["zaehler"], 5, "0", STR_PAD_LEFT);
		if ($rowf["nenner"] != "") { // Bruchnummer
			$fskenn.="/".str_pad($rowf["nenner"], 3, "0", STR_PAD_LEFT);
		} */

		// ohne fuehrende Nullen?
		$fskenn=$rowf["zaehler"];
		if ($rowf["nenner"] != "") { // Bruchnummer
			$fskenn.="/".$rowf["nenner"];
		}

		$fsbuchflae=$rowf["amtlicheflaeche"]; // amtliche Fl. aus DB-Feld
        $fsgeomflae=$rowf["fsgeomflae"]; // aus Geometrie ermittelte Fläche
        $fsbuchflaed=number_format($fsbuchflae,0,",",".") . " m&#178;"; // Display-Format dazu
        $fsgeomflaed=number_format($fsgeomflae,0,",",".") . " m&#178;";

		echo "\n<tr>"; // eine Zeile je Flurstueck
			// Sp. 1-3 der Tab. aus Buchungsstelle, nicht aus FS
			if($lfdnr == $altlfdnr) {	// gleiches Grundstueck
				echo "\n\t<td>&nbsp;</td>";
				echo "\n\t<td>&nbsp;</td>";
				echo "\n\t<td>&nbsp;</td>";
			} else {
				echo "\n\t<td class='right'>";
					echo "<a name='bvnr".$lfdnr."'></a>"; // Sprungmarke
					echo "<span title='Bestandsverzeichnisnummer (laufende Nummer)'>".$lfdnr."</span>";  // BVNR
					if ($idanzeige) {linkgml($gkz, $gml_bs, "Buchungsstelle");}
				echo "</td>";

				echo "\n\t<td class='right'>"; // Buchungsart 
					//	if ($showkey) {echo "<span class='key'>".$???."</span>&nbsp;";} // Schluessel
					echo "<span title='Buchungsart'>".$ba."</span>"; // entschluesselt
				echo "</td>"; 
				echo "\n\t<td class='right'>&nbsp;</td>"; // Anteil
				$altlfdnr=$lfdnr;
			}
			//Sp. 4-7 aus Flurstueck
			echo "\n\t<td class='right'>";
			if ($showkey) {
				echo "<span class='key' title='Gemarkungsschlüssel'>".$rowf["land"].$rowf["gemarkungsnummer"]."</span> ";
			}
			echo "<span title='Gemarkungsname'>".$rowf["bezeichnung"]."</span></td>";
			echo "\n\t<td class='right'><span title='Flurnummer'>".$flur."</span></td>";
			echo "\n\t<td class='right'><span title='Flurstücksnummer in der Notation: Zähler/Nenner' class='wichtig'>".$fskenn."</span>";
				if ($idanzeige) {linkgml($gkz, $rowf["gml_id"], "Flurstück");}
			echo "</td>";
			echo "\n\t<td class='fla' title='geometrisch berechnet: ".$fsgeomflaed."' class='flae'>".$fsbuchflaed."</td>";

			echo "\n\t<td><p class='nwlink noprint'>";
				echo "<a href='alkisfsnw.php?gkz=".$gkz."&amp;gmlid=".$rowf["gml_id"];
					if ($idanzeige) {echo "&amp;id=j";}
					if ($showkey)   {echo "&amp;showkey=j";}
					echo "' title='Flurstücksnachweis'>Flurstück ";
					echo "<img src='ico/Flurstueck_Link.ico' width='16' height='16' alt=''>";
				echo "</a>";
			echo "</p></td>";
		echo "\n</tr>";

		$j++;
	} // Ende Flurstueck
	pg_free_result($resf);
	return $j;
}

// **  Functions  zum   E n t s c h l u e s s e l n  **

// Entschluesslung ax_person.anrede
function anrede($key) {
	switch ($key) {
		case 1000: $wert = "Frau"; break;
		case 2000: $wert = "Herr"; break;
		case 3000: $wert = "Firma"; break;
		default:   $wert = ""; break;
	}
	return $wert;
}

// Entschluesslung AX_Namensnummer.artDerRechtsgemeinschaft
function rechtsgemeinschaft($key) {
	switch ($key) {
		case 1000: $wert = "Erbengemeinschaft"; break;
		case 2000: $wert = "Gütergemeinschaft"; break;
		case 3000: $wert = "BGB-Gesellschaft"; break;
		case 9999: $wert = "Sonstiges"; break;	// dann: beschriebDerRechtsgemeinschaft
		default:   $wert = ""; break;
	}
	return $wert;
}

// Entschluesslung ax_buchungsblatt.blattart
function blattart($key) {
	switch ($key) {
		case 1000: $wert = "Grundbuchblatt"; break;
		// Ein Grundbuchblatt ist ein Buchungsblatt, das die Buchung im Grundbuch enthält.
		case 2000: $wert = "Katasterblatt"; break;
		// Ein Katasterblatt ist ein Buchungsblatt, das die Buchung im Liegenschaftskataster enthält.
		case 3000: $wert = "Pseudoblatt"; break;
		// Ein Pseudoblatt ist ein Buchungsblatt, das die Buchung, die bereits vor Eintrag im Grundbuch Rechtskraft erlangt hat, enthält 
		// (z.B. Übernahme von Flurbereinigungsverfahren, Umlegungsverfahren).
		case 5000: $wert = "Fiktives Blatt"; break;
		// Das fiktive Blatt enthält die aufgeteilten Grundstücke und Rechte als Ganzes. 
		// Es bildet um die Miteigentumsanteile eine fachliche Klammer.
		default: $wert = "** Unbekannter Wert '".$key."'"; break;
	}
	return $wert;
}
// Entschluesslung ax_dienststelle.stellenart
function dienststellenart($key) {
	switch ($key) {
		case 1000: $wert = "Grundbuchamt"; break;
		case 1100: $wert = "Katasteramt"; break;
		case 1200: $wert = "Finanzamt"; break;
		case 1300: $wert = "Flurbereinigungsbehörde"; break;
		case 1400: $wert = "Forstamt"; break;
		case 1500: $wert = "Wasserwirtschaftsamt"; break;
		case 1600: $wert = "Straßenbauamt"; break;
		case 1700: $wert = "Gemeindeamt"; break;
		case 1900: $wert = "Kreis- oder Stadtverwaltung"; break;
		case 2000: $wert = "Wasser- und Bodenverband"; break;
		case 2100: $wert = "Umlegungsstelle"; break;
		case 2200: $wert = "Landesvermessungsverwaltung"; break;
		case 2300: $wert = "öbVI"; break;
		case 2400: $wert = "Bundeseisenbahnvermögen"; break;
		case 2500: $wert = "Landwirtschaftskammer"; break;
		default: $wert = ""; break;
	}
	return $wert;
}
?>