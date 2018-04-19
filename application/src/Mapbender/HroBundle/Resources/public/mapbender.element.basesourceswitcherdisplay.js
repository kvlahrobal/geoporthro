(function($) {
    $.widget("mapbender.mbBaseSourceSwitcherDisplay", {
        options: {},
        targetEl: null,
        _create: function() {
            if (!Mapbender.checkTarget("mbBaseSourceSwitcherDisplay", this.options.target)) {
                return;
            }
            var self = this;
            Mapbender.elementRegistry.onElementReady(this.options.target, $.proxy(self._setup, self));
        },
        _setup: function() {
            var self = this;
            this.targetEl = $('#' + this.options.target).data('mapbenderMbBaseSourceSwitcher');
            var cprContent=this.targetEl.getCpr();  //Set Start cpr ->
            var me = $(this.element);
            var element = me.find('a');
            element.html(cprContent.name);
            element.attr("href",cprContent.url);    // <-
            element.attr("target","_blank");    // <-
            element.attr("title","Quelle öffnen");    // <-
            
            $(document).bind('mbbasesourceswitchergroupactivate', $.proxy(self._display, self));
            $(document).bind('mbbasesourceswitcherready', $.proxy(self._displayDefault, self));
        },
        _display: function(e) {
            this.targetEl = $('#' + this.options.target).data('mapbenderMbBaseSourceSwitcher');
            var cprContent=this.targetEl.getCpr();  //Set Start cpr ->
            var me = $(this.element);
            var element = me.find('a');
            element.html(cprContent.name);
            element.attr("href",cprContent.url);    // <-
            element.attr("target","_blank");    // <-
            element.attr("title","Quelle öffnen");    // <-
        },
        _destroy: $.noop
    });

})(jQuery);
