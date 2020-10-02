(function($){

    $.widget("mapbender.mbRuler", {
        options: {
            target: null,
            click: undefined,
            icon: undefined,
            label: true,
            group: undefined,
            immediate: true,
            persist: true,
            type: 'line',
            precision: 2
        },
        clicks: 0,
        control: null,
        map: null,
        segments: null,
        container: null,
        popup: null,
        _create: function(){
            var self = this;
            if(this.options.type !== 'line' && this.options.type !== 'area'){
                throw Mapbender.trans("mb.core.ruler.create_error");
            }
            if(!Mapbender.checkTarget("mbRuler", this.options.target)){
                return;
            }

            Mapbender.elementRegistry.onElementReady(this.options.target, $.proxy(self._setup, self));
        },
        /**
         * Initializes the overview
         */
        _setup: function(){
            var sm = $.extend(true, {}, OpenLayers.Feature.Vector.style, {
                'default': this.options.style
            });
            var styleMap = new OpenLayers.StyleMap(sm);

            var handler = (this.options.type === 'line' ? OpenLayers.Handler.Path :
                    OpenLayers.Handler.Polygon);

            this.control = new OpenLayers.Control.Measure(handler, {
                callbacks: {
                    modify: function(point, feature, drawing){
                        // Monkey patching, so modify uses a different event than
                        // the point handler. Sad, but true.
                        if(drawing && this.delayedTrigger === null &&
                                !this.handler.freehandMode(this.handler.evt)){
                            this.measure(feature.geometry, "measuremodify");
                        }
                    }
                },
                // This, too, is part of the monkey patch - unregistered event
                // types wont fire
                EVENT_TYPES: OpenLayers.Events.prototype.BROWSER_EVENTS
                        .concat(['measuremodify']),
                handlerOptions: {
                    layerOptions: {
                        styleMap: styleMap,
                        name: 'rulerlayer'
                    }
                },
                persist: this.options.persist,
                immediate: this.options.immediate
            });

            this.control.events.on({
                'scope': this,
                'measure': this._handleFinal,
                'measurepartial': this._handlePartial,
                'measuremodify': this._handleModify
            });

            this.map = $('#' + this.options.target);

            this.container = $('<div/>');
            this.segments = $('<ul/>');
            
            this.segments.addClass('flipped-list');
            this.segments.appendTo(this.container);

            this._trigger('ready');
            this._ready();
        },
        /**
         * Default action for mapbender element
         */
        defaultAction: function(callback){
            this.activate(callback);
        },
        /**
         * This activates this button and will be called on click
         */
        activate: function(callback){
            this.callback = callback ? callback : null;
            var self = this,
                    olMap = this.map.data('mapQuery').olMap;
            olMap.addControl(this.control);
            this.control.activate();

            this._reset();
            if(!this.popup || !this.popup.$element){
                this.popup = new Mapbender.Popup2({
                    title: self.element.attr('title'),
                    modal: false,
                    draggable: true,
                    resizable: true,
                    closeButton: false,
                    closeOnESC: true,
                    destroyOnClose: true,
                    content: self.container,
                    width: 300,
                    height: 300,
                    buttons: {
                        'ok': {
                            label: Mapbender.trans("mb.core.ruler.popup.btn.ok"),
                            cssClass: 'button right',
                            callback: function(){
                                self.deactivate();
                            }
                        }
                    }
                });
                this.popup.$element.on('close', $.proxy(this.deactivate, this));
            }else{
                this.popup.open("");
            }

            (this.options.type === 'line') ?
                    $("#linerulerButton").parent().addClass("toolBarItemActive") :
                    $("#arearulerButton").parent().addClass("toolBarItemActive");
        },
        /**
         * This deactivates this button and will be called if another button of
         * this group is activated.
         */
        deactivate: function(){
            this.container.detach();
            var olMap = this.map.data('mapQuery').olMap;
            this.control.deactivate();
            olMap.removeControl(this.control);
            $("#linerulerButton, #arearulerButton").parent().removeClass("toolBarItemActive");
            if(this.popup && this.popup.$element){
                this.popup.destroy();
            }
            this.popup = null;
            this.callback ? this.callback.call() : this.callback = null;
        },
        _reset: function(){
            this.segments.empty();
            this.clicks = 0;
        },
        _handleModify: function(event){
            if (event.measure === 0.0) {
                return;
            }
            
            var measure = this._getMeasureFromEvent(event);
            
            if ($('body').data('mapbenderMbPopup')) {
                $("body").mbPopup('setContent', measure);
            }
            
            if (this.options.type === 'area') {
                this.segments.html($('<li/>', { html: measure }));
            } else if (this.options.type === 'line') {
                this.segments.find('li:last-child').html('Länge bis Stützpunkt ' + (this.clicks + 1) + ': ' + measure);
            }
        },
        _handlePartial: function(event){
            if (event.measure === 0) {// if first point
                this._reset();
                this.segments.append($('<li/>'));
                return;
            }
            
            this.clicks++;

            var measure = this._getMeasureFromEvent(event);
            
            if (this.options.type === 'area') {
                this.segments.html($('<li/>', { html: measure }));
            } else if (this.options.type === 'line') {
                this.segments.append($('<li/>', { html: measure }));
            }
        },
        _handleFinal: function(event){
            var measure = this._getMeasureFromEvent(event);
            if (this.options.type === 'line') {
                var value = this.segments.find('li:last-child');
                var value_text = value.text();
                value_text = value_text.substring(value_text.lastIndexOf(':') + 2);
                value.text(value_text);
                value.wrapInner('<b>Gesamtlänge: <b/>');
            } else {
                this.segments.find('li:last-child').wrapInner('<b><b/>');
            }
            this.clicks = 0;
        },
        _getMeasureFromEvent: function(event){
            var measure = event.measure,
                  units = event.units,
                  order = event.order;

            measure = (measure.toFixed(this.options.precision)).replace('.', ',') + " " + units;
            if (order > 1) {
                var length = event.geometry.getLength();
                if (length >= 1000) {
                    length = ((length / 1000).toFixed(this.options.precision)).replace('.', ',') + " " + "km";
                } else {
                    length = (length.toFixed(this.options.precision)).replace('.', ',') + " " + "m";
                }
                measure += "<sup>" + order + "</sup> (Umfang: " + length + ")";
            }

            return measure;
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
        }
    });

})(jQuery);
