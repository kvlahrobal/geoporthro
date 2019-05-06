/*jslint browser: true, nomen: true*/
/*globals Mapbender, OpenLayers, _, jQuery*/

(function ($) {
    'use strict';
    /*jslint nomen: true*/
    /**
     * Description of what this does.
     *
     * @author Arne Schubert <atd.schubert@gmail.com>
     * @namespace mapbender.mbGpsPosition
     */
    $.widget("mapbender.mbGpsPosition", {
        options: {
            follow: false,
            average: 1,
            zoomToAccuracy: false,
            centerOnFirstPosition: true,
            zoomToAccuracyOnFirstPosition: true,
            accurancyStyle: {
                fillColor: '#FFF',
                fillOpacity: 0.5,
                strokeWidth: 1,
                strokeColor: '#FFF'
            },
            laivMode: false
        },
        map: null,
        observer: null,
        firstPosition: true,
        stack: [],

        _create: function () {
            var widget = this;
            var element = $(widget.element);
            var options = widget.options;
            var target = options.target;

            if (!Mapbender.checkTarget("mbGpsPosition", target)) {
                return;
            }

            Mapbender.elementRegistry.onElementReady(target, $.proxy(widget._setup, widget));

            if (!options.average) {
                options.average = 1;
            }

            element.click(function () {
                if(widget.isActive()) {
                    widget.deactivate();
                } else {
                    widget.activate();
                }
                return false;
            });
        },

        _setup: function () {
            this.map = $('#' + this.options.target).data('mapbenderMbMap');
            if (this.options.autoStart === true) {
                this.toggleTracking();
            }
        },

        _createMarker: function (position, accuracy) {
            var self = this,
                olmap = this.map.map.olMap,
                markers,
                icon,
                candidates = olmap.getLayersByName('Markers'),

                vector,
                metersProj,
                currentProj,
                originInMeters,
                accuracyPoint,
                differance,
                circle;
            if (candidates.length > 0) {
                markers = candidates[0];
                olmap.removeLayer(markers);
                markers.destroy();
            }

            markers = new OpenLayers.Layer.Vector('Markers');
            var point = new OpenLayers.Feature.Vector(new OpenLayers.Geometry.Point(position.lon, position.lat), null, {
                strokeColor:   "#ff0000",
                strokeWidth:   3,
                strokeOpacity: 1,
                strokeLinecap: "butt",
                fillOpacity:   0,
                pointRadius:   10
            });
            markers.addFeatures([point]);
            olmap.addLayer(markers);

            // Accurancy
            if (!accuracy) {
                return;
            }
            candidates = olmap.getLayersByName('Accuracy');
            if (candidates.length > 0) {
                olmap.removeLayer(candidates[0]);
                candidates[0].destroy();
            }
            vector = new OpenLayers.Layer.Vector('Accuracy');
            olmap.addLayer(vector);

            metersProj = new OpenLayers.Projection('EPSG:900913');
            currentProj = olmap.getProjectionObject();

            originInMeters = new OpenLayers.LonLat(position.lon, position.lat);
            originInMeters.transform(currentProj, metersProj);

            accuracyPoint = new OpenLayers.LonLat(originInMeters.lon + (accuracy / 2), originInMeters.lat + (accuracy / 2));
            accuracyPoint.transform(metersProj, currentProj);

            differance = accuracyPoint.lon - position.lon;

            circle = new OpenLayers.Feature.Vector(
                OpenLayers.Geometry.Polygon.createRegularPolygon(

                    new OpenLayers.Geometry.Point(position.lon, position.lat),
                    differance,
                    40,
                    0
                ),
                {},
                self.options.accurancyStyle
            );
            vector.addFeatures([circle]);
        },

        _centerMap: function (point) {
            var olmap = this.map.map.olMap,
                extent = olmap.getExtent();
            if (extent.containsLonLat(point) === false || true === this.options.follow) {
                olmap.panTo(point);
            } else if (this.firstPosition && this.options.centerOnFirstPosition) {
                olmap.panTo(point);
            }
        },

        _zoomMap: function (point, accuracy) {
            if (!accuracy) {
                return; // no accurancy
            }
            if (!this.options.zoomToAccuracy && !(this.options.zoomToAccuracyOnFirstPosition && this.firstPosition)) {
                return;
            }

            var olmap = this.map.map.olMap,
                metersProj = new OpenLayers.Projection("EPSG:900913"),
                currentProj = olmap.getProjectionObject(),
                pointInMeters = point.transform(currentProj, metersProj),
                min = new OpenLayers.LonLat(pointInMeters.lon - (accuracy / 2), pointInMeters.lat - (accuracy / 2)).transform(metersProj, currentProj),
                max = new OpenLayers.LonLat(pointInMeters.lon + (accuracy / 2), pointInMeters.lat + (accuracy / 2)).transform(metersProj, currentProj);

            olmap.zoomToExtent(new OpenLayers.Bounds(min.lon, min.lat, max.lon, max.lat));
        },

        /**
         * Is button active?
         */
        isActive: function() {
            var widget = this;
            return widget.observer != null;
        },

        /**
         * Toggle GPS positioning
         *
         * @returns {self}
         */
        toggleTracking: function () {
            var widget = this;
            if (widget.isActive()) {
                return widget.deactivate();
            }
            return widget.activate();
        },
        numberWithCommas: function (x) {
            var parts = x.toString().split('.');
            parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');
            return parts.join('.');
        },
        /**
         * Activate GPS positioning
         *
         * @returns {self}
         */
        activate: function () {
                    
            if (this.options.laivMode) {
                var utmString = "";
                var gpsString = "";
            } else {
                var utmString = " (ETRS89/UTM-33N)";
                var gpsString = " (WGS84/Geographische Koordinaten)";
            }

            $("#content").append("<span id='mobileCoordinatesDisplay' class='mb-element mb-element-coordsdisplay center' style='top:20px;left:15px;font-size:12px;'><span class='iconCoordinates' id='coordinatesdisplay'></span><span id='mobileCoordinatesDisplayText'></span><span style='font-size:5px;'>" + utmString + "</span></span>");
            $("#content").append("<span id='mobileCoordinatesDisplayGPS' class='mb-element mb-element-coordsdisplay center' style='top:35px;left:15px;font-size:12px;'><span class='iconCoordinates' id='coordinatesdisplay'></span><span id='mobileCoordinatesDisplayGPSText'></span><span style='font-size:5px;'>" + gpsString + ")</span></span>");
          
            var widget = this;
            var olmap = widget.map.map.olMap;
            if (navigator.geolocation) {
                widget.observer = navigator.geolocation.watchPosition(function success(position) {
                    var proj = new OpenLayers.Projection("EPSG:4326"),
                        newProj = olmap.getProjectionObject(),
                        p = new OpenLayers.LonLat(position.coords.longitude, position.coords.latitude);

                    p.transform(proj, newProj);

                    // Averaging: Building a queue...
                    widget.stack.push(p);
                    if (widget.stack.length > widget.options.average) {
                        widget.stack.splice(0, 1);
                    }

                    // ...and reducing it.
                    p = _.reduce(widget.stack, function (memo, p) {
                        memo.lon += p.lon / widget.stack.length;
                        memo.lat += p.lat / widget.stack.length;
                        return memo;
                    }, new OpenLayers.LonLat(0, 0));

                    widget._createMarker(p, position.coords.accuracy);
                    widget._centerMap(p);
                    widget._zoomMap(p, position.coords.accuracy);

                    if (widget.options.laivMode) {
                        Proj4js.defs["EPSG:25832"] = "+proj=utm +zone=32 +ellps=GRS80 +towgs84=0,0,0,0,0,0,0 +units=m +no_defs";
                        var proj25832 = new OpenLayers.Projection("EPSG:25832");
                        p.transform(newProj, proj);
                        p.transform(proj, proj25832);
                        var x = widget.numberWithCommas(Math.round(p.lon * 1000) / 1000);
                        var y = widget.numberWithCommas(Math.round(p.lat * 1000) / 1000);
                        x = x.replace('.', 'x');
                        x = x.replace(/,/g, '.');
                        x = x.replace('x', ',');
                        x = 'Zone 32U ' + x;
                        y = y.replace('.', 'x');
                        y = y.replace(/,/g, '.');
                        y = y.replace('x', ',');
                    } else {
                        var x = Math.round(p.lon);
                        var y = Math.round(p.lat);
                    }
                    
                    $('#mobileCoordinatesDisplayText').text(x + ' m | ' + y + ' m');

                    if (widget.options.laivMode) {
                        p.transform(proj25832, proj);
                        var ln = Math.round(p.lon * 10000000) / 10000000;
                        var lt = Math.round(p.lat * 10000000) / 10000000;
                        var ln_deg = Math.trunc(ln);
                        var lt_deg = Math.trunc(lt);
                        var ln_min = (ln - ln_deg) * 60;
                        var lt_min = (lt - lt_deg) * 60;
                        var ln_sec = Math.round(((ln_min - Math.floor(ln_min)) * 60) * 100000) / 100000;
                        var lt_sec = Math.round(((lt_min - Math.floor(lt_min)) * 60) * 100000) / 100000;
                        var ln_sec_dec = (ln_sec - Math.floor(ln_sec)) * 100000;
                        var lt_sec_dec = (lt_sec - Math.floor(lt_sec)) * 100000;
                        ln = ln_deg + '°' + Math.floor(ln_min).toString().padStart(2, '0') + '′' + Math.floor(ln_sec).toString().padStart(2, '0') + ',' + Math.round(ln_sec_dec) + '′′';
                        lt = lt_deg + '°' + Math.floor(lt_min).toString().padStart(2, '0') + '′' + Math.floor(lt_sec).toString().padStart(2, '0') + ',' + Math.round(lt_sec_dec) + '′′';
                    } else {
                        p.transform(newProj, proj);
                        var ln = Math.round(p.lon * 100000) / 100000;
                        var lt = Math.round(p.lat * 100000) / 100000;
                        ln = ln.toString().replace('.', ',') + '°';
                        lt = lt.toString().replace('.', ',') + '°';
                    }
                    
                    $('#mobileCoordinatesDisplayGPSText').text(ln + ' | ' + lt);

                    if (widget.firstPosition) {
                        widget.firstPosition = false;
                    }


                }, function error(msg) {
                    Mapbender.error("Es ist nicht möglich Ihre Position zu bestimmen.");
                    widget.deactivate();
                }, { enableHighAccuracy: true, maximumAge: 0 });

                $(widget.element).parent().addClass("toolBarItemActive");

            } else {
                Mapbender.error(Mapbender.trans("mb.core.gpsposition.error.notsupported"));
            }
            return widget;
        },
        /**
         * Deactivate GPS positioning
         *
         * @param
         * @returns {self}
         */
        deactivate: function() {
            if(this.isActive()) {
                navigator.geolocation.clearWatch(this.observer);
                $(this.element).parent().removeClass("toolBarItemActive");
                this.firstPosition = true;
                this.observer = null;
            }
            // Delete Markers
            var olmap = this.map.map.olMap,
                markers,
                candidates = olmap.getLayersByName('Markers');
            if (candidates.length > 0) {
                markers = candidates[0];
                olmap.removeLayer(markers);
                markers.destroy();
            }

            candidates = olmap.getLayersByName('Accuracy');
            if (candidates.length > 0) {
                olmap.removeLayer(candidates[0]);
                candidates[0].destroy();
            }
            
            if ($('#mobileCoordinatesDisplay').length) {
                $('#mobileCoordinatesDisplay').remove();
            }
            
            if ($('#mobileCoordinatesDisplayGPS').length) {
                $('#mobileCoordinatesDisplayGPS').remove();
            }
            
            return this;
        },
        /**
         * Determinate ready state of plugin
         *
         * @param {mapbender.mbGpsPosition~readyCallback} callback - Callback to run on plugin ready
         * @returns {self}
         */
        ready: function (callback) {
            if (this.readyState === true) {
                /**
                 * Description of what this does.
                 *
                 * @callback mapbender.mbGpsPosition~readyCallback
                 * @param
                 */
                callback();
            } else {
                this.readyCallbacks.push(callback);
            }
            return this;
        },
        _ready: function () {
            var i;
            for (i = 0; i <  this.readyCallbacks.length; i += 1) {
                this.readyCallbacks.splice(0, 1)();
            }
            this.readyState = true;
        }
    });

}(jQuery));
