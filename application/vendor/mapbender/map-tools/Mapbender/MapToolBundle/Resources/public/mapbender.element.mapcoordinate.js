
(function($) {
    function readCoords(coordsText) {
        // string for coordinates input int/float values seaprated with a blank
        coordsText = coordsText.replace(new RegExp('\,', 'g'), '.')
        var regex = new RegExp(/^(-?[0-9]+([.][0-9]+([eE][0-9]+)?)?\s-?[0-9]+([.][0-9]+([eE][0-9]+)?)?)+$/);
        return regex.exec(coordsText);
    }
    function GetCoordinateModel(mbMap, $inputCoords, $inputSrs, $resultCoords, $resultSrs, $button, $container) {
        this.activated = false;
        this.mapClickHandler = null;
        this.feature = null;
        this.getName = function(){
            return "GetCoordinateModel";
        };
        this.activate = function(e) {
            mbMap.element.addClass('crosshair');
            $inputCoords.val('');
            $resultCoords.val('');
            $button.removeClass('disabled').on('click', $.proxy(this.clickButton, this));
            $inputSrs.on('change', $.proxy(this.srsChanged, this));
            this.activated = true;
            this.clickButton(e);
        };
        this.clickButton = function(e) {
            if(!this.mapClickHandler) {
                this.mapClickHandler = new OpenLayers.Handler.Click(this,
                    {'click': this._handleClick},
                    {map: mbMap.olMap}
                );
            }
            this.mapClickHandler.activate();
        };
        this.srsChanged = function(e) {
            var data = readCoords($resultCoords.val());
            if (data && data[0]) {
                this.setResult();
            }
        };
        this.mapSrsChanged = function() {
            $inputCoords.val('');
            $resultCoords.val('');
        };
        this.setResult = function() {
            var data = readCoords($resultCoords.val());
            if (data && data[0]) {
                var ordinates = data[0].split(/\s/);
                var selectedSrsName = $('select.inputSrs', $container).next().text();
                var inputSrs = Mapbender.Model.getProj($('select.inputSrs option').filter(function () { return $(this).html() == selectedSrsName; }).val());
                var mapProj = Mapbender.Model.getCurrentProj();
                if(this.feature) {
                    Mapbender.Model.highlightOff(this.feature);
                }
                var point = new OpenLayers.Geometry.Point(ordinates[0], ordinates[1]);
                var poi = {
                    position: new OpenLayers.LonLat(point.x, point.y),
                    label: ""
                };
                var newFeature = new OpenLayers.Feature.Vector(point, poi);
                newFeature.style = OpenLayers.Util.applyDefaults({ 'strokeColor': '#cc00ff', 'fillColor': '#cc00ff', 'fillOpacity': '0.1', 'strokeWidth': '3', 'pointRadius': '10' }, OpenLayers.Feature.Vector.style["default"]); 
                this.feature = [newFeature];
                Mapbender.Model.highlightOn(this.feature, {clearFirst: true, "goto": false});
                if (inputSrs.projCode !== mapProj.projCode) {
                    point = point.transform(mapProj, inputSrs);
                }
                var x = parseFloat(point.x);
                var y = parseFloat(point.y);
                if (inputSrs.proj.units === 'degrees') {
                    x = Math.round(x * 100000) / 100000.0;
                    y = Math.round(y * 100000) / 100000.0;
                } else {
                    x = Math.round(x * 1000) / 1000.0;
                    y = Math.round(y * 1000) / 1000.0;
                }
                $inputCoords.val((x.toString()).replace('.', ',') + ' ' + (y.toString()).replace('.', ','));
            }
        };
        this._handleClick = function(e){
            var lonlat = mbMap.olMap.getLonLatFromPixel(e.xy);
            $resultCoords.val(((parseFloat(lonlat.lon)).toString()).replace('.', ',') + ' ' + ((parseFloat(lonlat.lat)).toString()).replace('.', ','));
            this.setResult();
            //this.deactivate();
            return false;
        };
        this.removeFeature = function() {
            if(this.feature) {
                Mapbender.Model.highlightOff(this.feature);
            }
        };
        this.deactivate = function() {
            this.removeFeature();
            mbMap.element.removeClass('crosshair');
            this.activated = false;
            if(this.mapClickHandler)
                this.mapClickHandler.deactivate();
            $button.addClass('disabled').off('click', $.proxy(this.clickButton, this));
            $inputSrs.off('change', $.proxy(this.srsChanged, this));
        };
    }
    
    function InputModel(mbMap, $inputCoords, $inputSrs, $resultCoords, $resultSrs, $button, $container) {
        this.activated = false;
        this.feature = null;
        this.buffer = 300;
        this.getName = function(){
            return "InputModel";
        };
        this.activate = function() {
            this.activated = true;
            $button.removeClass('disabled').on('click', $.proxy(this.clickButton, this));
            $inputCoords.on('change keyup', $.proxy(this.inputChanged, this));
            $inputSrs.on('change', $.proxy(this.srsChanged, this));
//            this.setResult();
        };
        this.isInputValid = function(coordsText) {
            return readCoords(coordsText) ? true : false;
        };

        this.clickButton = function(e) {
            if(this.setResult()){
                var ordinates = ($resultCoords.val()).replace(new RegExp('\,', 'g'), '.').split(/\s/);
                var targetCoord = new OpenLayers.LonLat(parseFloat(ordinates[0]), parseFloat(ordinates[1]));
                if(mbMap.olMap.getMaxExtent().contains(targetCoord.lon, targetCoord.lat, true)) {
                    //mbMap.olMap.setCenter(targetCoord);
                    var point = new OpenLayers.Geometry.Point(parseFloat(ordinates[0]), parseFloat(ordinates[1]));
                    if(this.feature) {
                        Mapbender.Model.highlightOff(this.feature);
                    }
                    var poi = {
                        position: new OpenLayers.LonLat(point.x, point.y),
                        label: ""
                    };
                    var newFeature = new OpenLayers.Feature.Vector(point, poi);
                    newFeature.style = OpenLayers.Util.applyDefaults({ 'strokeColor': '#cc00ff', 'fillColor': '#cc00ff', 'fillOpacity': '0.1', 'strokeWidth': '3', 'pointRadius': '10' }, OpenLayers.Feature.Vector.style["default"]); 
                    this.feature = [newFeature];
                    Mapbender.Model.highlightOn(this.feature, {clearFirst: true, "goto": false});
                    var ext_buff = Mapbender.Model.calculateExtent(point, {w: this.buffer, h: this.buffer});
                    console.log(point, ext_buff);
                    mbMap.olMap.zoomToExtent(ext_buff, true);
                } else {
                    Mapbender.info(Mapbender.trans('mb.maptool.searchcoordinate.error.coordinate_outside'));
                }
            } else {
//                Mapbender.error('');
            }
        };
        this.srsChanged = function(e) {
            $inputCoords.val('');
            $resultCoords.val('');
        };
        this.mapSrsChanged = function() {
            this.setResult();
        };

        this.inputChanged = function(e) {
            this.setResult();
        };

        this.setResult = function() {
            var data = readCoords($inputCoords.val());
            if (data && data[0]) {
                $inputCoords.removeClass('error');
                var ordinates = data[0].split(/\s/);
                var bounds = null;
                var selectedSrsName = $('select.inputSrs', $container).next().text();
                var inputSrs = Mapbender.Model.getProj($('select.inputSrs option').filter(function () { return $(this).html() == selectedSrsName; }).val());
                var mapProj = Mapbender.Model.getCurrentProj();
                for (var i = 1; ordinates.length > 1 && i < ordinates.length; i = i + 2) { // 2D koordinates
                    var point = new OpenLayers.Geometry.Point(parseFloat(ordinates[i - 1]), parseFloat(ordinates[i]));
                    if (inputSrs.projCode !== mapProj.projCode) {
                        point = point.transform(inputSrs, mapProj);
                    }
                    if (bounds === null) {
                        bounds = point.getBounds();
                    } else {
                        bounds.extend(point.getBounds());
                    }
                }
                var lonlat = bounds.getCenterLonLat();
                $resultCoords.val(((parseFloat(lonlat.lon)).toString()).replace('.', ',') + " " + ((parseFloat(lonlat.lat)).toString()).replace('.', ','));
                return true;
            } else {
                $inputCoords.addClass('error');
                $resultCoords.val('');
                return false;
            }
        };
        this.removeFeature = function() {
            if(this.feature) {
                Mapbender.Model.highlightOff(this.feature);
            }
        };
        this.deactivate = function() {
            this.removeFeature();
            this.activated = false;
            $inputCoords.val('');
            $resultCoords.val('');
            $inputCoords.removeClass('error').off('change keyup', $.proxy(this.inputChanged, this));
            $button.addClass('disabled').off('click', $.proxy(this.clickButton, this));
            $inputSrs.off('change', $.proxy(this.srsChanged, this));
        };
    };

    $.widget("mapbender.mbMapCoordinate", {
        options: {
            target: null,
            type: 'dialog'
        },
        mbMap: null,
        containerInfo: null,
        _create: function() {
            if (!Mapbender.checkTarget("mbMapCoordinate", this.options.target)) {
                return;
            }
            Mapbender.elementRegistry.onElementReady(this.options.target, $.proxy(this._setup, this));
        },
        _setup: function() {
            var self = this;
            this.mbMap = $("#" + this.options.target).data("mapbenderMbMap");
            if (this.options.type === 'element') {
                this.containerInfo = new MapbenderContainerInfo(this, {
                    onactive:   function() {
                        self.activate(null);
                    },
                    oninactive: function() {
                        self.deactivate();
                    }
                });
            }
            this._trigger('ready');
            this._ready();
        },
        defaultAction: function(callback) {
            this.activate(callback);
        },
        activate: function(callback) {
            var self = this;
            var allSrs = this.mbMap.getAllSrs();
            var select = $('.inputSrs', this.element);
            select.html('');
            for (var i = 0; i < allSrs.length; i++) {
                select.append($('<option></option>').val(allSrs[i].name).html(allSrs[i].title));
            }
            var current = this.mbMap.getModel().getCurrentProj();
            select.val(current.projCode);
            $('input.mapSrs', this.element).val(current.projCode);
            if (this.options.type === 'element') {
                this._activateElement();
            } else if (this.options.type === 'dialog') {
                this._activateDialog();
            }
            this.callback = callback ? callback : null;
            $('.buttonGroup [data-modelname]', this.element).each(function(idx, item){
                var $item = $(item);
                if($item.attr('data-modelname') === 'GetCoordinateModel'){
                    $item.data('model', new GetCoordinateModel(self.mbMap.map, $('input.inputCoordinate', self.element),
                        $('select.inputSrs', self.element), $('input.mapCoordinate', self.element),
                        $('input.mapSrs', self.element), $('.readFromMap', self.element), self.element));
                    $item.data('model').deactivate();
                } else if($item.attr('data-modelname') === 'InputModel'){
                    $item.data('model', new InputModel(self.mbMap.map, $('input.inputCoordinate', self.element),
                        $('select.inputSrs', self.element), $('input.mapCoordinate', self.element),
                        $('input.mapSrs', self.element), $('.centerMap', self.element), self.element));
                    $item.data('model').deactivate();
                }
            });
            
            $(document).on('mbmapsrschanged', $.proxy(this._onSrsChanged, this));
            $(document).on('mbmapsrsadded', $.proxy(this._onSrsAdded, this));
            
            $('.buttonGroup .button[data-modelname]', this.element).on('mouseover',  $.proxy(this._onButtonOver, this));
            $('.buttonGroup .button[data-modelname]', this.element).on('mouseout',  $.proxy(this._onButtonOut, this));
            $('.buttonGroup .button[data-modelname]', this.element).on('click',  $.proxy(this._onButtonClick, this));
            $('.copyClipBoard', this.element).on('click',  $.proxy(this._copyToClipboard, this));
            $('.buttonGroup .button.resetFields', this.element).on('click',  $.proxy(this._resetFields, this));

            if($('.buttonGroup [data-modelname]', this.element).length === 1){
                $('.buttonGroup [data-modelname]', this.element).data('model').activate();
                $('.buttonGroup .readFromMap', this.element).addClass('hidden');
            } else if($('.buttonGroup [data-modelname="InputModel"]', this.element).length) {
                $('.buttonGroup [data-modelname="InputModel"]', this.element).data('model').activate();
            }
        },
        
        _copyToClipboard: function(e){
            $(e.target).parent().find('input').select();
             document.execCommand("copy");
        },
        _onButtonOver: function(e){
            var $this = $(e.target);
            if($this.hasClass('disabled')) {
                $this.attr('title', 'click to activate ' + $this.text());
            } else {
                $this.attr('title', $this.text());
            }
        },
        _onButtonOut: function(e){
            $(e.target).attr('title', '');
        },
        _onButtonClick: function(e){
            var $button = $(e.target);
            if(!$button.data('model').activated){
                $button.parent().find('.button').each(function(){
                    if($(this).data('modelname') && $button.data('modelname') !== $(this).data('modelname')){
                        $(this).data('model').deactivate();
                    }
                });
                $button.data('model').activate(e);
            }
        },
        _resetFields: function(){
            $('.buttonGroup .button', this.element).each(function(){
                if($(this).data('model') && $(this).data('model').activated){
                    $(this).data('model').removeFeature();
                }
            });
            $('input.inputCoordinate', this.element).val('');
            $('input.mapCoordinate', this.element).val('');
        },
        _onSrsChanged: function(event, srsObj){
            var founded = false;
            $('.buttonGroup .button', this.element).each(function(){
                if($(this).data('model') && $(this).data('model').activated){
                    $(this).data('model').mapSrsChanged();
                    founded = true;
                }
            });
            if(!founded){
                $('input.inputCoordinate', this.element).val('');
                $('input.mapCoordinate', this.element).val('');
            }
        },
        _onSrsAdded: function(event, srsObj){
            $('.inputSrs', this.element).append($('<option></option>').val(srsObj.name).html(srsObj.title));
        },
        _activateElement: function() {
            var self = this;
            this.element.removeClass('hidden');
            initDropdown.call($('.dropdown', this.element).get(0));
        },
        _activateDialog: function() {
            var self = this;
            if (!this.popup || !this.popup.$element) {
                this.popup = new Mapbender.Popup2({
                    title: self.element.attr('data-title'),
                    draggable: true,
                    modal: false,
                    closeButton: false,
                    closeOnESC: false,
                    content: this.element.removeClass('hidden'),
                    resizable: true,
                    width: 450,
                    height: 354,
                    buttons: {}
                });
                this.popup.$element.on('close', function() {
                    self.deactivate();
                });
                this.popup.$element.on('open', function() {
                    self.state = 'opened';
                });
            }
            initDropdown.call($('.dropdown', this.element).get(0));
            this.popup.open();
        },
        deactivate: function() {
            if (this.options.type === 'element') {
                this._deactivateElement();
            } else if (this.options.type === 'dialog') {
                this._deactivateDialog();
            }
            $('.buttonGroup .button', this.element).each(function(){
                if($(this).data('model')){
                    $(this).data('model').deactivate();
                }
            });
            this.callback ? this.callback.call() : this.callback = null;
            $('#srsList', this.element).off('change', $.proxy(this._changeSrs, this));
            $(document).off('mbmapsrschanged', $.proxy(this._onSrsChanged, this));
            $(document).off('mbmapsrsadded', $.proxy(this._onSrsAdded, this));
        },
        _deactivateElement: function() {
            ;
        },
        _deactivateDialog: function() {
            if (this.popup) {
                if (this.popup.$element) {
                    $('body').append(this.element.addClass('hidden'));
                    this.popup.destroy();
                }
                this.popup = null;
            }
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
