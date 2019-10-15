(function($) {
    $.widget("mapbender.mbLayertree", {
        options: {
            type: 'element',
            displaytype: 'tree',
            autoOpen: false,
            useTheme: false,
            target: null,
            titlemaxlength: 70,
            layerInfo: true,
            showBaseSource: true,
            showHeader: false,
            hideNotToggleable: false,
            hideSelect: false,
            hideInfo: false,
            themes: null,
            menu: []
        },
        model: null,
        dlg: null,
        template: null,
        menuTemplate: null,
        layerconf: null,
        popup: null,
        created: false,
        loadStarted: {},
        sourceAtTree: {},
        mobile: null,
        activeLayers: [],
        consts: {
            source: "source",
            theme: "theme",
            root: "root",
            group: "group",
            simple: "simple"
        },
        transConst: {
            outOfScale: '',
            outOfBounds: '',
            parentInvisible: ''
        },
        _create: function() {
            this.loadStarted = {};
            this.sourceAtTree = {};
            if (!Mapbender.checkTarget("mbLayertree", this.options.target)) {
                return;
            }
            var self = this;
            Mapbender.elementRegistry.onElementReady(this.options.target, $.proxy(self._setup, self));
            // falls mobile Oberfläche...
            if ($('#mobilePane').length) {
                mobile = true;
                $('#filter-input').hide();
                $('#clear-filter-input').hide();
            }
             // ansonsten...
            else {
                mobile = false;
            }
        },
        _setup: function() {
            this.transConst.outOfScale = Mapbender.trans("mb.core.layertree.const.outofscale");
            this.transConst.outOfBounds = Mapbender.trans("mb.core.layertree.const.outofbounds");
            this.transConst.parentInvisible = Mapbender.trans("mb.core.layertree.const.parentinvisible");
            this.options.titlemaxlength = parseInt(this.options.titlemaxlength);
            this.elementUrl = Mapbender.configuration.application.urls.element + '/' + this.element.attr('id') + '/';
            this.template = $('li', this.element).remove();
            this.template.removeClass('hidden');
            this.menuTemplate = $('#layer-menu', this.template).remove();

            this.model = $("#" + this.options.target).data("mapbenderMbMap").getModel();
            $(document).one('mapbender.setupfinished', $.proxy(this._setup2, this));
        },
        _setup2: function() {
            if (this.options.type === 'element') {
                this._createTree();
            } else if (this.options.type === 'dialog' && new Boolean(this.options.autoOpen).valueOf() === true) {
                this.open();
            }
            this.element.removeClass('hidden');
            this._trigger('ready');
            this._ready();
        },
        _createTree: function() {
            var self = this;
            var sources = this.model.getSources();
            if (this.created)
                this._unSortable();
            for (var i = (sources.length - 1); i > -1; i--) {
                if (!sources[i].configuration.isBaseSource
                    || (sources[i].configuration.isBaseSource && this.options.showBaseSource)) {
                    if (this.options.displaytype === "tree") {
                        var li_s = this._createSourceTree(sources[i], sources[i], this.model.getScale());
                        this._addNode(li_s, sources[i]);
                    } else {
                        return;
                    }
                    this.sourceAtTree[sources[i].id ] = {
                        id: sources[i].id
                    };
                    this._resetSourceAtTree(sources[i]);
                }
            }

            this._reset();

            $(document).bind('mbmapsourceloadstart', $.proxy(self._onSourceLoadStart, self));
            $(document).bind('mbmapsourceloadend', $.proxy(self._onSourceLoadEnd, self));
            $(document).bind('mbmapsourceloaderror', $.proxy(self._onSourceLoadError, self));
            $(document).bind('mbmapsourceadded', $.proxy(self._onSourceAdded, self));
            $(document).bind('mbmapsourcechanged', $.proxy(self._onSourceChanged, self));
            $(document).bind('mbmapsourceremoved', $.proxy(self._onSourceRemoved, self));
            this.created = true;
        },
        _addNode: function($toAdd, source) {
            if (this.options.useTheme) {
                var layerset = this.model.findLayerset({
                    source: {
                        origId: source.origId
                    }
                });
                var theme = {};
                $.each(this.options.themes, function(idx, item) {
                    if (item.id === layerset.id)
                        theme = item;
                })
                if (theme.useTheme) {
                    var $layersetEl = this._createThemeNode(layerset, theme);
                    $("ul.layers:first", $layersetEl).append($toAdd);
                } else {
                    $("ul.layers:first", this.element).append($toAdd);
                }
            } else {
                $("ul.layers:first", this.element).append($toAdd);
            }
        },
        _reset: function() {
            this._resetEvents();
            this._resetSortable();
            this._resetCheckboxes();
            this._setSourcesCount();
            
            // falls mobile Oberfläche...
            if (mobile === true) {
                // Titel und Position des Buttons zum Deaktivieren aller Kartenthemen ändern
                $('#unSelectAllRoot').prop('title', 'alle Kartenthemen deaktivieren');
                $('#unSelectAllRoot').insertBefore('#list-root');
                $('#unSelectAllRoot').wrap("<div class='spacerDiv'></div>");
                // Häkchen zum Aktivieren aller Unterebenen einer Ebene ausblenden
                $('.selectAll').hide();
            }
            
            // alle Layer durchgehen...
            $('input[name="selected"]').each(function(index, firstElement) {
                var $li = $(firstElement).parents('li:first');
                // falls aktiviert...
                if ($(firstElement).is(':checked')) {
                    // visuelle Markierung des Ordnersymbols aller übergeordneten Themen/Ordner hinzufügen
                    $li.parents('li[data-type]').find('.iconFolder:first').addClass('folderWithActiveLayer');
                }
                // ansonsten...
                else {
                    // alle übergeordneten Themen/Ordner durchlaufen und jeweils die visuelle Markierung des Ordnersymbols entfernen, falls dort keine Layer mehr aktiv sind
                    $li.parents('li[data-type]').each(function(index, secondElement) {
                        if ($(secondElement).find('.iconCheckboxActive').length == 0) {
                            $(secondElement).find('.iconFolder:first').removeClass('folderWithActiveLayer');
                        }
                    });
                }
            });
        },
        _createEvents: function() {
            var self = this;
            this.element.on('change', 'input[name="sourceVisibility"]', $.proxy(self._toggleSourceVisibility, self));
            this.element.on('change', 'input[name="selected"]', $.proxy(self._toggleSelected, self));
            this.element.on('change', 'input[name="info"]', $.proxy(self._toggleInfo, self));
            this.element.on('click', '.iconFolder', $.proxy(self._toggleContent, self));
            this.element.on('click', '#delete-all', $.proxy(self._removeAllSources, self));
            this.element.on('click', '.layer-menu-btn', $.proxy(self._toggleMenu, self));
            this.element.on('click', '.selectAll', $.proxy(self._selectAll, self));
            this.element.on('click', '.unSelectAll', $.proxy(self._unSelectAll, self));
            this.element.on('click', '#unSelectAllRoot', $.proxy(self._unSelectAllRoot, self));
            this.element.on('keyup', '#filter-input', $.proxy(self._filterInput, self));
            this.element.on('click', '#clear-filter-input', $.proxy(self._clearFilterInput, self));
        },
        _clearFilterInput: function(e) {
            $('#filter-input').val('');
            $("#list-root li").removeClass("filterSurvivor").show();
        },
        _filterInput: function(e) {
            var value = $('#filter-input').val().toLowerCase();
            if (value.length > 2) {
                $("#list-root li").each(function() {
                    if ($(this).data("title").toString().toLowerCase().search(value) > -1) {
                        $(this).addClass("filterSurvivor").show();
                    } else {
                        $(this).removeClass("filterSurvivor").hide();
                    }
                });
                $("#list-root li.filterSurvivor").each(function() {
                    $(this).parents("li.leave").each(function() {
                        $(this).addClass("filterSurvivor").show();
                    });
                    $(this).find("li:hidden").each(function() {
                        $(this).addClass("filterSurvivor").show();
                    });
                });
            }
        },
        _removeEvents: function() {
            var self = this;
            this.element.off('change', 'input[name="sourceVisibility"]', $.proxy(self._toggleSourceVisibility, self));
            this.element.off('change', 'input[name="selected"]', $.proxy(self._toggleSelected, self));
            this.element.off('change', 'input[name="info"]', $.proxy(self._toggleInfo, self));
            this.element.off('click', '.iconFolder', $.proxy(self._toggleContent, self));
            this.element.off('click', '#delete-all', $.proxy(self._removeAllSources, self));
            this.element.off('click', '.layer-menu-btn', $.proxy(self._toggleMenu, self));
            this.element.off('click', '.selectAll', $.proxy(self._selectAll, self));
            this.element.off('click', '.unSelectAll', $.proxy(self._unSelectAll, self));
            this.element.off('click', '#unSelectAllRoot', $.proxy(self._unSelectAllRoot, self));
        },
        _resetEvents: function() {
            this._removeEvents();
            this._createEvents();
        },
        _resetCheckboxes: function() {
            var self = this;
            this.element.off('change', 'input[name="sourceVisibility"]', $.proxy(self._toggleSourceVisibility, self));
            this.element.off('change', 'input[name="selected"]', $.proxy(self._toggleSelected, self));
            this.element.off('change', 'input[name="info"]', $.proxy(self._toggleInfo, self));
            this.element.on('change', 'input[name="sourceVisibility"]', $.proxy(self._toggleSourceVisibility, self));
            this.element.on('change', 'input[name="selected"]', $.proxy(self._toggleSelected, self));
            this.element.on('change', 'input[name="info"]', $.proxy(self._toggleInfo, self));
            if (initCheckbox) {
                $('.checkbox', self.element).each(function() {
                    initCheckbox.call(this);
                });
            }
        },
        _resetSortable: function() {
            this._unSortable();
            this._createSortable();
        },
        _unSortable: function() {
        },
        _sortItem: function($toMoveItem, $beforeItem, $afterItem) {
            var before = null;
            var after = null;
            var toMove = null;
            var liElements = $('li');
            if ($beforeItem) {
                if ($beforeItem.hasClass('themeContainer')) {
                    var $beforeSEl = $beforeItem.find('ul.layers:first li[data-type="root"]:last');
                    var bid = $beforeSEl.attr('data-sourceid');
                    before = {
                        sourceIdx: {
                            id: bid
                        },
                        layerIdx: {
                            id: $beforeSEl.attr("data-id")
                        }
                    };
                } else {
                    var bid = $beforeItem.attr('data-sourceid');
                    bid = bid ? bid : $beforeItem.parents('li[data-sourceid]:first').attr('data-sourceid');
                    before = {
                        sourceIdx: {
                            id: bid
                        },
                        layerIdx: {
                            id: $beforeItem.attr("data-id")
                        }
                    };
                }
            }
            if ($afterItem) {
                if ($afterItem.hasClass('themeContainer')) {
                    var $afterSEl = $afterItem.find('ul.layers:first li[data-type="root"]:first');
                    var aid = $afterSEl.attr('data-sourceid');
                    after = {
                        sourceIdx: {
                            id: aid
                        },
                        layerIdx: {
                            id: $afterSEl.attr("data-id")
                        }
                    };
                } else {
                    var aid = $afterItem.attr('data-sourceid');
                    aid = aid ? aid : $afterItem.parents('li[data-sourceid]:first').attr('data-sourceid');
                    after = {
                        sourceIdx: {
                            id: aid
                        },
                        layerIdx: {
                            id: $afterItem.attr("data-id")
                        }
                    };
                }
            }
            var sid = $toMoveItem.attr('data-sourceid');
            sid = sid ? sid : $toMoveItem.parents('li[data-sourceid]:first').attr('data-sourceid');
            var lid = $toMoveItem.attr('data-id');
            lid = lid ? lid : $toMoveItem.parents('li[data-sourceid]:first').attr('data-id');
            
            // neuen Themenbaum-li-Index des verschobenen Layers ermitteln
            var listIndex = liElements.index($("li[data-sourceid='" + sid +"']"));
            // alle aktiven Layer durchgehen ...
            $.each( this.activeLayers, function( index, item ) {
                // ... falls verschobener Layer unter den aktiven ist: neuen Themenbaum-li-Index setzen
                if (item.sourceId == sid) {
                    item.listIndex = listIndex;
                }
                // ... ansonsten: immer aktuellen Themenbaum-li-Index setzen (da durch das Verschieben aktive Layer ja erheblich nach oben oder unten "rutschen" können)
                else {
                    item.listIndex = liElements.index($("li[data-sourceid='" + item.sourceId +"']"));
                }
            } );
            // aktive Layer nach Themenbaum-li-Index sortieren
            this.activeLayers.sort(function(a, b){
                if(a.listIndex < b.listIndex) return -1;
                if(a.listIndex > b.listIndex) return 1;
                return 0;
            });
            // Sortierung umkehren (für OpenLayers-Zeichenreihenfolge!)
            this.activeLayers.reverse();
            // aktive Layer in OpenLayers gemäß Themenbaum-li-Index umpositionieren 
            this.model.setLayerPositions(this.activeLayers);
            
            toMove = {
                sourceIdx: {
                    id: sid
                },
                layerIdx: {
                    id: lid
                }
            };
            // nur, wenn nachfolgender Aufruf deaktiviert ist, funktioniert das Zusammenspiel Themenbaum-li-Index und OpenLayers-Zeichenreihenfolge ordentlich!
            /*this.model.changeSource({
                change: {
                    move: {
                        tomove: toMove,
                        before: before,
                        after: after
                    }
                }
            });*/
        },
        _sortTheme: function($theme) {
            var self = this;
            var $beforeEl = $theme.prev().length !== 0 ? $($theme.prev()[0]) : null;
            var $afterEl = $theme.next().length !== 0 ? $($theme.next()[0]) : null;
            var $last = null;
            $('ul.layers:first li[data-type="root"]', $theme).each(function(idx, item) {
                var $item = $(item);
                self._sortItem($item, $beforeEl, $afterEl);
                $last = $item;
            });
        },
        _sortSource: function($item) {
            var $beforeEl = $item.prev().length !== 0 ? $($item.prev()[0]) : null;
            var $afterEl = $item.next().length !== 0 ? $($item.next()[0]) : null;
            this._sortItem($item, $beforeEl, $afterEl);
        },
        _createSortable: function() {
            var self = this;
            $("ul.layers", this.element).each(function() {
                var that = this;
                $(that).sortable({
                    axis: 'y',
                    items: "> li:not(.notreorder)",
                    distance: 6,
                    cursor: "move",
                    update: function(event, ui) {
                        var $elm = $(ui.item);
                        if ($elm.hasClass('themeContainer')) {
                            self._sortTheme($elm);
                        } else {
                            self._sortSource($elm);
                        }
                    }
                });
            });
        },
        _isThemeVisible: function(layerset) {
            for (var i = 0; i < layerset.content.length; i++) {
                for (id in layerset.content[i]) {
                    if (layerset.content[i][id].configuration.children[0].options.treeOptions.selected) {
                        return true;
                    }
                }
            }
            return false;
        },
        _createThemeNode: function(layerset, theme) {
            var $li = $('ul.layers:first > li[data-layersetid="' + layerset.id + '"]', this.element);
            if ($li.length === 1) {
                return $li;
            } else {
                $li = this.template.clone();
            }
            $('ul.layers:first', this.element).append($li);
            $li.removeClass('hide-elm').addClass('toggleable');
            $li.attr('data-layersetid', layerset.id);
            $li.removeAttr('data-id');
            $li.removeAttr('data-sourceid');
            $li.attr('data-type', this.consts.theme).attr('data-title', layerset.title);
            $li.addClass("themeContainer");
            if (theme.opened)
                $li.addClass("showLeaves").find(".iconFolder").addClass("iconFolderActive");
            else
                $li.removeClass("showLeaves").find(".iconFolder").removeClass("iconFolderActive");
            $('span.layer-title:first', $li).text(layerset.title);
            $('span.layer-spinner:first', $li).remove();
            $('span.layer-state:first', $li).remove();
            $('div.featureInfoWrapper', $li).remove();
            $('div.selectedWrapper', $li).remove();
            if (!theme.allSelected) {
                $('div.selectAll', $li).remove();
            }
            if (!theme.sourceVisibility) {
                $('div.sourceVisibilityWrapper', $li).remove();
            } else {
                $('div.sourceVisibilityWrapper input[name="sourceVisibility"]', $li).prop('checked',
                    this._isThemeVisible(layerset));
            }
            $('.layer-menu-btn', $li).remove();
            return $li;
        },
        _createNode: function(source, sourceEl, config, isroot) {
            var $li = this.template.clone();
            $li.removeClass('hide-elm');
            $li.attr('data-id', sourceEl.options.id);
            $li.attr('data-sourceid', source.id);
            var nodeType = this._getNodeType(sourceEl, isroot);
            $('div.sourceVisibilityWrapper', $li).remove();
            $li.attr('data-type', nodeType).attr('data-title', sourceEl.options.title);
            if (nodeType === this.consts.root || nodeType === this.consts.group) {
                $('div.selectAll', $li).removeClass('left');
                $('.featureInfoWrapper:first', $li).remove();
                if (nodeType === this.consts.root) {
                    $li.addClass("serviceContainer");
                } else if (nodeType === this.consts.group) {
                    $li.addClass("groupContainer");
                }
                if (config.toggle === true) {
                    $li.addClass("showLeaves").find(".iconFolder").addClass("iconFolderActive");
                } else {
                    $li.removeClass("showLeaves").find(".iconFolder").removeClass("iconFolderActive");
                }
                if (config.toggleable) {
                    $li.addClass('toggleable');
                }
            } else {
                $('div.selectAll', $li).remove();
                $('div.unSelectAll', $li).remove();
            }
            $li.addClass(config.reorder);
            $li.find('.layer-state').attr('title', config.visibility.tooltip);
            $li.find('input.layer-selected').prop('checked', config.selected ? true : false);
            if (!config.selectable)
                $li.find('input.layer-selected').prop('disabled', true);
            $li.find('input.layer-info').prop('checked', config.info ? true : false);
            if (!config.infoable || config.infoable === '0')
                $li.find('input.layer-info').prop('disabled', true);
            var infoHidden = false;
            if (this.options.hideInfo) {
                infoHidden = true;
                $('input[name="info"]', $li).parents('.checkWrapper:first').remove();
            }
            var selectHidden = false;
            if (this.options.hideSelect && config.selected && !config.selectable &&
                (nodeType === this.consts.root || nodeType === this.consts.group)) {
                selectHidden = true;
                $('input[name="selected"]', $li).parents('.checkWrapper:first').remove();
            }
            if (config.toggleable === false && this.options.hideNotToggleable) {
                var $folder = $li.find('.iconFolder');
                if (selectHidden && infoHidden) {
                    $folder.addClass('placeholder')
                }
                $folder.removeClass('iconFolder');
            }
            $li.find('.layer-title:first').attr('title', sourceEl.options.title).text(this._subStringText(
                sourceEl.options.title));
            if (this.options.menu.length === 0) {
                $li.find('.layer-menu-btn').remove();
            }
            return $li;
        },
        _createSourceTree: function(source, sourceEl, scale, type, isroot) {
            if (sourceEl.type) {
                var li = this._createSourceTree(source, sourceEl.configuration.children[0], scale, sourceEl.type,
                    true);
                if (sourceEl.configuration.status !== 'ok') {
                    li.attr('data-state', 'error').find('span.layer-title:first').attr("title",
                        sourceEl.configuration.status);
                }
            } else {
                var config = this._getNodeProporties(sourceEl);
                var li = this._createNode(source, sourceEl, config, isroot);
                if (sourceEl.children) {
                    li.find('ul:first').attr('id', 'list-' + sourceEl.options.id);
                    if (config.toggle) {
                        li.find('ul:first').addClass("closed");
                    }
                    for (var j = sourceEl.children.length; j > 0; j--) {
                        li.find('ul:first').append(this._createSourceTree(source, sourceEl.children[j - 1], scale,
                            type, false));
                    }
                } else {
                    li.find('ul:first').remove();
                }
            }
            var thisTitle = li.data('title');
            var thisSubs = li.find('ul.layers');
            var thisSubsCount = thisSubs.length;
            var thisFirstSub = li.find('ul.layers > li');
            var thisFirstSubTitle = thisFirstSub.data('title');
            var thisFirstSubType = thisFirstSub.data('type');
            if (thisSubsCount === 1 || (thisSubsCount === 2 && thisFirstSubType === 'group' && thisFirstSubTitle === thisTitle)) {
                var ok = true;
                if (thisFirstSubType === 'simple') {
                    thisSubs.find('li').each(function() {
                        if ($(this).data('title') !== thisTitle) {
                            ok = false;
                            return false;
                        }
                    });
                } else if (thisFirstSubType === 'group') {
                    thisFirstSub.find('li').each(function() {
                        if ($(this).data('title') !== thisTitle) {
                            ok = false;
                            return false;
                        }
                    });
                }
                if (ok) {
                    li.removeClass('showLeaves').find('.iconFolder').removeClass('iconFolderActive').removeClass('iconFolder');
                    li.find('.iconOk').addClass('iconOkMarginLeft');
                }
            }
            return li;
        },
        _createTreeNode: function(source, sourceEl, scale, layerToAdd, parent, type, isroot, found) {
            if (sourceEl.type) {
                var li = "";
                for (var i = 0; i < sourceEl.configuration.children.length; i++) {
                    li = this._createTreeNode(source, sourceEl.configuration.children[i], scale, layerToAdd, parent,
                        sourceEl.type, true, false);
                }
            } else {
                if (layerToAdd.options.id.toString() === sourceEl.options.id.toString() || found) {
                    found = true;
                    var config = this._getNodeProporties(sourceEl);
                    var li = this._createNode(source, sourceEl, config, isroot);
                    if (sourceEl.children) {
                        li.find('ul:first').attr('id', 'list-' + sourceEl.options.id);
                        if (config.toggle) {
                            li.find('ul:first').addClass("closed");
                        }
                        for (var j = 0; j < sourceEl.children.length; j++) {
                            li.find('ul:first').append(this._createTreeNode(source, sourceEl.children[j], scale,
                                layerToAdd, parent, type, false, found));
                        }
                    } else {
                        li.find('ul').remove();
                    }
                    found = false;
                    return li;
                }
                if (sourceEl.children) {
                    parent = parent.find('li[data-id="' + sourceEl.options.id + '"]:first');
                    for (var j = 0; j < sourceEl.children.length; j++) {
                        var li = this._createTreeNode(source, sourceEl.children[j], scale, layerToAdd, parent, type,
                            false, found);
                        if (li !== null) {
                            if (sourceEl.children.length === 1) {
                                parent.add(li);
                            } else if (j === 0) {
                                parent.find('li[data-id="' + sourceEl.children[j + 1].options.id + '"]:first').after(
                                    li);
                            } else {
                                parent.find('li[data-id="' + sourceEl.children[j - 1].options.id + '"]:first').before(
                                    li);
                            }
                        }
                    }
                }
            }
            return null;
        },
        _onSourceAdded: function(event, options) {
            if (!this.created || !options.added)
                return;
            var added = options.added;
            if (added.source.configuration.baseSource && !this.options.showBaseSource) {
                return;
            }
            if (this.options.displaytype === "tree") {
                var hasChildren = false;
                for (layerid in added.children) {
                    this._createTreeNode(added.source, added.source, this.model.getScale(), added.children[layerid], $(
                        this.element).find('ul.layers:first'));
                }
                if (!hasChildren) {
                    var li_s = this._createSourceTree(added.source, added.source, this.model.getScale());
                    var first_li = $(this.element).find('ul.layers:first li:first');
                    if (first_li && first_li.length !== 0) {
                        first_li.before(li_s);
                    } else {
                        $(this.element).find('ul.layers:first').append($(li_s));
                    }
                }
            } else {
                return;
            }
            this.sourceAtTree[added.source.id ] = {
                id: added.source.id
            };
            this._reset();
        },
        _onSourceChanged: function(event, options) {
            if (options.changed && options.changed.children) {
                this._changeChildren(options.changed);
            } else if (options.changed && options.changed.childRemoved) {
                this._removeChild(options.changed);
            }
        },
        _isThemeChecked: function($li){
            if(this.options.useTheme === false) {
                return true;
            }
            var $lith = $li.parents('li.themeContainer:first');
            if($lith.length === 1){
                var theme = {};
                var lsid = $lith.attr('data-layersetid');
                $.each(this.options.themes, function(idx, item) {
                    if (item.id === lsid)
                        theme = item;
                });
                if(theme.sourceVisibility){
                    return $('input[name="sourceVisibility"]:first', $lith).prop('checked');
                } else {
                    return true;
                }
            } else if($lith.length === 0){
                return true;
            }
            return false;
        },
        _resetNodeOutOfScale: function($li, layerDef) {
            var model = this.model;
            if (layerDef.state.outOfScale) {
                $li.addClass("invisible").find('span.layer-state').attr("title", "out of scale");
                
                // falls WMS-Layergruppe vorhanden: Visibility-Information auch zu WMS-Layergruppe hinzufügen, sofern es keinen Layer in der Gruppe (mehr) gibt, der sichtbar (nicht "out of scale") ist
                if ($li.parents('li:first[data-type="group"]').length > 0) {
                    var addInvisible = true;
                    $li.parents('li:first[data-type="group"]').find('li[data-type="simple"]').each(function(idx, item){
                        if (!(model.findLayer( { id: $(item).attr('data-sourceid') }, { id: $(item).attr('data-id') } ).layer.state.outOfScale)) {
                            addInvisible = false;
                            return false;
                        }
                    });
                    if (addInvisible === true) {
                        $li.parents('li:first[data-type="group"]').addClass('invisible');
                    }
                }
                
            } else if (!layerDef.state.outOfScale) {
                $li.removeClass("invisible").find('span.layer-state').attr("title", "");
                
                // falls WMS-Layergruppe vorhanden: Visibility-Information auch von WMS-Layergruppe entfernen, sofern es keinen Layer in der Gruppe (mehr) gibt, der unsichtbar ("out of scale") ist
                if ($li.parents('li:first[data-type="group"]').length > 0) {
                    var removeInvisible = true;
                    $li.parents('li:first[data-type="group"]').find('li[data-type="simple"]').each(function(idx, item){
                        if ((model.findLayer( { id: $(item).attr('data-sourceid') }, { id: $(item).attr('data-id') } ).layer.state.outOfScale)) {
                            removeInvisible = false;
                            return false;
                        }
                    });
                    if (removeInvisible === true) {
                        $li.parents('li:first[data-type="group"]').removeClass('invisible');
                    }
                }
                
            }
        },
        _resetNodeSelected: function($li, layerOptions) {
            var chk_selected = $('input[name="selected"]:first', $li);
            chk_selected.prop('checked', layerOptions.treeOptions.selected);
            initCheckbox.call(chk_selected);
        },
        _resetNodeInfo: function($li, layerOptions) {
            var chk_info = $('input[name="info"]:first', $li);
            chk_info.prop('checked', layerOptions.treeOptions.info);
            chk_info.each(function(k, v) {
                initCheckbox.call(v);
            });
        },
        _resetNodeVisible: function($li, layerDef) {
            if (layerDef.state.visibility) {
                $li.removeClass("invisible").find('span.layer-state:first').attr("title", "");
            }
            this._resetNodeOutOfScale($li, layerDef);
        },
        _resetSourceAtTree: function(source) {
            var self = this;
            function resetSourceAtTree(layer, parent) {
                var $li = $('li[data-id="' + layer.options.id + '"]', self.element);
                self._resetNodeSelected($li, layer.options);
                self._resetNodeInfo($li, layer.options);
                self._resetNodeVisible($li, layer);
                if (layer.children) {
                    for (var i = 0; i < layer.children.length; i++) {
                        resetSourceAtTree(layer.children[i], layer);
                    }
                }
            }
            ;
            resetSourceAtTree(source.configuration.children[0], null);
        },
        _changeChildren: function(changed) {
            if (changed.children) {
                for (var layerId in changed.children) {
                    var $li = $('li[data-id="' + layerId + '"]', this.element);
                    if ($li.length !== 0) {
                        if ($li.attr("data-type") === this.consts.root && !this._isThemeChecked($li)){
                            continue;
                        }
                        if (changed.children[layerId].options) {
                            this._resetNodeSelected($li, changed.children[layerId].options);
                            this._resetNodeInfo($li, changed.children[layerId].options);
                            if (changed.children[layerId].options.state) {
                                this._resetNodeVisible($li, changed.children[layerId].options);
                            }
                            if(changed.children[layerId].options.treeOptions.allow){
                                if(changed.children[layerId].options.treeOptions.allow.selected === true){
                                    var chk_selected = $('input[name="selected"]:first', $li);
                                    chk_selected.prop('disabled', false);
                                    $li.removeClass('invisible');
                                    initCheckbox.call(chk_selected);
                                } else if(changed.children[layerId].options.treeOptions.allow.selected === false){
                                    var chk_selected = $('input[name="selected"]:first', $li);
                                    chk_selected.prop('disabled', true);
                                    $li.addClass('invisible');
                                    initCheckbox.call(chk_selected);
                                }
                            }
                        } else if (changed.children[layerId].state) {
                            this._resetNodeOutOfScale($li, changed.children[layerId]);
                        }
                    }
                }
            }
        },
        _removeChild: function(changed) {
            var self = this;
            if (changed && changed.sourceIdx && changed.childRemoved) {
                var source = this.model.getSource(changed.sourceIdx);
                $('ul.layers:first li[data-id="' + changed.childRemoved.layer.options.id + '"]', self.element).
                    remove();
            }
        },
        _onSourceRemoved: function(event, removed) {
            if (removed && removed.source && removed.source.id) {
                var $source = $('ul.layers:first li[data-sourceid="' + removed.source.id + '"]', this.element);
                var $theme = $source.parents('.themeContainer:first');
                $('ul.layers:first li[data-sourceid="' + removed.source.id + '"]', this.element).remove();
                if ($theme.length && $theme.find('.serviceContainer').length === 0){
                    $theme.remove();
                }
                this._setSourcesCount();
            }
        },
        _onSourceLoadStart: function(event, options) {
            if (options.source && this.sourceAtTree[options.source.id ]) {
                this.loadStarted[options.source.id ] = true;
                var source_li = $('li[data-sourceid="' + options.source.id + '"][data-type="root"]', this.element);
                if (options.source.configuration.children[0].options.treeOptions.selected && !source_li.hasClass(
                    'invisible')) {
                    source_li.attr('data-state', 'loading').find('span.layer-state:first').attr("title",
                        source_li.attr('data-title'));
                }
            }
        },
        _onSourceLoadEnd: function(event, option) {
            if (option.source && this.sourceAtTree[option.source.id ] && this.loadStarted[option.source.id]) {
                this.loadStarted[option.source.id] = false;
                var source_li = $('li[data-sourceid="' + option.source.id + '"][data-type="root"]', this.element);
                source_li.attr('data-state', '');
                this._resetSourceAtTree(option.source);
            }
        },
        _onSourceLoadError: function(event, option) {
            if (option.source && this.sourceAtTree[option.source.id ] && this.loadStarted[option.source.id]) {
                this.loadStarted[option.source.id] = false;
                var source_li = $('li[data-sourceid="' + option.source.id + '"][data-type="root"]', this.element);
                source_li.attr('data-state', 'error').find('span.layer-title:first').attr("title",
                    option.error.details);
            }
        },
        _subStringText: function(text) {
            if(text === null) {
                return '';
            }
            if (text.length <= this.options.titlemaxlength) {
                return text;
            } else {
                for (var i = this.options.titlemaxlength; i > 0; i--) {
                    if (text[i] === " ") {
                        text = text.substring(0, i) + "...";
                        break;
                    }
                }
                if (text.length < 2 || text.length > this.options.titlemaxlength + 3)
                    return text.substring(0, this.options.titlemaxlength) + "...";
                else
                    return text;
            }
        },
        _getNodeType: function(node, isroot) {
            if (isroot) {
                return this.consts.root;
            } else if (node.children) {
                return this.consts.group;
            } else {
                return this.consts.simple;
            }
        },
        _getNodeProporties: function(nodeConfig) {
            var conf = {
                selected: nodeConfig.options.treeOptions.selected,
                selectable: nodeConfig.options.treeOptions.allow.selected,
                info: nodeConfig.options.treeOptions.info,
                infoable: nodeConfig.options.treeOptions.allow.info,
                reorderable: nodeConfig.options.treeOptions.allow.reorder
            };

            if (nodeConfig.children) {
                conf["toggle"] = nodeConfig.options.treeOptions.toggle;
                conf["toggleable"] = nodeConfig.options.treeOptions.allow.toggle;
            } else {
                conf["toggle"] = null;
                conf["toggleable"] = null;
            }

            if (nodeConfig.state.outOfScale) {
                conf["visibility"] = {
                    state: "invisible",
                    tooltip: this.transConst.outOfScale
                };
            } else if (nodeConfig.state.outOfBounds) {
                conf["visibility"] = {
                    state: "invisible",
                    tooltip: this.transConst.outOfBounds
                };
            } else if (!nodeConfig.state.visibility) {
                conf["visibility"] = {
                    state: "invisible",
                    tooltip: this.transConst.parentinvisible
                };
            } else {
                conf["visibility"] = {
                    state: "",
                    tooltip: ""
                };
            }
            return conf;
        },
        _toggleContent: function(e) {
            var $me = $(e.target);
            var $parent = $me.parents('li:first');
            if (!$parent.hasClass('toggleable'))
                return false;
            if ($me.hasClass("iconFolderActive")) {
                $me.removeClass("iconFolderActive");
                $parent.removeClass("showLeaves");
            } else {
                $me.addClass("iconFolderActive");
                $parent.addClass("showLeaves");
            }
            var li = $me.parents('li:first[data-sourceid]');
            if (li.length > 0) {
                this._resetSourceAtTree(this.model.getSource({
                    id: li.attr(
                        'data-sourceid')
                }));
            }
            return false;
        },
        _toggleSourceVisibility: function(e) {
            var self = this;
            var $sourceVsbl = $(e.target);
            var $li = $sourceVsbl.parents('li:first');
            $('li[data-type="' + this.consts.root + '"]', $li).each(function(idx, item) {
                var $item = $(item);
                var chk_selected = $('input[name="selected"]:first', $item);
                self.model.changeSource({
                    change: {
                    sourceIdx: {
                        id: $item.attr('data-sourceid')
                    },
                    options: {
                        type: 'selected',
                        configuration: {
                            options: {
                                visibility: $sourceVsbl.prop('checked') === false ? false : chk_selected.prop('checked')
                            }
                        }
                    }
                }});
            });
            return false;
        },
        /**
         * Set map visibility of a single layer / all layers in a "root" / all layers in a "theme".
         *
         * @param {jQuery object} $el The ul.layers > li element that received a click
         * @param {boolean} newState Make layer(s) visible with true, hide with false
         */
        _setLayerVisibility: function($el, newState, unSelectAllRoot) {
            var self = this;
            
            var liElements = $('li');
            
            var layerOptions = {
                options: {
                    treeOptions: {
                        selected: !!newState
                    }
                }
            };
            var layers = {};
            var targets = [];

            var $targetRoots = $('li[data-type="' + this.consts.root + '"]', $el);
            if (!$targetRoots.length) {
                if ($el.attr('data-type') == this.consts.root || $el.attr('data-type') == this.consts.group) {
                    $targetRoots = $el;
                }
            }
            if ($targetRoots.length) {
                $targetRoots.each(function(_, rootEl) {
                    targets.push({
                        source: {
                            id: $(rootEl).data('sourceid')
                        },
                        layers: $('ul.layers li', rootEl)
                    });
                });
            } else {
                targets.push({
                    source: {
                        id: $el.closest('li[data-type="' + this.consts.root + '"]').data('sourceid')
                    },
                    layers: $el
                });
            }
            for (var i = 0; i < targets.length; ++i) {
                layers[targets[i].source] = layerOptions;
                targets[i].layers.each(function(_, layerEl) {
                    var $layerEl = $(layerEl);
                    var wasSelected = !!$('input[name="selected"]:first', $layerEl).prop('checked');                    
                    if (layerEl === $el[0] || wasSelected !== !!newState) {
                        layers[$layerEl.attr('data-id')] = layerOptions;
                    }
                });
                // Themenbaum-li-Index des Layers ermitteln
                var listIndex = liElements.index($("li[data-sourceid='" + targets[i].source.id +"']"));
                // falls Layer aktiviert wurde ...
                if (newState === true)
                    // ... zu aktiven Layern hinzufügen
                    this.activeLayers.push({ sourceId: targets[i].source.id, listIndex: listIndex });
                // falls Layer deaktiviert wurde ...
                else
                    // ... aus aktiven Layern entfernen
                    this.activeLayers = this.activeLayers.filter(function(activeLayer) { return activeLayer.sourceId !== targets[i].source.id } );
                // aktive Layer nach Themenbaum-li-Index sortieren
                this.activeLayers.sort(function(a, b){
                    if(a.listIndex < b.listIndex) return -1;
                    if(a.listIndex > b.listIndex) return 1;
                    return 0;
                });
                // Sortierung umkehren (für OpenLayers-Zeichenreihenfolge!)
                this.activeLayers.reverse();
                self.model.changeLayerState(targets[i].source, {layers: layers}, newState ? false : null, !!newState);
                // aktive Layer in OpenLayers gemäß Themenbaum-li-Index umpositionieren 
                self.model.setLayerPositions(this.activeLayers);
            }
            for (var i = 0; i < targets.length; ++i) {
                targets[i].layers.each(function(_, layerEl) {
                    var $layerEl = $(layerEl);
                    // falls aktiviert...
                    if (newState) {
                        // visuelle Markierung des Ordnersymbols aller übergeordneten Themen/Ordner hinzufügen
                        $layerEl.parents('li[data-type]').find('.iconFolder:first').addClass('folderWithActiveLayer');
                        // falls selbst ohne Ordnersymbol...
                        if ($layerEl.parents('li[data-type="root"]:first').find('.iconFolder').length === 0)
                            // visuelle Markierung des selectAll-Häkchens hinzufügen
                            $layerEl.parents('li[data-type="root"]:first').find('.selectAll:first').addClass('folderWithActiveLayer');
                    }
                    // ansonsten...
                    else {
                        if (mobile === true) {
                            $layerEl.parents('li[data-type="root"]:first').find('.checkWrapper:first').removeClass('iconCheckboxActive');
                        }
                        // alle übergeordneten Themen/Ordner durchlaufen und jeweils die visuelle Markierung des Ordnersymbols entfernen, falls dort keine Layer mehr aktiv sind
                        $layerEl.parents('li[data-type]').each(function(index, element) {
                            if ($(element).find('.iconCheckboxActive').length == 0) {
                                $(element).find('.iconFolder:first').removeClass('folderWithActiveLayer');
                                $(element).find('.selectAll:first').removeClass('folderWithActiveLayer');
                            }
                        });
                    }
                });
            }
            return false;
        },
        _selectAll: function(e) {
            return this._setLayerVisibility.call(this, $(e.target).closest('li'), true);
        },
        _unSelectAll: function(e) {
            return this._setLayerVisibility.call(this, $(e.target).closest('li'), false);
        },
        _unSelectAllRoot: function(e) {
            return this._setLayerVisibility.call(this, $('#list-root').children('li'), false);
        },
        _toggleSelected: function(e) {
            var $container = $(e.target).closest('li');
            var newState = $('input[name="selected"]:first', $container).prop('checked');
            return this._setLayerVisibility.call(this, $container, newState);
        },
        _toggleInfo: function(e) {
            var li = $(e.target).parents('li:first');
            var tochange = {
                sourceIdx: {
                    id: li.attr(
                        'data-sourceid')
                },
                options: {
                    children: {},
                    type: 'info'
                }
            };
            tochange.options.children[li.attr('data-id')] = {
                options: {
                    treeOptions: {
                        info: $(
                            e.target).
                            is(
                                ':checked')
                    }
                }
            };
            this.model.changeSource({
                change: tochange
            });
        },
        currentMenu: null,
        closeMenu: function(menu) {
            menu.off('click').remove();
            return false;
        },
        _toggleMenu: function(e) {
            var self = this;
            function createMenu($element, sourceId, layerId) {
                var atLeastOne = false;
                var source = self.model.findSource({
                    id: sourceId
                })[0];
                var menu = $(self.menuTemplate.clone().attr("data-menuLayerId", layerId).attr("data-menuSourceId",
                    sourceId));
                var exitButton = menu.find('.exit-button');
                var previousMenu = self.currentMenu;

                if (self.currentMenu === menu) {
                    return;
                }

                self.currentMenu = menu;

                if (previousMenu) {
                    self.closeMenu(previousMenu);
                }

                exitButton.on('click', function(e) {
                    self.closeMenu(menu);
                });

                var removeButton = menu.find('.layer-remove-btn');
                atLeastOne = removeButton.length > 0;
                removeButton.on('click', $.proxy(self._removeSource, self));

                if ($element.parents('li:first').attr('data-type') !== self.consts.root) {
                    menu.find('#layer-opacity').remove();
                    menu.find('#layer-opacity-text').remove();
                }

                menu.removeClass('hidden');
                $element.append(menu);
                $(menu).on('mousedown mousemove', function(e) {
                    e.stopPropagation();
                });

                if ($.inArray("opacity", self.options.menu) !== -1 && menu.find('#layer-opacity').length > 0) {
                    atLeastOne = true;
                    $('.layer-opacity-handle').attr('unselectable', 'on');
                    new Dragdealer('layer-opacity', {
                        x: source.configuration.options.opacity,
                        horizontal: true,
                        vertical: false,
                        speed: 1,
                        steps: 100,
                        handleClass: "layer-opacity-handle",
                        animationCallback: function(x, y) {
                            var percentage = Math.round(x * 100);
                            $("#layer-opacity").find(".layer-opacity-handle").text(percentage);
                            self._setOpacity(self.model.findSource({
                                id: sourceId
                            })[0], percentage / 100.0);
                        }
                    });
                }
                if ($.inArray("zoomtolayer", self.options.menu) !== -1 && menu.find('.layer-zoom').length > 0
                    && self.model.getLayerExtents({
                        sourceId: sourceId,
                        layerId: layerId
                    })) {
                    atLeastOne = true;
                    $('.layer-zoom', menu).removeClass('inactive').on('click', $.proxy(self._zoomToLayer, self));
                } else {
                    $('.layer-zoom', menu).remove();
                }

                if ($.inArray("metadata", self.options.menu) === -1 || menu.find(
                    '.layer-metadata').length === 0 || isNaN(parseInt(source.origId))) {
                    $('.layer-metadata', menu).remove();
                } else {
                    atLeastOne = true;
                    var layer = self.model.findLayer({
                        id: sourceId
                    },
                    {
                        id: layerId
                    });
                    if (layer) {
                        $('.layer-metadata', menu).removeClass('inactive').on('click', $.proxy(self._showMetadata,
                            self));
                    }
                }
                var dims = source.configuration.options.dimensions ? source.configuration.options.dimensions : [];
                if ($.inArray("dimension", self.options.menu) !== -1 && source.type === 'wms'
                    && source.configuration.children[0].options.id === layerId && dims.length > 0) {
                    atLeastOne = true;
                    var lastItem = $('.layer-dimension-checkbox', menu).prev();
                    var dimCheckbox = $('.layer-dimension-checkbox', menu).remove();
                    var dimTitle = $('.layer-dimension-title', menu).remove();
                    var dimBar = $('.layer-dimension-bar', menu).remove();
                    var dimTextfield = $('.layer-dimension-textfield', menu).remove();
                    $.each(dims, function(idx, item) {
                        var chkbox = dimCheckbox.clone();
                        var title = dimTitle.clone();
                        lastItem.after(chkbox);
                        var inpchkbox = chkbox.find('.checkbox');
                        inpchkbox.data('dimension', item);
                        initCheckbox.call(inpchkbox);
                        chkbox.on("click", function(){
                            var me = $(this);
                            var checkbox = me.find(".checkbox");

                            if(checkbox.is(":disabled")){
                                me.addClass("checkboxDisabled");
                            }else{
                                if(checkbox.is(":checked")){
                                    me.removeClass("iconCheckboxActive");
                                    checkbox.get(0).checked = false;
                                }else{
                                    me.addClass("iconCheckboxActive");
                                    checkbox.get(0).checked = true;
                                }
                            }

                            checkbox.trigger('change');
                        });
                        inpchkbox.on('change', function(e) {
                            self._callDimension(source, $(e.target));
                        });
                        title.attr('title', title.attr('title') + ' ' + item.name);
                        title.attr('id', title.attr('id') + item.name);
                        chkbox.after(title);
                        if (item.type === 'single') {
                            var textf = dimTextfield.clone();
                            title.after(textf);
                            textf.val(item.extent);
                            inpchkbox.attr('data-value', item.extent);
                            lastItem = textf;
                        } else if (item.type === 'multiple' || item.type === 'interval') {
                            var bar = dimBar.clone();
                            title.after(bar);
                            bar.removeClass('layer-dimension-bar');
                            bar.attr('id', bar.attr('id') + item.name);
                            bar.find('.layer-dimension-handle').removeClass('layer-dimension-handle').
                                addClass('layer-dimension-' + item.name + '-handle').attr('unselectable', 'on');
                            lastItem = bar;
                            var dimHandler = Mapbender.Dimension(item);
                            var label = $('#layer-dimension-value-' + item.name, menu);
                            new Dragdealer('layer-dimension-' + item.name, {
                                x: dimHandler.partFromValue(dimHandler.getDefault()),
                                horizontal: true,
                                vertical: false,
                                speed: 1,
                                steps: dimHandler.getStepsNum(),
                                handleClass: 'layer-dimension-' + item.name + '-handle',
                                callback: function(x, y) {
                                    self._callDimension(source, inpchkbox);
                                },
                                animationCallback: function(x, y) {
                                    var value = dimHandler.valueFromPart(x);
                                    label.text(value);
                                    inpchkbox.attr('data-value', value);
                                }
                            });
                        } else {
                            Mapbender.error("Source dimension " + item.type + " is not supported.");
                            return;
                        }
                    });
                } else {
                    $('.layer-dimension-checkbox', menu).remove();
                    $('.layer-dimension-title', menu).remove();
                    $('.layer-dimension-bar', menu).remove();
                    $('.layer-dimension-textfield', menu).remove();
                }
                if(!atLeastOne) {
                    self.closeMenu(menu);
                    Mapbender.info(Mapbender.trans('mb.core.layertree.contextmenu.nooption'));
                }
            }

            var $btnMenu = $(e.target);
            var currentLayerId = $btnMenu.parents('li:first').attr("data-id");
            var currentSourceId = $btnMenu.parents('li[data-sourceid]:first').attr("data-sourceid");
            if ($('#layer-menu').length !== 0) {
                var layerIdMenu = $('#layer-menu').attr("data-menuLayerId");
                if (layerIdMenu !== currentLayerId) {
                    createMenu($btnMenu, currentSourceId, currentLayerId);
                }
            } else {
                createMenu($btnMenu, currentSourceId, currentLayerId);
            }
            return false;
        },
        _callDimension: function(source, chkbox) {
            var dimension = chkbox.data('dimension');
            var params = {};
            params[dimension['__name']] = chkbox.attr('data-value');
            if (chkbox.is(':checked')) {
                this.model.resetSourceUrl(source, {
                    'add': params
                },
                true);
            } else if (params[dimension['__name']]) {
                this.model.resetSourceUrl(source, {
                    'remove': params
                },
                true);
            }
            return true;
        },
        _setOpacity: function(source, opacity) {
            this.model.setOpacity(source, opacity);
        },
        _removeSource: function(e) {
            var layer = $(e.currentTarget).closest("li").data();
            var types = this.consts;
            var model = this.model;

            if (layer.sourceid && layer.type) {
                switch (layer.type) {
                    case types.root:
                        model.removeSource({
                            remove: {
                                sourceIdx: {
                                    id: layer.sourceid
                                }
                            }
                        });
                        break;
                    case types.group:
                    case types.simple:
                        model.changeSource({
                            change: {
                                layerRemove: {
                                    sourceIdx: {
                                        id: layer.sourceid
                                    },
                                    layer: {
                                        options: {
                                            id: layer.id
                                        }
                                    }
                                }
                            }
                        });
                        break;
                }
            }

            this._setSourcesCount();
        },
        _showLegend: function(elm) {
        },
        _exportKml: function(elm) {
        },
        _zoomToLayer: function(e) {
            var options = {
                sourceId: $(e.target).parents('div.layer-menu:first').attr("data-menuSourceId"),
                layerId: $(e.target).parents('div.layer-menu:first').attr("data-menuLayerId")
            };
            this.model.zoomToLayer(options);
        },
        _showMetadata: function(e) {
            Mapbender.Metadata.call(
                this.options.target,
                {
                    id: $(
                        e.target).
                        parents(
                            'div.layer-menu:first').
                        attr(
                            "data-menuSourceId")
                },
            {
                id: $(
                    e.target).
                    parents(
                        'div.layer-menu:first').
                    attr(
                        "data-menuLayerId")
            }
            );
        },
        _setSourcesCount: function() {
            var countObj = {};
            $(this.element).find("#list-root li[data-sourceid]").each(function(idx, elm) {
                countObj[$(elm).attr('data-sourceid')] = true;
            });
            var num = 0;
            for (s in countObj)
                num++;
            $(this.element).find('#counter').text(num);
        },
        _removeAllSources: function(e) {
            var self = this;
            if (Mapbender.confirm(Mapbender.trans("mb.core.layertree.confirm.allremove"))) {
                $(this.element).find("#list-root li[data-sourceid]").each(function(idx, elm) {
                    var sourceId = $(elm).attr('data-sourceid');
                    self.model.removeSource({
                        remove: {
                            sourceIdx: {
                                id: sourceId
                            }
                        }
                    });
                });
            }
            this._setSourcesCount();
        },
        /**
         * Default action for mapbender element
         */
        defaultAction: function(callback) {
            this.open(callback);
        },
        /**
         * Opens a dialog with a layertree (if options.type == 'dialog')
         */
        open: function(callback) {
            this.callback = callback ? callback : null;
            if (this.options.type === 'dialog') {
                var self = this;
                if (!this.popup || !this.popup.$element) {
                    this._createTree();
                    this.popup = new Mapbender.Popup2({
                        title: self.element.attr('data-title'),
                        modal: false,
                        resizable: true,
                        draggable: true,
                        closeButton: false,
                        closeOnESC: false,
                        content: [self.element.show()],
                        destroyOnClose: true,
                        width: 350,
                        height: 500,
                        cssClass: 'customLayertree',
                        buttons: {
                            'ok': {
                                label: Mapbender.trans("mb.core.layertree.popup.btn.ok"),
                                cssClass: 'button right',
                                callback: function() {
                                    self.close();
                                }
                            }
                        }
                    });
                    this._reset();
                    this.popup.$element.on('close', $.proxy(this.close, this));
                } else {
                    this._reset();
                    this.popup.open();
                }
            }
        },
        /**
         * closes a dialog with a layertree (if options.type == 'dialog')
         */
        close: function() {
            if (this.options.type === 'dialog') {
                if (this.popup) {
                    $("ul.layers:first", this.element).empty();
                    $(this.element).hide().appendTo("body");
                    this._unSortable();
                    this.created = false;
                    if (this.popup.$element) {
                        this.popup.destroy();
                    }
                    this.popup = null;
                }
            }
            this.callback ? this.callback.call() : this.callback = null;
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
