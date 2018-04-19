<?php
function lage_zum_fs($gmlid) {
	// Zu einem Flurstück die Lagebezeichnungen (mit Hausnummer) so aufbereiten, 
	// dass ggf. mehrere Lagebezeichnungen in eine Zelle der Tabelle passen.
	$sql ="SELECT DISTINCT k.bezeichnung, l.hausnummer ";
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
		echo "Fehler bei Lagebezeichnung \n";
		//echo $sql."\n";
	}
	$j=0;
	$lagehsnr="";
	while($row = pg_fetch_array($res)) {
		if ($j > 0) {$lagehsnr.=", ";}
		$sneu=$row["bezeichnung"];
		if ($sneu == $salt) { // gleiche Str.
			$lagehsnr.=$row["hausnummer"]; // HsNr dran haengen
		} else { // Name UND HsNr dranhaengen
			$lagehsnr.=$sneu." ".$row["hausnummer"];
		}
		$salt=$sneu; // Name f. naechste Runde
		$j++;
	}
	pg_free_result($res);
	return($lagehsnr);
}

$cntget = extract($_GET); // Parameter aus URL lesen
header('Content-type: application/octet-stream');
header('Content-Disposition: attachment; filename="alkis_'.$tabtyp.'_'.$gmlid.'.csv"');
require_once("alkis_conf_location.php");
include("alkisfkt.php");

// CSV-Ausgabe: Kopfzeile mit Feldnamen
echo "Flurstückskennzeichen;Gemarkungsschlüssel;Gemarkungsname;Flurnummer;Flurstücksnummer;Buchfläche in m²;Adresse(n);Grundbuchbezirksschlüssel;Grundbuchbezirk;Grundbuchblattnummer;Bestandsverzeichnisnummer (laufende Nummer);Anteil am Flurstück;Buchungsart;Namensnummer;Anteil der Person;Rechtsgemeinschaft;Person;Geburtsdatum;Anschrift;Anteil (berechnet)";

// Datenbank-Verbindung
$con = pg_connect("host=".$dbhost." port=" .$dbport." dbname=".$dbname." user=".$dbuser." password=".$dbpass);#." sslmode=".$sslmode);
if (!$con) {
	exit("Fehler beim Verbinden der DB");
}
pg_set_client_encoding($con, LATIN1); // Für Excel kein UTF8 ausgeben

// Der Parameter "Tabellentyp" bestimmt den Namen des Filter-Feldes aus dem View "exp_csv".
switch ($tabtyp) { // zulaessige Werte fuer &tabtyp=
	case 'flurstueck': $filter = "fsgml"; break; // ax_flurstueck.gml_id
	case 'grundbuch':  $filter = "gbgml"; break; // ax_buchungsblatt.gml_id
	case 'person':     $filter = "psgml"; break; // ax_person.gml_id
	case 'strasse':    $filter = "stgml"; break; // ax_lagebezeichnungkatalogeintrag.gml_id = Straße-GML-ID
	default: exit("Falscher Parameter '".$tabtyp."'"); break;
}

// Daten aus gespeichertem View, zusaetzlich Filter: "feld"='wert' mitgeben
if ($tabtyp == 'strasse') { // Sonderversion
	$sql="SELECT * FROM prozessiert.export_strasse_auskunft WHERE ".$filter." = $1 ";
} else {
	$sql="SELECT * FROM prozessiert.export_auskunft WHERE ".$filter." = $1 ";
}

$v=array($gmlid);
$res=pg_prepare("", $sql);
$res=pg_execute("", $v);
if (!$res) {exit("Fehler bei Datenbankabfrage");}
$i=1; // Kopfzeile zählt mit
$fsalt='';

// Datenfelder auslesen
while($row = pg_fetch_array($res)) {
	$i++; // Zeile der Tabelle
	$rechnen=true; // Formel in letzte Spalte?

	// Flurstueck
	$fsgml=$row["fsgml"];
	$gmkgnr=$row["gemarkungsnummer"];
	$gemkname=$row["gemarkung"]; 
	$flurnummer=$row["flurnummer"];
	$flstnummer=$row["zaehler"];
	$nenner=$row["nenner"];
    $fs_kennz=substr($row["fs_kennz"],0,2).$gmkgnr."-".str_pad($flurnummer,3,"0",STR_PAD_LEFT)."-".str_pad($flstnummer,5,"0",STR_PAD_LEFT)."/".str_pad($nenner,3,"0",STR_PAD_LEFT);
	if ($nenner > 0) {$flstnummer.="/".$nenner;} // BruchNr
	$fs_flae=$row["fs_flae"]; // amtliche Fl. aus DB-Feld

	// Grundbuch (Blatt)
	$gb_bezirk=$row["gb_bezirk"]; // Nummer des Bezirks
    $gb_beznam=$row["beznam"];    // Name des Bezirks
	$gb_blatt=$row["gb_blatt"];

	// Buchungsstelle (Grundstueck)
	$bu_lfd=$row["bu_lfd"]; // BVNR
	$bu_ant=$row["bu_ant"]; // '=zaehler/nenner' oder NULL
	$bu_key=$row["buchungsart"]; // Schlüssel
	$bu_art=$row["bu_art"]; // entschlüsselt (Umlaute in ANSI!)
	if($bu_ant == '') { // Keine Bruch-Zahl
		$bu_ant = '1'; // "voller Anteil" (Faktor 1)
	} else {
		$bu_ant=str_replace(".", ",", $bu_ant); // Dezimalkomma statt -punkt.		
	}

	// Namensnummer
	$nam_lfd="'".kurz_namnr($row["nam_lfd"])."'"; // In Hochkomma, wird sonst wie Datum dargestellt.
	$nam_ant=$row["nam_ant"];
	$nam_adr=$row["nam_adr"]; // Art der Rechtsgemeischaft (Schlüssel)

	if ($nam_adr == '') {     // keine Rechtsgemeinschaft
		$rechtsg='';
		if ($nam_ant == '') { // und kein Bruch-Anteil
			$nam_ant=1; // dann ganzer Anteil
		}
	} else {
		$rechnen=false; // bei Rechtsgemeinschaft die Anteile manuell interpretieren
		if ($nam_adr == 9999) { // sonstiges
			$rechtsg=$row["nam_bes"]; // Beschrieb der Rechtsgemeinschaft
		} else {
			$rechtsg=rechtsgemeinschaft($nam_adr); // Entschlüsseln
		}
	}

	// Person
	$vnam=$row["vorname"];
	$nana=$row["nachnameoderfirma"];
	$namteil=$row["namensbestandteil"];
	$name=anrede($row["anrede"]);
	if ($name != "") {$name.=" ";} // Trenner
	if ($namteil != "") {$name.=$namteil." ";} // von und zu
	$name.=$nana;
	if ($vnam != "") {$name.=", ".$vnam;} // Vorname nach hinten
    if ($row["geburtsdatum"] != '')
        $gebdat=strftime('%d.%m.%Y', strtotime($row["geburtsdatum"]));
    else
        $gebdat='';

	// Adresse der Person (Eigentuemer))
	$ort=$row["ort"];
	if ($ort == "") {
		$adresse="";
	} else { 
		$adresse=$row["strasse"]." ".$row["hausnummer"].", ".$row["plz"]." ".$ort;
		$land=$row["land"]; // nur andere Länder anzeigen
		if (($land != "DEUTSCHLAND") and ($land != "")) {
			$adresse.=" (".$land.")";
		}
	}

	// Adressen (Lage) zum FS
	if($fsgml != $fsalt) { // nur bei geändertem Kennz.
		$lage=lage_zum_fs($fsgml); // die Lage neu ermitteln
		$fsalt=$fsgml;
	}

	// Den Ausgabe-Satz montieren aus Flurstücks-, Grundbuch- und Namens-Teil
	//      A             B           C             D               E               F            G
	$fsteil=$fs_kennz.";".$gmkgnr.";".$gemkname.";".$flurnummer.";".$flstnummer.";".$fs_flae.";".$lage.";";
	//      H              I              J             K           L           M
	$gbteil=$gb_bezirk.";".$gb_beznam.";".$gb_blatt.";".$bu_lfd.";".$bu_ant.";".$bu_art.";";
	//       N            O            P            Q         R           S
	$namteil=$nam_lfd.";".$nam_ant.";".$rechtsg.";".$name.";".$gebdat.";".$adresse;

	// Anteile "GB am FS" und "Pers am GB" verrechnen
	if ($rechnen) { // beide Anteile verwertbar
		$formelteil=";=L".$i."*O".$i; // Spalte T
	} else {
		$formelteil=';';
	}

	// Ausgabe in CSV-Datei
	echo "\n".$fsteil.$gbteil.$namteil.$formelteil;
}
pg_free_result($res);
if ($i == 1) {exit ("Kein Treffer fuer gml_id=".$gmlid);} // nur Kopfzeile
pg_close($con);
exit(0);
?>