(function($) {

    $.widget("mapbender.mbAlkisInfo", {
        options: {
            dataSrs: 'EPSG:25833',
            spatialSearchSrs: 'EPSG:4326',
            type: 'dialog'
        },
        _create: function() {
            if (!Mapbender.checkTarget("mbAlkisInfo", this.options.target)) {
                return;
            }
            Mapbender.elementRegistry.onElementReady(this.options.target, $.proxy(this._setup, this));
        },
        _setup: function() {
            this.target = $("#" + this.options.target).data("mapbenderMbMap");//.getModel();
            //
            this.dataSrsProj = this.target.getModel().getProj(this.options.dataSrs);
            this.spatialSearchSrsProj = this.target.getModel().getProj(this.options.spatialSearchSrs);
            this.elementUrl = Mapbender.configuration.application.urls.element + '/' + this.element.attr('id') + '/';
            this.mapClickHandler = new OpenLayers.Handler.Click(this, {
                'click': this._triggerAlkisInfo
            },
            {
                map: $('#' + this.options.target).data('mapQuery').olMap
            });
            this.mapClickHandler.pixelTolerance = 30;
            if (this.options.autoActivate)
                this.activate();
            this._trigger('ready');
            this._ready();
        },
        /**
         * Default action for mapbender element
         */
        defaultAction: function(callback) {
            this.activate(callback);
        },
        activate: function(callback) {
            this.callback = callback ? callback : null;
            $('#' + this.options.target).addClass('mb-alkis-info-active');
            this.mapClickHandler.activate();
        },
        activateEigenSearchInfo: function(gmlId) {
            var self = this;
            $.ajax({
                url: Mapbender.configuration.application.urls.element + '/' + this.element.attr('id') + '/search',
                type: 'POST',
                data: {
                    gmlid: gmlId
                },
                dataType: 'text',
                context: self,
                success: this._successEigenSearchInfo,
                error: this._findError
            });
            return false;
        },
        activateGrundSearchInfo: function(gmlId) {
            var self = this;
            $.ajax({
                url: Mapbender.configuration.application.urls.element + '/' + this.element.attr('id') + '/search',
                type: 'POST',
                data: {
                    gmlid: gmlId
                },
                dataType: 'text',
                context: self,
                success: this._successGrundSearchInfo,
                error: this._findError
            });
            return false;
        },
        _successEigenSearchInfo: function(response, textStatus, jqXHR) {
            var result = JSON.parse(response);
            var tip = JSON.parse(result.documents[0].json);

            tip.gmlid = tip.gml_id;// ? tip.gml_id : 'DEMVAL03Z0000eil';
             
            var iframe = this._getIframeDeclaration(Mapbender.Util.UUID(),
            this.options.infourleigen + '?gmlid=' + tip.gmlid);
            this._getContext().html(iframe);
            $('#' + this.options.target).addClass('mb-alkis-info-active');
        },
        _successGrundSearchInfo: function(response, textStatus, jqXHR) {
            var result = JSON.parse(response);
            var tip = JSON.parse(result.documents[0].json);

            tip.gmlid = tip.gml_id;// ? tip.gml_id : 'DEMVAL03Z0000eil';
             
            var iframe = this._getIframeDeclaration(Mapbender.Util.UUID(),
            this.options.infourlgrund + '?gmlid=' + tip.gmlid);
            this._getContext().html(iframe);
            $('#' + this.options.target).addClass('mb-alkis-info-active');
        },
        deactivate: function() {
            $('#' + this.options.target).removeClass('mb-alkis-info-active');
            $(".toolBarItemActive").removeClass("toolBarItemActive");
            if (this.popup) {
                if (this.popup.$element) {
                    $('body').append(this.element.addClass('hidden'));
                    this.popup.destroy();
                }
                this.popup = null;
            }
            this.mapClickHandler.deactivate();
            this.callback ? this.callback.call() : this.callback = null;
        },
        _triggerAlkisInfo: function(e) {
            var self = this;
            var lonlat = this.target.map.olMap.getLonLatFromPixel(new OpenLayers.Pixel(e.xy.x, e.xy.y));
            var mapProj = this.target.getModel().getCurrentProj();
            this.clickPoint = new OpenLayers.Geometry.Point(lonlat.lon, lonlat.lat);
            var pointWgs84 = this.clickPoint.clone().transform(mapProj, this.spatialSearchSrsProj);
            if (this.dataSrsProj.projCode !== mapProj.projCode) {
                this.clickPoint = this.clickPoint.transform(mapProj, this.dataSrsProj);
            }
            $.ajax({
                url: Mapbender.configuration.application.urls.element + '/' + this.element.attr('id') + '/search',
                type: 'POST',
                data: {
                    x: lonlat.lon,
                    y: lonlat.lat
                },
                dataType: 'text',
                context: self,
                success: this._findSuccess,
                error: this._findError
            });
            return false;
        },
        _findSuccess: function(response, textStatus, jqXHR) {
            var result = JSON.parse(response);
            if (result) {
                var iframe = this._getIframeDeclaration(Mapbender.Util.UUID(), this.options.infourl + '?gmlid=' + result.properties.id_fachsystem);
                this._getContext().html(iframe);
            } else {
                Mapbender.info('ALKIS-Auskunft: kein Abfrageresultat');
            }
        },
        _findError: function(response) {
            Mapbender.error(response);
        },
        _getIframeDeclaration: function(uuid, url) {
            var id = uuid ? (' id="' + uuid + '"') : '';
            var src = url ? (' src="' + url + '"') : '';
            return '<iframe class="alkisInfoFrame"' + id + src + '></iframe>';
        },
        _getContext: function() {
            var self = this;
            if (this.options.type === 'dialog') {
                if (!this.popup || !this.popup.$element) {
                    this.popup = new Mapbender.Popup2({
                        title: self.element.attr('data-title'),
                        draggable: true,
                        modal: false,
                        closeButton: false,
                        closeOnESC: false,
                        content: this.element.removeClass('hidden'),
                        resizable: true,
                        cssClass: 'alkisDialog',
                        width: 700,
                        height: 580,
                        buttons: {
                            'ok': {
                                label: Mapbender.trans('Schließen'),
                                cssClass: 'button buttonCancel critical right',
                                callback: function() {
                                    this.close();
                                    if (self.options.deactivateOnClose) {
                                        self.deactivate();
                                    }
                                }
                            }
                        }
                    });
                    this.popup.$element.on('close', function() {
                        self.state = 'closed';
                        if (self.options.deactivateOnClose) {
                            self.deactivate();
                        }
                        if (self.popup && self.popup.$element) {
                            self.popup.$element.hide();
                        }
                    });
                    this.popup.$element.on('open', function() {
                        self.state = 'opened';
                    });
                }
                if(self.state !== 'opened') {
                    this.popup.open();
                }
            }
            return this.element;
        },
        /**
         *
         */
        ready: function(callback) {
            if (this.readyState === true) {
                callback();
            } else {
                this.readyCallbacks.push(callback);
            }
        },
        /**
         *
         */
        _ready: function() {
            for (callback in this.readyCallbacks) {
                callback();
                delete(this.readyCallbacks[callback]);
            }
            this.readyState = true;
        },
        _destroy: $.noop
    });
})(jQuery);
