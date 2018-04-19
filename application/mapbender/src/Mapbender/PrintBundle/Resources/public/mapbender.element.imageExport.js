(function($){

    $.widget("mapbender.mbImageExport", {
        options: {},
        map: null,
        popupIsOpen: true,
        _create: function(){
            if(!Mapbender.checkTarget("mbImageExport", this.options.target)){
                return;
            }
            var self = this;
            var me = this.element;
            this.elementUrl = Mapbender.configuration.application.urls.element + '/' + me.attr('id') + '/';
            Mapbender.elementRegistry.onElementReady(this.options.target, $.proxy(self._setup, self));
        },
        _setup: function(){
            this.map = $('#' + this.options.target).data('mapbenderMbMap');

            this._trigger('ready');
            this._ready();
        },
        defaultAction: function(callback){
            this.open(callback);
        },
        open: function(callback){
            this.callback = callback ? callback : null;
            var self = this;
            var me = $(this.element);
            this.elementUrl = Mapbender.configuration.application.urls.element + '/' + me.attr('id') + '/';
            if(!this.popup || !this.popup.$element){
                this.popup = new Mapbender.Popup2({
                    title: self.element.attr('title'),
                    draggable: true,
                    header: true,
                    modal: false,
                    closeButton: false,
                    closeOnESC: false,
                    content: self.element,
                    width: 250,
                    buttons: {
                        'cancel': {
                            label: Mapbender.trans("mb.print.imageexport.popup.btn.cancel"),
                            cssClass: 'button buttonCancel critical right',
                            callback: function(){
                                self.close();
                            }
                        },
                        'ok': {
                            label: Mapbender.trans("mb.print.imageexport.popup.btn.ok"),
                            cssClass: 'button right',
                            callback: function(){
                                self._exportImage();
                                self.close();
                            }
                        }
                    }
                });
                this.popup.$element.on('close', $.proxy(this.close, this));
            }else{
                if(this.popupIsOpen === false){
                    this.popup.open(self.element);
                }
            }
            me.show();
            this.popupIsOpen = true;
        },
        close: function(){
            if(this.popup){
                this.element.hide().appendTo($('body'));
                this.popupIsOpen = false;
                if(this.popup.$element){
                    this.popup.destroy();
                }
                this.popup = null;
            }
            this.callback ? this.callback.call() : this.callback = null;
        },
        /**
         *
         */
        ready: function(callback){
            if(this.readyState === true){
                callback();
            }else{
                this.readyCallbacks.push(callback);
            }
        },
        /**
         *
         */
        _ready: function(){
            for(callback in this.readyCallbacks){
                callback();
                delete(this.readyCallbacks[callback]);
            }
            this.readyState = true;
        },
        _exportImage: function(){
            var self = this;
            var sources = this.map.getSourceTree(), num = 0;
            var layers = new Array();
            var imageSize = this.map.map.olMap.getCurrentSize();
            
            // Array anlegen zur Sicherstellung der korrekten Layer-Reihenfolge im Bildexport
            var layerConfsWithCalculatedWeights = [];
            
            for(var i = 0; i < sources.length; i++){
                var layer = this.map.map.layersList[sources[i].mqlid];

                if(layer.olLayer.params.LAYERS.length === 0){
                    continue;
                }

                if(Mapbender.source[sources[i].type] && typeof Mapbender.source[sources[i].type].getPrintConfig === 'function'){
                    // Korrektur der Bildgröße bei WMS (ansonsten werden WIDTH und HEIGHT des WMS-GetMap-Aufrufs fälschlicherweise jeweils mit dem Bbox-Faktor (der Ratio) des WMS multipliziert!)
                    if (sources[i].type === 'wms') {
                        var layerConf = Mapbender.source['wms'].getPrintConfig(layer.olLayer, this.map.map.olMap.getExtent(), null, sources[i].configuration.options.proxy, imageSize);
                    } else {
                        var layerConf = Mapbender.source[sources[i].type].getPrintConfig(layer.olLayer, this.map.map.olMap.getExtent(), sources[i].configuration.options.proxy);
                    }
                    layerConf.opacity = sources[i].configuration.options.opacity;
                        
                    // Wichtung des Layers auf 0 setzen
                    var calculatedWeight = 0;
                        
                    // falls Layer keine Hintergrundkarte ist...
                    if (!sources[i].configuration.isBaseSource) {
                        // ...Wichtung des Layers auf Position in OpenLayers setzen...
                        var mqLayer = this.map.map.layersList[sources[i].mqlid];
                        calculatedWeight = mqLayer.position();
                        // ...Array zur Sicherstellung der korrekten Layer-Reihenfolge im Bildexport befüllen mit Layer und dessen Wichtung
                        layerConfsWithCalculatedWeights.push(layerConf, calculatedWeight);
                    // ansonsten...
                    } else {
                        // ...Layer ablegen
                        layers[num] = layerConf;
                        num++;
                    }
                }
            }
            
            // falls Array zur Sicherstellung der korrekten Layer-Reihenfolge im Bildexport nicht immer noch leer ist...
            if (layerConfsWithCalculatedWeights.length > 0) {
            
                // ...Array zur Sicherstellung der korrekten Layer-Reihenfolge nach Wichtung sortieren (also nach dem 2., 4., 6. Wert usw.)...
                var groupSize = 2;    
                var tempArray = [];
                while((sec = layerConfsWithCalculatedWeights.splice(0, groupSize)).length > 0) {
                    tempArray.push(sec);
                }
                tempArray.sort();
                layerConfsWithCalculatedWeights = [];
                for(var i in tempArray) {
                    for(var j in tempArray[i]) {
                        layerConfsWithCalculatedWeights.push(tempArray[i][j]);
                    }
                }
                
                // ...sortiertes Array zur Sicherstellung der korrekten Layer-Reihenfolge durchlaufen und jeweiligen Layer ablegen
                for (var j = 0; j < layerConfsWithCalculatedWeights.length; j++) {
                    if (j % 2 == 0) {
                        layers[num] = layerConfsWithCalculatedWeights[j];
                        num++;
                    }
                }
                
            }
            
            var vectorLayers = [];

            // Iterating over all vector layers, not only the ones known to MapQuery
            var geojsonFormat = new OpenLayers.Format.GeoJSON();
            for(var i = 0; i < this.map.map.olMap.layers.length; i++) {
                var layer = this.map.map.olMap.layers[i];
                if('OpenLayers.Layer.Vector' !== layer.CLASS_NAME || this.layer === layer) {
                    continue;
                }

                var geometries = [];
                for(var idx = 0; idx < layer.features.length; idx++) {
                    var feature = layer.features[idx];
                    if (!feature.onScreen(true)) continue


                        var geometry = geojsonFormat.extract.geometry.apply(geojsonFormat, [feature.geometry]);

                        if(feature.style !== null){
                            geometry.style = feature.style;
                        }else{
                            geometry.style = layer.styleMap.createSymbolizer(feature,feature.renderIntent);
                        }
                        // only visible features
                        if(geometry.style.fillOpacity > 0 && geometry.style.strokeOpacity > 0){
                            geometries.push(geometry);
                        } else if (geometry.style.label !== undefined){
                            geometries.push(geometry);
                        }
                }

                var lyrConf = {
                    type: 'GeoJSON+Style',
                    opacity: 1,
                    geometries: geometries
                };


                vectorLayers.push(JSON.stringify(lyrConf))
            }
            
            // Markers
            var geojsonFormat = new OpenLayers.Format.GeoJSON();
            var markerStyleMap = new OpenLayers.StyleMap({ 
                'default': {
                    fillColor: "#780046",
                    fillOpacity: 1,
                    strokeColor: "#780046",
                    strokeOpacity: 1,
                    strokeWidth: "0",
                    pointRadius: "10"
                }
            });
            for(var i = 0; i < this.map.map.olMap.layers.length; i++) {
                var layer = this.map.map.olMap.layers[i];
                if('OpenLayers.Layer.Markers' !== layer.CLASS_NAME || this.layer === layer) {
                    continue;
                }
                
                var points = [];
                for(var idy = 0; idy < layer.markers.length; idy++) {
                    var marker = layer.markers[idy];
                    var point = new OpenLayers.Geometry.Point(marker.lonlat.lon, marker.lonlat.lat);
                    
                    var geometry = geojsonFormat.extract.geometry.apply(geojsonFormat, [point]);
                    feature = new OpenLayers.Feature.Vector(point);
                    geometry.style = markerStyleMap.createSymbolizer(feature,'default');
                    points.push(geometry);
                }

                var lyrConf = {
                    type: 'GeoJSON+Style',
                    opacity: 1,
                    geometries: points
                };

                vectorLayers.push(JSON.stringify(lyrConf))
            }
            
            // Pop-ups
            var geojsonFormat = new OpenLayers.Format.GeoJSON();
            var popupStyleMap = new OpenLayers.StyleMap({ 
                'default': {
                    fillColor: "#780046",
                    fillOpacity: 0,
                    strokeColor: "#780046",
                    strokeOpacity: 0,
                    strokeWidth: "0",
                    pointRadius: "0",
                    display: "popup"
                }
            });
            var points = [];
            for(var i = 0; i < this.map.map.olMap.popups.length; i++) {
                var popup = this.map.map.olMap.popups[i];

                var point = new OpenLayers.Geometry.Point(popup.lonlat.lon, popup.lonlat.lat);
                
                var geometry = geojsonFormat.extract.geometry.apply(geojsonFormat, [point]);
                feature = new OpenLayers.Feature.Vector(point);
                geometry.style = popupStyleMap.createSymbolizer(feature,'default');
                geometry.style.label = popup.contentHTML;
                points.push(geometry);

                var lyrConf = {
                    type: 'GeoJSON+Style',
                    opacity: 1,
                    geometries: points
                };

                vectorLayers.push(JSON.stringify(lyrConf))
            }

            var mapExtent = this.map.map.olMap.getExtent();

            if (num === 0) {
                Mapbender.info(Mapbender.trans("mb.print.imageexport.info.noactivelayer"));
            } else {
                $('#imageexport-loading-modal').modal('show');
                var url = Mapbender.configuration.application.urls.element + '/' + this.element.attr('id') + '/export';
                var format = $("input[name='imageformat']:checked").val();
                var data = {
                    scale: this.map.map.olMap.getScale(),
                    bbox: mapExtent.toArray(),
                    requests: layers,
                    format: format,
                    width: imageSize.w,
                    height: imageSize.h,
                    centerx: mapExtent.getCenterLonLat().lon,
                    centery: mapExtent.getCenterLonLat().lat,
                    extentwidth: mapExtent.getWidth(),
                    extentheight: mapExtent.getHeight(),
                    vectorLayers: vectorLayers
                };
                var form = $('<form/>');
                $('<input></input>').attr('type', 'hidden').attr('name', 'data').val(JSON.stringify(data)).appendTo(form);
                var formVars = form.serialize();
                form.remove();
                var xhr = new XMLHttpRequest();
                xhr.open('POST', url, true);
                xhr.responseType = 'arraybuffer';
                xhr.onload = function () {
                    $('#imageexport-loading-modal').modal('hide');
                    if (this.status === 200) {
                        var filename = '';
                        var disposition = xhr.getResponseHeader('Content-Disposition');
                        if (disposition && disposition.indexOf('attachment') !== -1) {
                            var filenameRegex = /filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/;
                            var matches = filenameRegex.exec(disposition);
                            if (matches != null && matches[1]) filename = matches[1].replace(/['"]/g, '');
                        }
                        var type = xhr.getResponseHeader('Content-Type');
                        var blob = typeof File === 'function'
                            ? new File([this.response], filename, { type: type })
                            : new Blob([this.response], { type: type });
                        if (typeof window.navigator.msSaveOrOpenBlob !== 'undefined') {
                            window.navigator.msSaveOrOpenBlob(blob, filename);
                        } else {
                            var URL = window.URL || window.webkitURL;
                            var downloadUrl = URL.createObjectURL(blob);
                            if (filename) {
                                var a = document.createElement('a');
                                if (typeof a.download === 'undefined') {
                                    window.location = downloadUrl;
                                } else {
                                    a.href = downloadUrl;
                                    a.download = filename;
                                    document.body.appendChild(a);
                                    a.click();
                                }
                            } else {
                                window.location = downloadUrl;
                            }
                            setTimeout(function () { URL.revokeObjectURL(downloadUrl); }, 100);
                        }
                    }
                };
                xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
                xhr.send(formVars);
            }
        }

    });

})(jQuery);
