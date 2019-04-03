(function ($) {
    $.widget("mapbender.mbOlcSearch", {
        options: {
            timeoutDelay: 300,
            timeoutId: null,
            buffer: 1.0,
            dataSrs: 'EPSG:25833',
            spatialSearchSrs: 'EPSG:4326'
        },
        firstTimeSearch: true,
        hilfetext: 'Die Suche startet automatisch während der Eingabe. Sie können Ihre Suche über folgende Arten von Eingaben gestalten:<br/><br/><ul class="hilfetexte-liste"><li>→ voller <i>Plus code</i> [Beispiele: <span>9F6J33VX+55</span>, <span>9F000000+</span> oder <span>9F6J33+</span>]</li><li>→ regionaler <i>Plus code</i> [Beispiele: <span>33VX+55, Rostock</span> oder <span>rostock,33VX+55</span>]</li><li>→ Koordinatenpaar in der Notation <span>x/Länge,y/Breite</span> [Beispiele: <span>310223,5997644</span> oder <span>12.098,54.092</span>]</li></ul>',
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
            $('#clear-search-olc', this.element).on('click', $.proxy(self._clearSearch, self));
            $(document).on('click', "#" + this.element.attr('id') + ' .document', $.proxy(self._activateLayer, self));
            $(document).on('click', "#" + this.element.attr('id') + ' .document', $.proxy(self._zoom, self));
            $(document).on('click', "#" + this.element.attr('id') + ' .document > i', $.proxy(self._zoom, self));
            $(document).on('click', "#" + this.element.attr('id') + ' .document > small', $.proxy(self._zoom, self));
            $(document).on('click', "#" + this.element.attr('id') + ' .document', $.proxy(self._highlightOn, self));
            $(document).on('mouseover', "#" + this.element.attr('id') + ' .document', $.proxy(self._highlightOn, self));
            $(document).on('mouseover', "#" + this.element.attr('id') + ' .document > i', $.proxy(self._highlightOn, self));
            $(document).on('mouseover', "#" + this.element.attr('id') + ' .document > small', $.proxy(self._highlightOn, self));
            $(document).on('mouseout', "#" + this.element.attr('id') + ' .document', $.proxy(self._highlightOff, self));
            $(document).on('mouseout', "#" + this.element.attr('id') + ' .document > i', $.proxy(self._highlightOff, self));
            $(document).on('mouseout', "#" + this.element.attr('id') + ' .document > small', $.proxy(self._highlightOff, self));
            $('#inputSrs', this.element).on('change', $.proxy(self._inputSrsChanged, self));
            this._showSearch();
        },
        _showSearch: function () {
            $('.removeResultsButton').removeClass('hidden');
            $('.search').removeClass('hidden');
            $('#search-olc').val('');
            $('#searchResultsOlc').remove();
            $('.searcholccontent').html(this.hilfetext);
        },
        _clearSearch: function () {
            $('#search-olc', this.element).val('');
            $('#searchResultsOlc', this.element).remove();
            $('.searcholccontent').html(this.hilfetext);
            this.firstTimeSearch = true;
        },
        _resetSearch: function () {
            this._highlightOffAll();
            $('#search-olc', this.element).val('');
            $('#searchResultsOlc', this.element).remove();
            $('.searcholccontent').html(this.hilfetext);
            this.firstTimeSearch = true;
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
        _inputSrsChanged: function () {
            if ($('#search-olc').val().length)
                $.proxy(this._findOnKeyup, this);
        },
        _findOnKeyup: function (e) {
            var self = this;

            if (typeof self.options.timeoutId !== 'undefined') {
                window.clearTimeout(self.options.timeoutId);
            }

            self.options.timeoutId = window.setTimeout(function () {
                self.options.timeoutId = undefined;
                self._find();
            }, self.options.timeoutDelay);
        },
        _find: function (terms) {
            var self = this;
            $.ajax({
                url: Mapbender.configuration.application.urls.element + '/' + this.element.attr('id') + '/search',
                type: 'POST',
                data: {
                    term: $('#search-olc', self.element).val(),
                    epsg_in: $('#inputSrs', self.element).val(),
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
            $('div.searcholccontent', this.element).html(response);
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
                // permanenete Markierung
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
        _activateLayer: function () {
            if (this.firstTimeSearch === true) {
                var layertreeRootContainer = $('li[data-type="root"][data-title="Open Location Codes (Plus codes)"]');
                $('li[data-type="simple"]', layertreeRootContainer).each(function(idx, item) {
                    var layertreeLayerContainer = $(item);
                    var layertreeLayerCheckbox = $('input[name="selected"]:first', layertreeLayerContainer);
                    layertreeLayerCheckbox.prop('checked', true);
                    layertreeLayerCheckbox.change();
                });
            }
            this.firstTimeSearch = false;
        },
        _destroy: $.noop
    });

})(jQuery);
