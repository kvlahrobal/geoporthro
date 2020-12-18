(function ($) {
    $.widget("mapbender.mbBaseSearchOne", {
        options: {
            timeoutDelay: 300,
            timeoutId: null,
            buffer: 1.0,
            dataSrs: 'EPSG:25833',
            spatialSearchSrs: 'EPSG:4326'
        },
        hilfetexte: {
            allgemein_eins: 'Die Suche startet automatisch während der Eingabe. Sie können Ihre Suche über folgende Arten von Eingaben gestalten:<br/><br/><ul class="hilfetexte-liste">',
            allgemein_zwei: 'Die Suche startet automatisch während der Eingabe, und zwar ab dem <i>zweiten</i> eingegebenen Zeichen. Sie können Ihre Suche über folgende Arten von Eingaben gestalten:<br/><br/><ul class="hilfetexte-liste">',
            allgemein_drei: 'Die Suche startet automatisch während der Eingabe, und zwar ab dem <i>dritten</i> eingegebenen Zeichen. Sie können Ihre Suche über folgende Arten von Eingaben gestalten:<br/><br/><ul class="hilfetexte-liste">',
            addr: '<li>→ Ortsteilname [Beispiele: <span>schmarl</span> oder <span>brinckmans</span>]</li><li>→ Straßenname [Beispiele: <span>wagner</span> oder <span>holbei</span>]</li><li>→ Adresse (Straße mit Hausnummer und eventuellem Hausnummernzusatz) [Beispiele: <span>riga 19</span> oder <span>löns 14a</span>]</li></ul><br/>Resultate können Ortsteile, Straßen, Adressen (Straßen mit Hausnummer und eventuellem Hausnummernzusatz) und historische Adressen (Straßen mit Hausnummer und eventuellem Hausnummernzusatz sowie Angabe des Datums, an dem die Adresse historisiert wurde) sein, jeweils gekennzeichnet durch ein vorangestelltes sprechendes Icon.',
            auftrag: '<li>→ Auftragsnummer im Georg.net-Format [Beispiele: <span>08K665</span> oder <span>15K0955</span>]</li><li>→ Auftragsnummer im „hybriden“ Format [Beispiele: <span>2008K665</span> oder <span>2015K0955</span>]</li><li>→ Auftragsnummer im ALKIS-/LAH-Format [Beispiele: <span>200800665</span> oder <span>201500955</span>]</li></ul><br/>Die Auftragsart ist jeweils gekennzeichnet durch ein vorangestelltes Icon, dessen Farbe die Auftragsart repräsentiert. Außerdem werden – sofern vorhanden – je Auftrag immer die verknüpften Dokumente gelistet.',
            baulasten: '<li>→ Bezeichnung [Beispiele: <span>51197</span> oder <span>.0029</span>]</li></ul>',
            eigen: '<li>→ Vorname [Beispiel: <span>jürg</span>]</li><li>→ Nachname [Beispiel: <span>schmi</span>]</li><li>→ Kombination aus Vor- und Nachname (Reihenfolge egal) [Beispiel: <span>schmi jürg</span>]</li><li>→ Bezeichnung (bei Firmen, Organisationen etc.) [Beispiel: <span>carit</span>]</li></ul>',
            flur: '<li>→ Gemarkungsschlüssel [Beispiel: <span>2218</span>]</li><li>→ Gemarkungsname [Beispiel: <span>kasseb</span>]</li><li>→ Flur als Kombination aus Gemarkungsschlüssel und Flurnummer [Beispiel: <span>2222 flur 3</span>]</li><li>→ Flur als Kombination aus Gemarkungsname und Flurnummer [Beispiel: <span>evershagen 3</span>]</li><li>→ Flurstück als Kombination aus Gemarkungsschlüssel oder Gemarkungsname und Flurnummer, Zähler (und Nenner) [Beispiele: <span>2232 1 461</span> oder <span>2232 1 160/2</span> oder <span>krummen 1 461</span> oder <span>krummen 1 160/2</span>]</li><li>→ Flurstück als Kombination aus Gemarkungsschlüssel oder Gemarkungsname und Zähler (und Nenner) [Beispiele: <span>2232 461</span> oder <span>2232 160/2</span> oder <span>krummen 461</span> oder <span>krummen 160/2</span>]</li><li>→ Flurstück mittels Zähler und Nenner [Beispiele: <span>160/2</span> oder <span>12/20</span>]</li><li>→ Flurstück mittels Zähler [Beispiele: <span>160</span> oder <span>12</span>]</li></ul><br/><b>Achtung –</b> Sonderfall Flurnummer ≥ 10:<br/>Hier muss der Flurnummer <i>immer</i> eine Null vorangestellt werden und es darf <i>kein</i> Leerzeichen zwischen Gemarkungsname/-schlüssel und Flurnummer stehen bzw. es <i>muss</i> das Wort <i>Flur</i> zwischen Gemarkungsname und Flurnummer stehen [Beispiele: <span style="font-family:monospace">2238014,5</span> oder <span style="font-family:monospace">heide014, 5</span> oder <span style="font-family:monospace">2238 flur 014 5</span>].<br/><br/>Resultate können Gemarkungen, Fluren, Flurstücke und historische Flurstücke (teilweise mit Angabe des Datums, an dem das Flurstücke historisiert wurde) sein, jeweils gekennzeichnet durch ein vorangestelltes sprechendes Icon.',
            grund: '<li>→ Grundbuchbezirksname [Beispiel: <span>rosto</span>]</li><li>→ Grundbuchbezirksnummer [Beispiel: <span>2250</span>]</li><li>→ Grundbuchblattnummer [Beispiel: <span>18305</span>]</li><li>→ Kombination aus Grundbuchbezirks- und Grundbuchblattnummer (Reihenfolge egal) [Beispiel: <span>2250 18305</span>]</li></ul>',
            risse_fst: '<li>→ Flurstück mittels Flurstücksnummer [Beispiele: <span>132219001000280182</span> oder <span>132222003000240000</span> oder <span>132230002002330003</span> oder <span>132230002003600000</span>]</li><li>→ Flurstück mittels Flurstückskennzeichen [Beispiele: <span>132219-001-00028/0182</span> oder <span>132222-003-00024</span> oder <span>132230-002-00233/0003</span> oder <span>132230-002-00360</span>]</li></ul><br/>Es werden je Flurstück immer alle diejenigen Risse gelistet, die das Flurstück räumlich schneiden.',
            schiffe: '<li>→ Bezeichnung des Schiffes [Beispiele: <span>jantje</span> oder <span>PHOEN</span>]</li><li>→ Typ des Schiffes [Beispiele: <span>kutter</span> oder <span>Yacht</span>]</li><li>→ vollständiges Baujahr des Schiffes [Beispiel: <span>1980</span>]</li><li>→ Kurzbezeichnung des Schiffsliegeplatzes [Beispiele: <span>WMD</span> oder <span>rsc</span>]</li><li>→ Langbezeichnung des Schiffsliegeplatzes [Beispiel: <span>warnemü</span>]</li></ul>'
        },
        line_style: {
            'strokeColor': '#cc00ff',
            'fillColor': '#cc00ff',
            'strokeWidth': '3',
            'fillOpacity': '0.1'
        },
        point_style: {
            'strokeColor': '#cc00ff',
            'fillColor': '#cc00ff',
            'strokeWidth': '3',
            'fillOpacity': '0.1',
            'pointRadius': '10'
        },
        geomFeature: null,
        foundedFeature: null,
        mbMap: null,
        alkisInfoDialog: null,
        mobile: null,
        _create: function () {
            var self = this;
            if (!Mapbender.checkTarget("Search", this.options.target)) {
                return;
            }
            if (!Mapbender.checkTarget("AlkisInfo", this.options.alkisinfo)) {
                return;
            }
            Mapbender.elementRegistry.onElementReady(this.options.target, $.proxy(self._setup, self));
            Mapbender.elementRegistry.onElementReady(this.options.alkisinfo, $.proxy(self._setupAlkisinfo, self));
            // falls mobile Oberfläche...
            if ($('#mobilePane').length) {
                mobile = true;
            }
             // ansonsten...
            else {
                mobile = false;
            }
        },
        /**
         * Initializes the wmc handler
         */
        _setup: function () {
            var self = this;
            this.mbMap = $('#' + this.options.target).data('mapbenderMbMap');
            this.dataSrsProj = Mapbender.Model.getProj(this.options.dataSrs);
            $('input', this.element).on('keyup', $.proxy(self._findOnKeyup, self));
            
            // falls mobile Oberfläche...
            if (mobile === true) {
                // Button zum Zurücksetzen umgestalten
                $('#removeResults', this.element).empty();
                $('#removeResults', this.element).removeClass('button');
                $('#removeResults', this.element).removeClass('critical');
                // Suchfenster schließen bei Klick auf Resultat
                $('#removeResults', this.element).on('click', $.proxy(self._resetSearch, self));
                $('#clear-search', this.element).on('click', $.proxy(self._clearSearch, self));
                $(document).on('click', ' .clickable', $.proxy(self._closeMobilePane, self));
                $(document).on('click', "#" + this.element.attr('id') + ' .document', $.proxy(self._zoom, self));
                $(document).on('click', "#" + this.element.attr('id') + ' .document > i', $.proxy(self._zoom, self));
                $(document).on('click', "#" + this.element.attr('id') + ' .document > small', $.proxy(self._zoom, self));
                $(document).on('click', "#" + this.element.attr('id') + ' div[data-page]', $.proxy(self._changePage, self));
                $(document).on('change', "#" + this.element.attr('id') + " select", $.proxy(self._changeSearchType, self));
                $(document).on('click', "#" + this.element.attr('id') + ' .eigen', $.proxy(self._showEigenInfo, self));
                $(document).on('click', "#" + this.element.attr('id') + ' .grund', $.proxy(self._showGrundInfo, self));
            }
             // ansonsten...
            else {
                $('#removeResults', this.element).on('click', $.proxy(self._resetSearch, self));
                $('#clear-search', this.element).on('click', $.proxy(self._clearSearch, self));
                $(document).on('click', "#" + this.element.attr('id') + ' .document', $.proxy(self._zoom, self));
                $(document).on('click', "#" + this.element.attr('id') + ' .document > i', $.proxy(self._zoom, self));
                $(document).on('click', "#" + this.element.attr('id') + ' .document > small', $.proxy(self._zoom, self));
                $(document).on('click', "#" + this.element.attr('id') + ' .subdocument span', $.proxy(self._zoom, self));
                $(document).on('click', "#" + this.element.attr('id') + ' .document', $.proxy(self._highlightOn, self));
                $(document).on('click', "#" + this.element.attr('id') + ' .risse-zu-flurstueck', $.proxy(self._changeSearchTypeToRisseFst, self));
                $(document).on('mouseover', "#" + this.element.attr('id') + ' .document', $.proxy(self._highlightOn, self));
                $(document).on('mouseover', "#" + this.element.attr('id') + ' .document > i', $.proxy(self._highlightOn, self));
                $(document).on('mouseover', "#" + this.element.attr('id') + ' .document > small', $.proxy(self._highlightOn, self));
                $(document).on('mouseover', "#" + this.element.attr('id') + ' .subdocument span', $.proxy(self._highlightOn, self));
                $(document).on('mouseout', "#" + this.element.attr('id') + ' .document', $.proxy(self._highlightOff, self));
                $(document).on('mouseout', "#" + this.element.attr('id') + ' .document > i', $.proxy(self._highlightOff, self));
                $(document).on('mouseout', "#" + this.element.attr('id') + ' .document > small', $.proxy(self._highlightOff, self));
                $(document).on('mouseout', "#" + this.element.attr('id') + ' .subdocument span', $.proxy(self._highlightOff, self));
                $(document).on('click', "#" + this.element.attr('id') + ' div[data-page]', $.proxy(self._changePage, self));
                $(document).on('change', "#" + this.element.attr('id') + " select", $.proxy(self._changeSearchType, self));
                $(document).on('click', "#" + this.element.attr('id') + ' .eigen', $.proxy(self._showEigenInfo, self));
                $(document).on('click', "#" + this.element.attr('id') + ' .grund', $.proxy(self._showGrundInfo, self));
            }

            this._showSearch();
        },
        _closeMobilePane: function (e) {
            $('#mobilePaneClose').click();
        },
        _showSearch: function () {
            var search = $('#search-select', this.element).val();
            $('.removeResultsButton', this.element).removeClass('hidden');
            $('.search', this.element).removeClass('hidden');
            $('#search', this.element).val('');
            $('#searchResults', this.element).remove();
            if (search === 'addr') {
                $('.basesearchonecontent').html(this.hilfetexte.allgemein_drei.concat(this.hilfetexte.addr));
            } else if (search === 'auftrag') {
                $('.basesearchonecontent').html(this.hilfetexte.allgemein_zwei.concat(this.hilfetexte.auftrag));
            } else if (search === 'baulasten') {
                $('.basesearchonecontent').html(this.hilfetexte.allgemein_zwei.concat(this.hilfetexte.baulasten));
            } else if (search === 'eigen') {
                $('.basesearchonecontent').html(this.hilfetexte.allgemein_zwei.concat(this.hilfetexte.eigen));
            } else if (search === 'flur') {
                $('.basesearchonecontent').html(this.hilfetexte.allgemein_zwei.concat(this.hilfetexte.flur));
            } else if (search === 'grund') {
                $('.basesearchonecontent').html(this.hilfetexte.allgemein_eins.concat(this.hilfetexte.grund));
            } else if (search === 'risse_fst') {
                $('.basesearchonecontent').html(this.hilfetexte.allgemein_drei.concat(this.hilfetexte.risse_fst));
            } else if (search === 'schiffe') {
                $('.basesearchonecontent').html(this.hilfetexte.allgemein_eins.concat(this.hilfetexte.schiffe));
            }
        },
        _clearSearch: function () {
            $('#search', this.element).val('');
            $('#searchResults', this.element).remove();
            $('#page', this.element).val(1);
            $('#search', this.element).val('');
            $('#searchResults', this.element).remove();
            var search = $('#search-select', this.element).val();
            if (search === 'addr') {
                $('.basesearchonecontent').html(this.hilfetexte.allgemein_drei.concat(this.hilfetexte.addr));
            } else if (search === 'auftrag') {
                $('.basesearchonecontent').html(this.hilfetexte.allgemein_zwei.concat(this.hilfetexte.auftrag));
            } else if (search === 'baulasten') {
                $('.basesearchonecontent').html(this.hilfetexte.allgemein_zwei.concat(this.hilfetexte.baulasten));
            } else if (search === 'eigen') {
                $('.basesearchonecontent').html(this.hilfetexte.allgemein_zwei.concat(this.hilfetexte.eigen));
            } else if (search === 'flur') {
                $('.basesearchonecontent').html(this.hilfetexte.allgemein_zwei.concat(this.hilfetexte.flur));
            } else if (search === 'grund') {
                $('.basesearchonecontent').html(this.hilfetexte.allgemein_eins.concat(this.hilfetexte.grund));
            } else if (search === 'risse_fst') {
                $('.basesearchonecontent').html(this.hilfetexte.allgemein_drei.concat(this.hilfetexte.risse_fst));
            } else if (search === 'schiffe') {
                $('.basesearchonecontent').html(this.hilfetexte.allgemein_eins.concat(this.hilfetexte.schiffe));
            }
        },
        _resetSearch: function () {
            this._highlightOffAll();
            $('#page', this.element).val(1);
            $('#search', this.element).val('');
            $('#searchResults', this.element).remove();
            var search = $('#search-select', this.element).val();
            if (search === 'addr') {
                $('.basesearchonecontent').html(this.hilfetexte.allgemein_drei.concat(this.hilfetexte.addr));
            } else if (search === 'auftrag') {
                $('.basesearchonecontent').html(this.hilfetexte.allgemein_zwei.concat(this.hilfetexte.auftrag));
            } else if (search === 'baulasten') {
                $('.basesearchonecontent').html(this.hilfetexte.allgemein_zwei.concat(this.hilfetexte.baulasten));
            } else if (search === 'eigen') {
                $('.basesearchonecontent').html(this.hilfetexte.allgemein_zwei.concat(this.hilfetexte.eigen));
            } else if (search === 'flur') {
                $('.basesearchonecontent').html(this.hilfetexte.allgemein_zwei.concat(this.hilfetexte.flur));
            } else if (search === 'grund') {
                $('.basesearchonecontent').html(this.hilfetexte.allgemein_eins.concat(this.hilfetexte.grund));
            } else if (search === 'risse_fst') {
                $('.basesearchonecontent').html(this.hilfetexte.allgemein_drei.concat(this.hilfetexte.risse_fst));
            } else if (search === 'schiffe') {
                $('.basesearchonecontent').html(this.hilfetexte.allgemein_eins.concat(this.hilfetexte.schiffe));
            }
        },
        _zoomToTarget: function (point) {
            var olMap = this.mbMap.map.olMap;
            $.proxy(this._zoomto(olMap), this);
            $.proxy(this._setCenter(point, olMap), this);
        },
        _zoomto: function (map) {
            var mapscales = map.options.scales;
            var scalesSize = mapscales.length;

            var zoom = scalesSize / 2;
            if (scalesSize % 2 !== 0) {
                zoom = parseInt(zoom, 10);
            }
            map.zoomTo(zoom);
        },
        _setCenter: function (point, map) {
            var targetCoord = new OpenLayers.LonLat(point.x, point.y);
            map.setCenter(targetCoord);
            $.proxy(this._zoom(map), false);

        },
        _setupAlkisinfo: function () {
            this.alkisInfoDialog = $('#' + this.options.alkisinfo);
        },
        _alkisinfo: function (func, param) {
            $('#' + this.options.alkisinfo).mbAlkisInfo(func, param);
        },
        _showEigenInfo: function (e) {
            var self = this;
            var gmlId = $(e.currentTarget).attr('data-gmlid');
            self._alkisinfo('activateEigenSearchInfo', gmlId);
        },
        _showGrundInfo: function (e) {
            var self = this;
            var gmlId = $(e.currentTarget).attr('data-gmlid');
            self._alkisinfo('activateGrundSearchInfo', gmlId);
        },
        _changeSearchType: function (e) {
            var self = this;
            this._highlightOffAll();
            this._showSearch();
        },
        _changeSearchTypeToRisseFst: function (e) {
            var self = this;
            this._highlightOffAll();
            $('select#search-select').val('risse_fst');
            $('select#search-select').change();
            $('div.dropdownValue').text('Risse zu Flurstücken');
            this._autoStartSearch($(e.target).data('flurstuecksnummer'));
        },
        _changePage: function (e) {
            var self = this;
            $('#page', this.element).val($(e.target).data('page'));
            self._find();
        },
        _autoStartSearch: function (term) {
            $('input#search').val(term);
            $('input#search').keyup();
        },
        _findOnKeyup: function (e) {
            var self = this;
            
            var search = $('#search-select', this.element).val();
            if (((search === 'addr' || search === 'risse_fst') && $('#search', self.element).val().length > 2) || ((search === 'auftrag' || search === 'baulasten' || search === 'eigen' || search === 'flur') && $('#search', self.element).val().length > 1) || (search !== 'addr' && search !== 'baulasten' && search !== 'auftrag' && search !== 'eigen' && search !== 'flur' && search !== 'risse_fst')) {

                if (typeof self.options.timeoutId !== 'undefined') {
                    window.clearTimeout(self.options.timeoutId);
                }

                self.options.timeoutId = window.setTimeout(function () {
                    self.options.timeoutId = undefined;
                    $('#page', self.element).val(1);
                    self._find();
                }, self.options.timeoutDelay);

            }
        },
        _find: function (terms) {
            var self = this;
            if ($('#opr-search-select option[value="risse_fst"]').length) {
                var risseFstLink = 1;
            } else {
                var risseFstLink = 0;
            }
            $.ajax({
                url: Mapbender.configuration.application.urls.element + '/' + this.element.attr('id') + '/search',
                type: 'POST',
                data: {
                    term: $('#search', self.element).val(),
                    page: $('#page', self.element).val(),
                    type: $("#" + $(self.element).attr('id') + " select").val(),
                    risse_fst_link: risseFstLink,
                },
                dataType: 'text',
                contetnType: 'text/html',
                context: this,
                success: this._findSuccess,
                error: this._findError
            });
            return false;
        },
        _findSuccess: function (response, textStatus, jqXHR) {
            $('div.basesearchonecontent', this.element).html(response);
        },
        _zoom: function (e) {
            var geom,
                    mapProj = Mapbender.Model.getCurrentProj();
            if ($(e.target).data('geom')) {
                geom = OpenLayers.Geometry.fromWKT($(e.target).data('geom'));
            } else if ($(e.target).data('x') && $(e.target).data('y')) {
                geom = new OpenLayers.Geometry.Point(parseFloat($(e.target).data('x')), parseFloat($(e.target).data('y')));
            } else if ($(e.target).parent().data('geom')) {
                geom = OpenLayers.Geometry.fromWKT($(e.target).parent().data('geom'));
            } else if ($(e.target).parent().data('x') && $(e.target).parent().data('y')) {
                geom = new OpenLayers.Geometry.Point(parseFloat($(e.target).parent().data('x')), parseFloat($(e.target).parent().data('y')));
            } else {
                return;
            }
            
            if (this.dataSrsProj.projCode !== mapProj.projCode) {
                geom = geom.transform(this.dataSrsProj, mapProj);
            }
            var buffer = this.options.buffer ? this.options.buffer : 1; // kilometer
            var geomExtent = Mapbender.Model.calculateExtent(geom, {w: buffer, h: buffer});
            var zoomLevel = this.mbMap.map.olMap.getZoomForExtent(geomExtent, false);
            var centroid = geom.getCentroid(true);
            this.mbMap.map.olMap.setCenter(new OpenLayers.LonLat(centroid.x, centroid.y), zoomLevel);
            // falls keine mobile Oberfläche...
            if (mobile === false) {
                if (this.foundedFeature) {
                    Mapbender.Model.highlightOff(this.foundedFeature);
                    this.foundedFeature = null;
                }
            }
            if (geom.CLASS_NAME === "OpenLayers.Geometry.Point") {
                var centroid = geom.getCentroid(true);
                var poi = {
                    position: new OpenLayers.LonLat(centroid.x, centroid.y),
                    label: ""
                };
                var foundedFeature = new OpenLayers.Feature.Vector(centroid, poi);
                foundedFeature.style = OpenLayers.Util.applyDefaults(this.point_style, OpenLayers.Feature.Vector.style["default"]);
            } else {
                var foundedFeature = new OpenLayers.Feature.Vector(geom);
                foundedFeature.style = OpenLayers.Util.applyDefaults(this.line_style, OpenLayers.Feature.Vector.style["default"]);

            }
            if (foundedFeature) {
                // falls mobile Oberfläche...
                if (mobile === true) {
                    // permanenete Markierung zunächst zulassen...
                    Mapbender.Model.highlightOn(foundedFeature, {clearFirst: false, "goto": false});
                    // ...nach ein paar Sekunden aber wieder löschen
                    function highlightOffAfterTimeout() {
                        Mapbender.Model.highlightOff(foundedFeature);
                    }
                    setTimeout(highlightOffAfterTimeout, 5000)
                // ansonsten...
                } else {
                    // permanenete Markierung
                    if (foundedFeature) {
                        this.foundedFeature = [foundedFeature];
                        Mapbender.Model.highlightOn(this.foundedFeature, {clearFirst: false, "goto": false});
                    }
                }
            } 
        },
        _highlightOn: function (e) {
            var geom,
                    mapProj = Mapbender.Model.getCurrentProj();
            if ($(e.target).data('geom')) {
                geom = OpenLayers.Geometry.fromWKT($(e.target).data('geom'));
            } else if ($(e.target).data('x') && $(e.target).data('y')) {
                geom = new OpenLayers.Geometry.Point(parseFloat($(e.target).data('x')), parseFloat($(e.target).data('y')));
            } else if ($(e.target).parent().data('geom')) {
                geom = OpenLayers.Geometry.fromWKT($(e.target).parent().data('geom'));
            } else if ($(e.target).parent().data('x') && $(e.target).parent().data('y')) {
                geom = new OpenLayers.Geometry.Point(parseFloat($(e.target).parent().data('x')), parseFloat($(e.target).parent().data('y')));
            } else {
                return;
            }

            if (this.dataSrsProj.projCode !== mapProj.projCode)
                geom = geom.transform(this.dataSrsProj, mapProj);
            this._highlightOff(e);
            if (Mapbender.Model.getMapExtent().toGeometry().intersects(geom)) {
                var geomFeature = new OpenLayers.Feature.Vector(geom);
                if (geomFeature) {
                    if (geom.CLASS_NAME === "OpenLayers.Geometry.Point") {
                        geomFeature.style = OpenLayers.Util.applyDefaults(this.point_style, OpenLayers.Feature.Vector.style["default"]);
                    } else {
                        geomFeature.style = OpenLayers.Util.applyDefaults(this.line_style, OpenLayers.Feature.Vector.style["default"]);
                    }
                    this.geomFeature = [geomFeature];
                    this.mbMap.highlightOn(this.geomFeature, {clearFirst: false, "goto": false});
                }
            }
        },
        _highlightOff: function (e) {
            if (this.geomFeature) {
                Mapbender.Model.highlightOff(this.geomFeature);
                this.geomFeature = null;
            }
        },
        _highlightOffAll: function (e) {
            if (this.geomFeature) {
                Mapbender.Model.highlightOff(this.geomFeature);
                this.geomFeature = null;
            }
            if (this.foundedFeature) {
                Mapbender.Model.highlightOff(this.foundedFeature);
                this.foundedFeature = null;
            }
        },
        _findError: function (response) {
            Mapbender.error(response);
        },
        _destroy: $.noop
    });

})(jQuery);
