(function ($) {
    $.widget("mapbender.mbThematicSearchOne", {
        options: {
            timeoutDelay: 300,
            timeoutId: null,
            buffer: 1.0,
            dataSrs: 'EPSG:25833',
            spatialSearchSrs: 'EPSG:4326'
        },
        firstTimeSearch: {
            anundverkauf: true,
            anlagevermoegendereigenbetriebe: true,
            baumkataster: true,
            bebauungsplaene: true,
            bebauungsplaene_nur_rk: true,
            betriebegewerblicherart: true,
            grundvermoegen: true,
            gruenfriedhofsflaechen: true,
            gruenpflegeobjekte: true,
            ingenieurbauwerke: true,
            kleingartenanlagen: true,
            leuchten: true,
            leuchtenschalteinrichtungen: true,
            lichtsignalanlagen: true,
            mietenpachten: true,
            spielgeraete: true,
            strassennetz: true,
            wirtschaftseinheiten_wiro: true
        },
        hilfetexte: {
            allgemein: 'Die Suche startet automatisch während der Eingabe. Sie können Ihre Suche über folgende Arten von Eingaben gestalten:<br/><br/><ul class="hilfetexte-liste">',
            anundverkauf: '<li>→ Flurstück in verschiedenen Kombinationen (Reihenfolge egal) [Beispiele: <span>2231 179</span> oder <span>2231 1 179</span> oder <span>2231 1 /1</span> oder <span>2231 /1</span> oder <span>gehlsdorf 179</span> oder <span>gehlsdorf 1 179</span> oder <span>gehlsdorf 1 /1</span> oder <span>gehlsdorf /1</span>]</li><li>→ Aktenzeichen [Beispiele: <span>2332VW140003</span> oder <span>2332</span>]</li><li>→ Status [Beispiel: <span>SO</span>]</li><li>→ Bemerkungen [Beispiel: <span>brückenweg</span>]</li></ul>',
            anlagevermoegendereigenbetriebe: '<li>→ Flurstück in verschiedenen Kombinationen (Reihenfolge egal) [Beispiele: <span>2245 884</span> oder <span>2245 1 884</span> oder <span>2245 1 /98</span> oder <span>2245 /98</span> oder <span>flurbezirk vi 884</span> oder <span>flurbezirk vi 1 884</span> oder <span>flurbezirk vi 1 /98</span> oder <span>flurbezirk vi /98</span>]</li><li>→ Aktenzeichen [Beispiele: <span>00.06.-22441-bG-0043</span> oder <span>00.06.-22451</span> oder <span>223814</span>]</li><li>→ Status [Beispiel: <span>BV 88</span>]</li><li>→ Wirtschaftseinheit [Beispiel: <span>1177</span>]</li></ul>',
            baumkataster: '<li>→ Bewirtschafter [Beispiel: <span>62</span>]</li><li>→ Grünpflegebezirk [Beispiel: <span>19</span>]</li><li>→ Nummer des Grünpflegeobjektes [Beispiele: <span>3027/02</span> oder <span>3027</span>]</li><li>→ Bezeichnung des Grünpflegeobjektes [Beispiel: <span>barnstorfer anla</span>]</li><li>→ Nummer als Kombination aus Grünpflegebezirk, Nummer des Grünpflegeobjektes und Nummer (Reihenfolge egal) [Beispiel: <span>11 3004 19</span>]</li><li>→ Nummer als Kombination aus Grünpflegebezirk, Bezeichnung des Grünpflegeobjektes und Nummer (Reihenfolge egal) [Beispiel: <span>11 tolstoi 19</span>]</li><li>→ Teil als Kombination aus Grünpflegebezirk, Nummer des Grünpflegeobjektes und Teilnummer (Reihenfolge egal) [Beispiel: <span>19 1100/04 956</span>]</li><li>→ Teil als Kombination aus Grünpflegebezirk, Bezeichnung des Grünpflegeobjektes und Teilnummer (Reihenfolge egal) [Beispiel: <span>19 wallanl 956</span>]</li></ul><br/>Resultate können Bäume, Bäume (gefällt), Baumgruppen sowie Baumreihen sein.',
            bebauungsplaene: '<li>→ Nummer [Beispiele: <span>16.w.43</span> oder <span>16w</span>]</li><li>→ Bezeichnung [Beispiel: <span>nienhag</span>]</li></ul><br/>Resultate können rechtskräftige Bebauungspläne sowie Bebauungspläne im Verfahren sein.',
            bebauungsplaene_nur_rk: '<li>→ Nummer [Beispiele: <span>16.w.43</span> oder <span>16w</span>]</li><li>→ Bezeichnung [Beispiel: <span>nienhag</span>]</li></ul>',
            betriebegewerblicherart: '<li>→ Flurstück in verschiedenen Kombinationen (Reihenfolge egal) [Beispiele: <span>2241 372</span> oder <span>2241 1 372</span> oder <span>2241 1 /3</span> oder <span>2241 /3</span> oder <span>flurbezirk ii 372</span> oder <span>flurbezirk ii 1 372</span> oder <span>flurbezirk ii 1 /3</span> oder <span>flurbezirk ii /3</span>]</li><li>→ Aktenzeichen [Beispiele: <span>00.06.-22201-bG-0011</span> oder <span>00.06.-22352</span> oder <span>223815</span>]</li><li>→ Status [Beispiel: <span>BgA 5</span>]</li><li>→ Bemerkungen [Beispiel: <span>Stadthafen</span>]</li></ul>',
            grundvermoegen: '<li>→ Aktenzeichen [Beispiele: <span>00.06.-22413-igb-0021</span> oder <span>00.06.-22352</span> oder <span>2235</span>]</li></ul>',
            gruenfriedhofsflaechen: '<li>→ Art [Beispiel: <span>rasen</span>]</li><li>→ Grünpflegebezirk [Beispiel: <span>24</span>]</li><li>→ Nummer des Grünpflegeobjektes [Beispiele: <span>1118/02</span> oder <span>1118</span>]</li><li>→ Bezeichnung des Grünpflegeobjektes [Beispiel: <span>kriegsgräb</span>]</li><li>→ Pflegebezeichnung [Beispiel: <span>1116 wiese</span>]</li><li>→ Teil als Kombination aus Grünpflegebezirk, Nummer des Grünpflegeobjektes und Teilnummer (Reihenfolge egal) [Beispiel: <span>21 1118 13</span>]</li><li>→ Teil als Kombination aus Grünpflegebezirk, Bezeichnung des Grünpflegeobjektes und Teilnummer (Reihenfolge egal) [Beispiel: <span>21 spiellandschaft 13</span>]</li></ul>',
            gruenpflegeobjekte: '<li>→ Grünpflegebezirk [Beispiel: <span>25</span>]</li><li>→ Art [Beispiel: <span>ballspielan</span>]</li><li>→ Nummer [Beispiele: <span>3015/02</span> oder <span>3015</span>]</li><li>→ Bezeichnung [Beispiel: <span>langenorter hufe/haferweg</span>]</li></ul><br/>Resultate können Friedhöfe, Parks und Grünanlagen, Spielplätze sowie Straßenbegleitgrün sein.',
            ingenieurbauwerke: '<li>→ Nummer [Beispiel: <span>bw 108</span>]</li><li>→ ASB-Nummer [Beispiele: <span>1938:523</span> oder <span>523</span>]</li><li>→ Art [Beispiel: <span>Brücke</span>]</li></ul>',
            kleingartenanlagen: '<li>→ Bezeichnung [Beispiel: <span>helsin</span>]</li></ul>',
            leuchten: '<li>→ Nummer des Leuchtentragsystems [Beispiel: <span>055-01-2-6</span>]</li><li>→ MSLINK des Leuchtentragsystems [Beispiel: <span>7725660</span>]</li><li>→ Nummer der Leuchte [Beispiel: <span>106-33-4-5</span>]</li><li>→ MSLINK der Leuchte [Beispiel: <span>3058105</span>]</li></ul>',
            leuchtenschalteinrichtungen: '<li>→ MSLINK [Beispiel: <span>853138</span>]</li><li>→ Bezeichnung [Beispiel: <span>S 107-13</span>]</li></ul>',
            lichtsignalanlagen: '<li>→ Nummer [Beispiel: <span>LSA 303</span>]</li><li>→ Knoten-Nummer [Beispiel: <span>422</span>]</li><li>→ Bezeichnung [Beispiel: <span>goetheplatz</span>]</li></ul>',
            mietenpachten: '<li>→ Aktenzeichen [Beispiel: <span>2341l04</span>]</li></ul><br/>Es werden je Aktenzeichen immer sowohl die Teilflächen als auch die Gesamtfläche gelistet.',
            spielgeraete: '<li>→ Grünpflegebezirk [Beispiel: <span>19</span>]</li><li>→ Art des Grünpflegeobjektes [Beispiel: <span>ballspielan</span>]</li><li>→ Nummer des Grünpflegeobjektes [Beispiele: <span>1100/02</span> oder <span>1100</span>]</li><li>→ Bezeichnung des Grünpflegeobjektes [Beispiel: <span>park am fi</span>]</li><li>→ Pflegeeinheit [Beispiel: <span>8313</span>]</li><li>→ Nummer als Kombination aus Grünpflegebezirk, Nummer des Grünpflegeobjektes und Nummer (Reihenfolge egal) [Beispiel: <span>19 1100 10</span>]</li><li>→ Nummer als Kombination aus Grünpflegebezirk, Bezeichnung des Grünpflegeobjektes und Nummer (Reihenfolge egal) [Beispiel: <span>19 wallanlagen 10</span>]</li></ul>',
            strassennetz: '<li>→ Nummer [Beispiel: <span>0995071</span>]</li><li>→ SKEY [Beispiel: <span>0105005 0205301</span>]</li></ul><br/>Resultate können Netzknoten sowie Netzabschnitte sein, jeweils gekennzeichnet durch ein vorangestelltes sprechendes Icon.',
            wirtschaftseinheiten_wiro: '<li>→ Nummer [Beispiel: <span>6433</span>]</li></ul>'
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
        _create: function () {
            var self = this;
            if (!Mapbender.checkTarget("Search", this.options.target)) {
                return;
            }
            Mapbender.elementRegistry.onElementReady(this.options.target, $.proxy(self._setup, self));
        },
        /**
         * Initializes the wmc handler
         */
        _setup: function () {
            var self = this;
            this.mbMap = $('#' + this.options.target).data('mapbenderMbMap');
            this.dataSrsProj = Mapbender.Model.getProj(this.options.dataSrs);
            $('input', this.element).on('keyup', $.proxy(self._findOnKeyup, self));
            $('#removeResults', this.element).on('click', $.proxy(self._resetSearch, self));
            $('#clear-search-thematic-one', this.element).on('click', $.proxy(self._clearSearch, self));
            $(document).on('click', "#" + this.element.attr('id') + ' .document', $.proxy(self._activateLayer, self));
            $(document).on('click', "#" + this.element.attr('id') + ' .document', $.proxy(self._zoom, self));
            $(document).on('click', "#" + this.element.attr('id') + ' .document', $.proxy(self._highlightOn, self));
            $(document).on('mouseover', "#" + this.element.attr('id') + ' .document', $.proxy(self._highlightOn, self));
            $(document).on('mouseout', "#" + this.element.attr('id') + ' .document', $.proxy(self._highlightOff, self));
            $(document).on('click', "#" + this.element.attr('id') + ' div[data-page]', $.proxy(self._changePage, self));
            $(document).on('change', "#" + this.element.attr('id') + " select", $.proxy(self._changeSearchType, self));
            this._showSearch();
        },
        _showSearch: function () {
            var search = $('#search-select-thematic-one').val();
            $('.removeResultsButton').removeClass('hidden');
            $('.search').removeClass('hidden');
            $('#search-thematic-one').val('');
            $('#searchResultsThematicOne').remove();
            if (search === 'anundverkauf') {
                $('.thematicsearchonecontent').html(this.hilfetexte.allgemein.concat(this.hilfetexte.anundverkauf));
            }
            else if (search === 'anlagevermoegendereigenbetriebe') {
                $('.thematicsearchonecontent').html(this.hilfetexte.allgemein.concat(this.hilfetexte.anlagevermoegendereigenbetriebe));
            }
            else if (search === 'baumkataster') {
                $('.thematicsearchonecontent').html(this.hilfetexte.allgemein.concat(this.hilfetexte.baumkataster));
            }
            else if (search === 'bebauungsplaene') {
                $('.thematicsearchonecontent').html(this.hilfetexte.allgemein.concat(this.hilfetexte.bebauungsplaene));
            }
            else if (search === 'bebauungsplaene_nur_rk') {
                $('.thematicsearchonecontent').html(this.hilfetexte.allgemein.concat(this.hilfetexte.bebauungsplaene_nur_rk));
            }
            else if (search === 'betriebegewerblicherart') {
                $('.thematicsearchonecontent').html(this.hilfetexte.allgemein.concat(this.hilfetexte.betriebegewerblicherart));
            }
            else if (search === 'grundvermoegen') {
                $('.thematicsearchonecontent').html(this.hilfetexte.allgemein.concat(this.hilfetexte.grundvermoegen));
            }
            else if (search === 'gruenfriedhofsflaechen') {
                $('.thematicsearchonecontent').html(this.hilfetexte.allgemein.concat(this.hilfetexte.gruenfriedhofsflaechen));
            }
            else if (search === 'gruenpflegeobjekte') {
                $('.thematicsearchonecontent').html(this.hilfetexte.allgemein.concat(this.hilfetexte.gruenpflegeobjekte));
            }
            else if (search === 'ingenieurbauwerke') {
                $('.thematicsearchonecontent').html(this.hilfetexte.allgemein.concat(this.hilfetexte.ingenieurbauwerke));
            }
            else if (search === 'kleingartenanlagen') {
                $('.thematicsearchonecontent').html(this.hilfetexte.allgemein.concat(this.hilfetexte.kleingartenanlagen));
            }
            else if (search === 'leuchten') {
                $('.thematicsearchonecontent').html(this.hilfetexte.allgemein.concat(this.hilfetexte.leuchten));
            }
            else if (search === 'leuchtenschalteinrichtungen') {
                $('.thematicsearchonecontent').html(this.hilfetexte.allgemein.concat(this.hilfetexte.leuchtenschalteinrichtungen));
            }
            else if (search === 'lichtsignalanlagen') {
                $('.thematicsearchonecontent').html(this.hilfetexte.allgemein.concat(this.hilfetexte.lichtsignalanlagen));
            }
            else if (search === 'mietenpachten') {
                $('.thematicsearchonecontent').html(this.hilfetexte.allgemein.concat(this.hilfetexte.mietenpachten));
            }
            else if (search === 'spielgeraete') {
                $('.thematicsearchonecontent').html(this.hilfetexte.allgemein.concat(this.hilfetexte.spielgeraete));
            }
            else if (search === 'strassennetz') {
                $('.thematicsearchonecontent').html(this.hilfetexte.allgemein.concat(this.hilfetexte.strassennetz));
            }
            else if (search === 'wirtschaftseinheiten_wiro') {
                $('.thematicsearchonecontent').html(this.hilfetexte.allgemein.concat(this.hilfetexte.wirtschaftseinheiten_wiro));
            }
        },
        _clearSearch: function () {
            $('#search-thematic-one', this.element).val('');
            $('#searchResultsThematicOne', this.element).remove();
            var search = $('#search-select-thematic-one', this.element).val();
            if (search === 'anundverkauf') {
                $('.thematicsearchonecontent').html(this.hilfetexte.allgemein.concat(this.hilfetexte.anundverkauf));
                this.firstTimeSearch.anundverkauf = true;
            }
            else if (search === 'anlagevermoegendereigenbetriebe') {
                $('.thematicsearchonecontent').html(this.hilfetexte.allgemein.concat(this.hilfetexte.anlagevermoegendereigenbetriebe));
                this.firstTimeSearch.anlagevermoegendereigenbetriebe = true;
            }
            else if (search === 'baumkataster') {
                $('.thematicsearchonecontent').html(this.hilfetexte.allgemein.concat(this.hilfetexte.baumkataster));
                this.firstTimeSearch.baumkataster = true;
            }
            else if (search === 'bebauungsplaene') {
                $('.thematicsearchonecontent').html(this.hilfetexte.allgemein.concat(this.hilfetexte.bebauungsplaene));
                this.firstTimeSearch.bebauungsplaene = true;
            }
            else if (search === 'bebauungsplaene_nur_rk') {
                $('.thematicsearchonecontent').html(this.hilfetexte.allgemein.concat(this.hilfetexte.bebauungsplaene_nur_rk));
                this.firstTimeSearch.bebauungsplaene_nur_rk = true;
            }
            else if (search === 'betriebegewerblicherart') {
                $('.thematicsearchonecontent').html(this.hilfetexte.allgemein.concat(this.hilfetexte.betriebegewerblicherart));
                this.firstTimeSearch.betriebegewerblicherart = true;
            }
            else if (search === 'grundvermoegen') {
                $('.thematicsearchonecontent').html(this.hilfetexte.allgemein.concat(this.hilfetexte.grundvermoegen));
                this.firstTimeSearch.grundvermoegen = true;
            }
            else if (search === 'gruenfriedhofsflaechen') {
                $('.thematicsearchonecontent').html(this.hilfetexte.allgemein.concat(this.hilfetexte.gruenfriedhofsflaechen));
                this.firstTimeSearch.gruenfriedhofsflaechen = true;
            }
            else if (search === 'gruenpflegeobjekte') {
                $('.thematicsearchonecontent').html(this.hilfetexte.allgemein.concat(this.hilfetexte.gruenpflegeobjekte));
                this.firstTimeSearch.gruenpflegeobjekte = true;
            }
            else if (search === 'ingenieurbauwerke') {
                $('.thematicsearchonecontent').html(this.hilfetexte.allgemein.concat(this.hilfetexte.ingenieurbauwerke));
                this.firstTimeSearch.ingenieurbauwerke = true;
            }
            else if (search === 'kleingartenanlagen') {
                $('.thematicsearchonecontent').html(this.hilfetexte.allgemein.concat(this.hilfetexte.kleingartenanlagen));
                this.firstTimeSearch.kleingartenanlagen = true;
            }
            else if (search === 'leuchten') {
                $('.thematicsearchonecontent').html(this.hilfetexte.allgemein.concat(this.hilfetexte.leuchten));
                this.firstTimeSearch.leuchten = true;
            }
            else if (search === 'leuchtenschalteinrichtungen') {
                $('.thematicsearchonecontent').html(this.hilfetexte.allgemein.concat(this.hilfetexte.leuchtenschalteinrichtungen));
                this.firstTimeSearch.leuchtenschalteinrichtungen = true;
            }
            else if (search === 'lichtsignalanlagen') {
                $('.thematicsearchonecontent').html(this.hilfetexte.allgemein.concat(this.hilfetexte.lichtsignalanlagen));
                this.firstTimeSearch.lichtsignalanlagen = true;
            }
            else if (search === 'mietenpachten') {
                $('.thematicsearchonecontent').html(this.hilfetexte.allgemein.concat(this.hilfetexte.mietenpachten));
                this.firstTimeSearch.mietenpachten = true;
            }
            else if (search === 'spielgeraete') {
                $('.thematicsearchonecontent').html(this.hilfetexte.allgemein.concat(this.hilfetexte.spielgeraete));
                this.firstTimeSearch.spielgeraete = true;
            }
            else if (search === 'strassennetz') {
                $('.thematicsearchonecontent').html(this.hilfetexte.allgemein.concat(this.hilfetexte.strassennetz));
                this.firstTimeSearch.strassennetz = true;
            }
            else if (search === 'wirtschaftseinheiten_wiro') {
                $('.thematicsearchonecontent').html(this.hilfetexte.allgemein.concat(this.hilfetexte.wirtschaftseinheiten_wiro));
                this.firstTimeSearch.wirtschaftseinheiten_wiro = true;
            }
        },
        _resetSearch: function () {
            this._highlightOffAll();
            $('#search-thematic-one', this.element).val('');
            $('#searchResultsThematicOne', this.element).remove();
            var search = $('#search-select-thematic-one', this.element).val();
            if (search === 'anundverkauf') {
                $('.thematicsearchonecontent').html(this.hilfetexte.allgemein.concat(this.hilfetexte.anundverkauf));
                this.firstTimeSearch.anundverkauf = true;
            }
            else if (search === 'anlagevermoegendereigenbetriebe') {
                $('.thematicsearchonecontent').html(this.hilfetexte.allgemein.concat(this.hilfetexte.anlagevermoegendereigenbetriebe));
                this.firstTimeSearch.anlagevermoegendereigenbetriebe = true;
            }
            else if (search === 'baumkataster') {
                $('.thematicsearchonecontent').html(this.hilfetexte.allgemein.concat(this.hilfetexte.baumkataster));
                this.firstTimeSearch.baumkataster = true;
            }
            else if (search === 'bebauungsplaene') {
                $('.thematicsearchonecontent').html(this.hilfetexte.allgemein.concat(this.hilfetexte.bebauungsplaene));
                this.firstTimeSearch.bebauungsplaene = true;
            }
            else if (search === 'bebauungsplaene_nur_rk') {
                $('.thematicsearchonecontent').html(this.hilfetexte.allgemein.concat(this.hilfetexte.bebauungsplaene_nur_rk));
                this.firstTimeSearch.bebauungsplaene_nur_rk = true;
            }
            else if (search === 'betriebegewerblicherart') {
                $('.thematicsearchonecontent').html(this.hilfetexte.allgemein.concat(this.hilfetexte.betriebegewerblicherart));
                this.firstTimeSearch.betriebegewerblicherart = true;
            }
            else if (search === 'grundvermoegen') {
                $('.thematicsearchonecontent').html(this.hilfetexte.allgemein.concat(this.hilfetexte.grundvermoegen));
                this.firstTimeSearch.grundvermoegen = true;
            }
            else if (search === 'gruenfriedhofsflaechen') {
                $('.thematicsearchonecontent').html(this.hilfetexte.allgemein.concat(this.hilfetexte.gruenfriedhofsflaechen));
                this.firstTimeSearch.gruenfriedhofsflaechen = true;
            }
            else if (search === 'gruenpflegeobjekte') {
                $('.thematicsearchonecontent').html(this.hilfetexte.allgemein.concat(this.hilfetexte.gruenpflegeobjekte));
                this.firstTimeSearch.gruenpflegeobjekte = true;
            }
            else if (search === 'ingenieurbauwerke') {
                $('.thematicsearchonecontent').html(this.hilfetexte.allgemein.concat(this.hilfetexte.ingenieurbauwerke));
                this.firstTimeSearch.ingenieurbauwerke = true;
            }
            else if (search === 'kleingartenanlagen') {
                $('.thematicsearchonecontent').html(this.hilfetexte.allgemein.concat(this.hilfetexte.kleingartenanlagen));
                this.firstTimeSearch.kleingartenanlagen = true;
            }
            else if (search === 'leuchten') {
                $('.thematicsearchonecontent').html(this.hilfetexte.allgemein.concat(this.hilfetexte.leuchten));
                this.firstTimeSearch.leuchten = true;
            }
            else if (search === 'leuchtenschalteinrichtungen') {
                $('.thematicsearchonecontent').html(this.hilfetexte.allgemein.concat(this.hilfetexte.leuchtenschalteinrichtungen));
                this.firstTimeSearch.leuchtenschalteinrichtungen = true;
            }
            else if (search === 'lichtsignalanlagen') {
                $('.thematicsearchonecontent').html(this.hilfetexte.allgemein.concat(this.hilfetexte.lichtsignalanlagen));
                this.firstTimeSearch.lichtsignalanlagen = true;
            }
            else if (search === 'mietenpachten') {
                $('.thematicsearchonecontent').html(this.hilfetexte.allgemein.concat(this.hilfetexte.mietenpachten));
                this.firstTimeSearch.mietenpachten = true;
            }
            else if (search === 'spielgeraete') {
                $('.thematicsearchonecontent').html(this.hilfetexte.allgemein.concat(this.hilfetexte.spielgeraete));
                this.firstTimeSearch.spielgeraete = true;
            }
            else if (search === 'strassennetz') {
                $('.thematicsearchonecontent').html(this.hilfetexte.allgemein.concat(this.hilfetexte.strassennetz));
                this.firstTimeSearch.strassennetz = true;
            }
            else if (search === 'wirtschaftseinheiten_wiro') {
                $('.thematicsearchonecontent').html(this.hilfetexte.allgemein.concat(this.hilfetexte.wirtschaftseinheiten_wiro));
                this.firstTimeSearch.wirtschaftseinheiten_wiro = true;
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
        _changeSearchType: function (e) {
            var self = this;
            this._highlightOffAll();
            this._showSearch();
        },
        _changePage: function (e) {
            var self = this;
            $('#page-thematic-one', this.element).val($(e.target).data('page'));
            self._find();
        },
        _findOnKeyup: function (e) {
            var self = this;

            if (typeof self.options.timeoutId !== 'undefined') {
                window.clearTimeout(self.options.timeoutId);
            }

            self.options.timeoutId = window.setTimeout(function () {
                self.options.timeoutId = undefined;
                $('#page-thematic-one', this.element).val(1);
                self._find();
            }, self.options.timeoutDelay);
        },
        _find: function (terms) {
            var self = this;
            $.ajax({
                url: Mapbender.configuration.application.urls.element + '/' + this.element.attr('id') + '/search',
                type: 'POST',
                data: {
                    term: $('#search-thematic-one', self.element).val(),
                    page: $('#page-thematic-one', self.element).val(),
                    type: $("#" + $(self.element).attr('id') + " select").val(),
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
            $('div.thematicsearchonecontent', this.element).html(response);
        },
        _zoom: function (e) {
            var geom,
                    mapProj = Mapbender.Model.getCurrentProj();
            if ($(e.target).data('geom')) {
                geom = OpenLayers.Geometry.fromWKT($(e.target).data('geom'));
            } else if ($(e.target).data('x') && $(e.target).data('y')) {
                geom = new OpenLayers.Geometry.Point(parseFloat($(e.target).data('x')), parseFloat($(e.target).data('y')));
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
            if (this.foundedFeature) {
                Mapbender.Model.highlightOff(this.foundedFeature);
                this.foundedFeature = null;
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
                this.foundedFeature = [foundedFeature];
                Mapbender.Model.highlightOn(this.foundedFeature, {clearFirst: false, "goto": false});
            }
        },
        _highlightOn: function (e) {
            var geom,
                    mapProj = Mapbender.Model.getCurrentProj();
            if ($(e.target).data('geom')) {
                geom = OpenLayers.Geometry.fromWKT($(e.target).data('geom'));
            } else if ($(e.target).data('x') && $(e.target).data('y')) {
                geom = new OpenLayers.Geometry.Point(parseFloat($(e.target).data('x')), parseFloat($(e.target).data('y')));
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
        _activateLayer: function () {
            var search = $('#search-select-thematic-one').val();
            switch(search){
                case 'anundverkauf':
                    if (this.firstTimeSearch.anundverkauf === true) {
                        var layertreeLayerContainer = $('li[data-type="simple"][data-title="An- und Verkauf"]');
                        var layertreeLayerCheckbox = $('input[name="selected"]:first', layertreeLayerContainer);
                        layertreeLayerCheckbox.prop('checked', true);
                        layertreeLayerCheckbox.change();
                    }
                    this.firstTimeSearch.anundverkauf = false;
                    break;
                case 'anlagevermoegendereigenbetriebe':
                    if (this.firstTimeSearch.anlagevermoegendereigenbetriebe === true) {
                        var layertreeLayerContainer = $('li[data-type="simple"][data-title="Anlagevermögen der Eigenbetriebe"]');
                        var layertreeLayerCheckbox = $('input[name="selected"]:first', layertreeLayerContainer);
                        layertreeLayerCheckbox.prop('checked', true);
                        layertreeLayerCheckbox.change();
                    }
                    this.firstTimeSearch.anlagevermoegendereigenbetriebe = false;
                    break;
                case 'baumkataster':
                    if (this.firstTimeSearch.baumkataster === true) {
                        var layertreeRootContainer = $('li[data-type="root"][data-title="Baumkataster"]');
                        $('li[data-type="simple"]', layertreeRootContainer).each(function(idx, item) {
                            var layertreeLayerContainer = $(item);
                            var layertreeLayerCheckbox = $('input[name="selected"]:first', layertreeLayerContainer);
                            layertreeLayerCheckbox.prop('checked', true);
                            layertreeLayerCheckbox.change();
                        });
                    }
                    this.firstTimeSearch.baumkataster = false;
                    break;
                case 'bebauungsplaene':
                    if (this.firstTimeSearch.bebauungsplaene === true) {
                        var layertreeRootContainer = $('li[data-type="root"][data-title="B-Pläne"]');
                        $('li[data-type="simple"]', layertreeRootContainer).each(function(idx, item) {
                            var layertreeLayerContainer = $(item);
                            var layertreeLayerCheckbox = $('input[name="selected"]:first', layertreeLayerContainer);
                            layertreeLayerCheckbox.prop('checked', true);
                            layertreeLayerCheckbox.change();
                        });
                        var layertreeRootContainer = $('li[data-type="root"][data-title="B-Pläne im Verfahren"]');
                        $('li[data-type="simple"]', layertreeRootContainer).each(function(idx, item) {
                            var layertreeLayerContainer = $(item);
                            var layertreeLayerCheckbox = $('input[name="selected"]:first', layertreeLayerContainer);
                            layertreeLayerCheckbox.prop('checked', true);
                            layertreeLayerCheckbox.change();
                        });
                    }
                    this.firstTimeSearch.bebauungsplaene = false;
                    break;
                case 'bebauungsplaene_nur_rk':
                    if (this.firstTimeSearch.bebauungsplaene_nur_rk === true) {
                        var layertreeRootContainer = $('li[data-type="root"][data-title="B-Pläne"]');
                        $('li[data-type="simple"]', layertreeRootContainer).each(function(idx, item) {
                            var layertreeLayerContainer = $(item);
                            var layertreeLayerCheckbox = $('input[name="selected"]:first', layertreeLayerContainer);
                            layertreeLayerCheckbox.prop('checked', true);
                            layertreeLayerCheckbox.change();
                        });
                    }
                    this.firstTimeSearch.bebauungsplaene_nur_rk = false;
                    break;
                case 'betriebegewerblicherart':
                    if (this.firstTimeSearch.betriebegewerblicherart === true) {
                        var layertreeLayerContainer = $('li[data-type="simple"][data-title="Betriebe gewerblicher Art"]');
                        var layertreeLayerCheckbox = $('input[name="selected"]:first', layertreeLayerContainer);
                        layertreeLayerCheckbox.prop('checked', true);
                        layertreeLayerCheckbox.change();
                    }
                    this.firstTimeSearch.betriebegewerblicherart = false;
                    break;
                case 'grundvermoegen':
                    if (this.firstTimeSearch.grundvermoegen === true) {
                        var layertreeRootContainer = $('li[data-type="root"][data-title="Bewirtschaftung"]');
                        $('li[data-type="simple"]', layertreeRootContainer).each(function(idx, item) {
                            var layertreeLayerContainer = $(item);
                            var layertreeLayerCheckbox = $('input[name="selected"]:first', layertreeLayerContainer);
                            layertreeLayerCheckbox.prop('checked', true);
                            layertreeLayerCheckbox.change();
                        });
                    }
                    this.firstTimeSearch.grundvermoegen = false;
                    break;
                case 'gruenfriedhofsflaechen':
                    if (this.firstTimeSearch.gruenfriedhofsflaechen === true) {
                        var layertreeLayerContainer = $('li[data-type="simple"][data-title="Grünflächen und Friedhofsbegleitflächen"]');
                        var layertreeLayerCheckbox = $('input[name="selected"]:first', layertreeLayerContainer);
                        layertreeLayerCheckbox.prop('checked', true);
                        layertreeLayerCheckbox.change();
                    }
                    this.firstTimeSearch.gruenfriedhofsflaechen = false;
                    break;
                case 'gruenpflegeobjekte':
                    if (this.firstTimeSearch.gruenpflegeobjekte === true) {
                        var layertreeRootContainer = $('li[data-type="root"][data-title="Grünpflegeobjekte"]');
                        $('li[data-type="simple"]', layertreeRootContainer).each(function(idx, item) {
                            var layertreeLayerContainer = $(item);
                            var layertreeLayerCheckbox = $('input[name="selected"]:first', layertreeLayerContainer);
                            layertreeLayerCheckbox.prop('checked', true);
                            layertreeLayerCheckbox.change();
                        });
                    }
                    this.firstTimeSearch.gruenpflegeobjekte = false;
                    break;
                case 'ingenieurbauwerke':
                    if (this.firstTimeSearch.ingenieurbauwerke === true) {
                        var layertreeLayerContainer = $('li[data-type="simple"][data-title="Ingenieurbauwerke Verkehr"]');
                        var layertreeLayerCheckbox = $('input[name="selected"]:first', layertreeLayerContainer);
                        layertreeLayerCheckbox.prop('checked', true);
                        layertreeLayerCheckbox.change();
                    }
                    this.firstTimeSearch.ingenieurbauwerke = false;
                    break;
                case 'kleingartenanlagen':
                    if (this.firstTimeSearch.kleingartenanlagen === true) {
                        var layertreeLayerContainer = $('li[data-type="simple"][data-title="Kleingartenanlagen"]');
                        var layertreeLayerCheckbox = $('input[name="selected"]:first', layertreeLayerContainer);
                        layertreeLayerCheckbox.prop('checked', true);
                        layertreeLayerCheckbox.change();
                    }
                    this.firstTimeSearch.kleingartenanlagen = false;
                    break;
                case 'leuchten':
                    if (this.firstTimeSearch.leuchten === true) {
                        var layertreeLayerContainer = $('li[data-type="simple"][data-title="Leuchten"]');
                        var layertreeLayerCheckbox = $('input[name="selected"]:first', layertreeLayerContainer);
                        layertreeLayerCheckbox.prop('checked', true);
                        layertreeLayerCheckbox.change();
                    }
                    this.firstTimeSearch.leuchten = false;
                    break;
                case 'leuchtenschalteinrichtungen':
                    if (this.firstTimeSearch.leuchtenschalteinrichtungen === true) {
                        var layertreeLayerContainer = $('li[data-type="simple"][data-title="Leuchtenschalteinrichtungen"]');
                        var layertreeLayerCheckbox = $('input[name="selected"]:first', layertreeLayerContainer);
                        layertreeLayerCheckbox.prop('checked', true);
                        layertreeLayerCheckbox.change();
                    }
                    this.firstTimeSearch.leuchtenschalteinrichtungen = false;
                    break;
                case 'lichtsignalanlagen':
                    if (this.firstTimeSearch.lichtsignalanlagen === true) {
                        var layertreeLayerContainer = $('li[data-type="simple"][data-title="Lichtsignalanlagen"]');
                        var layertreeLayerCheckbox = $('input[name="selected"]:first', layertreeLayerContainer);
                        layertreeLayerCheckbox.prop('checked', true);
                        layertreeLayerCheckbox.change();
                    }
                    this.firstTimeSearch.lichtsignalanlagen = false;
                    break;
                case 'mietenpachten':
                    if (this.firstTimeSearch.mietenpachten === true) {
                        var layertreeLayerContainer = $('li[data-type="simple"][data-title="Mieten und Pachten"]');
                        var layertreeLayerCheckbox = $('input[name="selected"]:first', layertreeLayerContainer);
                        layertreeLayerCheckbox.prop('checked', true);
                        layertreeLayerCheckbox.change();
                    }
                    this.firstTimeSearch.mietenpachten = false;
                    break;
                case 'spielgeraete':
                    if (this.firstTimeSearch.spielgeraete === true) {
                        var layertreeLayerContainer = $('li[data-type="simple"][data-title="Spielgeräte"]');
                        var layertreeLayerCheckbox = $('input[name="selected"]:first', layertreeLayerContainer);
                        layertreeLayerCheckbox.prop('checked', true);
                        layertreeLayerCheckbox.change();
                    }
                    this.firstTimeSearch.spielgeraete = false;
                    break;
                case 'strassennetz':
                    if (this.firstTimeSearch.strassennetz === true) {
                        var layertreeLayerContainer = $('li[data-type="simple"][data-title="Netzknoten"]');
                        var layertreeLayerCheckbox = $('input[name="selected"]:first', layertreeLayerContainer);
                        layertreeLayerCheckbox.prop('checked', true);
                        layertreeLayerCheckbox.change();
                        layertreeLayerContainer = $('li[data-type="simple"][data-title="Netzabschnitte"]');
                        layertreeLayerCheckbox = $('input[name="selected"]:first', layertreeLayerContainer);
                        layertreeLayerCheckbox.prop('checked', true);
                        layertreeLayerCheckbox.change();
                    }
                    this.firstTimeSearch.strassennetz = false;
                    break;
                default:
                    if (this.firstTimeSearch.wirtschaftseinheiten_wiro === true) {
                        var layertreeRootContainer = $('li[data-type="group"][data-title="Wirtschaftseinheiten"]');
                        $('li[data-type="simple"]', layertreeRootContainer).each(function(idx, item) {
                            var layertreeLayerContainer = $(item);
                            var layertreeLayerCheckbox = $('input[name="selected"]:first', layertreeLayerContainer);
                            layertreeLayerCheckbox.prop('checked', true);
                            layertreeLayerCheckbox.change();
                        });
                    }
                    this.firstTimeSearch.wirtschaftseinheiten_wiro = false;
                    break;
            }
        },
        _destroy: $.noop
    });

})(jQuery);
