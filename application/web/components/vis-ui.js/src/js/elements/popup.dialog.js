(function($) {

    // fake dialogExtend check for ui-dialog
    $.fn["dialog"] = function(arg1, arg2) {
        return this.hasClass('popup-dialog') ? this.popupDialog(arg1, arg2) : this.dialog(arg1, arg2);
    };

    /**
     * jQuery dialog with bootstrap styles
     *
     * @author Andriy Oblivantsev <eslider@gmail.com>
     * @copyright 05.11.2014 by WhereGroup GmbH & Co. KG
     */
    $.widget("vis-ui-js.popupDialog", $.ui.dialog, {

        // track if window is opened
        isOpened: false,

        /**
         * Constructor, runs only if the object wasn't created before
         *
         * @return {*}
         * @private
         */
        _create: function() {
            var element = $(this.element);
            var widget = this;

            element.addClass('popup-dialog');

            // overrides default options
            $.extend(this.options, {
                show: {
                    effect:   "fadeIn",
                    duration: 300
                },
                hide: {
                    effect:   "fadeOut",
                    duration: 300
                }
            });

            //resize dialog height fix
            element.bind('popupdialogresize', function(e, ui) {
                var win = $(e.target).closest('.ui-dialog');
                var height = 0;
                $.each($('> .modal-header, > .modal-body, > .modal-footer', win), function(idx, el) {
                    height += $(el).outerHeight();
                });
                win.height(Math.round(height));
                element.width(element.closest('.ui-dialog').find('> .modal-header').width());
            });

            //resize dialog height fix
            element.bind('popupdialogresizestop', function(e, ui) {
                element.width(element.closest('.ui-dialog').find('> .modal-header').width());
            });

            // prevent key listening outside the dialog
            element.on('keydown', function(e) {
                e.stopPropagation();
            });

            var result = this._super();

            // fake dialogExtend check for ui-dialog
            element.data("ui-dialog",true);
            element.dialogExtend($.extend(true, {
                closable:    true,
                maximizable: true,
                resizible: true,
                //dblclick: true,
                //minimizable: true,
                //modal: true,
                collapsable: true
            }, this.options));

            var dialog = element.closest('.ui-dialog');
            if(this.options.modal){
                var modal = $('<div class="mb-element-modal-dialog"><div class="background" unselectable="on"></div></div>');

                modal.insertBefore(dialog);
                modal.prepend(dialog);
                modal.find('> .background').on('click mousemove mouseout mouseover',function(e){
                    e.preventDefault();
                    e.stopPropagation();
                    return false;
                });
                element.bind('popupdialogclose',function(){
                    modal.fadeOut(function(){
                        modal.remove();
                    });
                });
            }

            // Fullscreen on double click
            $(dialog).dblclick(function(event) {
                var target = $(event.target);
                if(!target.is('.ui-dialog-titlebar, .ui-dialog-title')){
                    return;
                }

                if(element.dialogExtend('state') == 'normal'){
                    element.dialogExtend('maximize');
                }else{
                    element.dialogExtend('restore');
                }
            });

            return result;
        },

        /**
         * Overrides default open method, but adds some Bootstrap classes to the dialog
         * @return {}
         */
        open: function() {
            if(this.isOpened){
                return
            }
            this.isOpened = true;

            var content = $(this.element);
            var dialog = content.closest('.ui-dialog');
            var header = $('.ui-widget-header', dialog);
            var closeButton = $('.ui-dialog-titlebar-close', header);
            var dialogBody = $('.ui-dialog-content', dialog);
            var dialogBottomPane = $('.ui-dialog-buttonpane', dialog);
            var dialogBottomButtons = $('.ui-dialog-buttonset > .ui-button', dialogBottomPane);

            // Marriage of jQuery UI and Bootstrap
            dialog.addClass('modal-content');
            dialogBottomPane.addClass('modal-footer');
            dialogBottomButtons.addClass('button');
            header.addClass('modal-header');
            closeButton.addClass('close');
            dialogBody.addClass('modal-body');

            // Set as mapbender element
            dialog.addClass('mb-element-popup-dialog');
            dialogBottomButtons.addClass('btn');

            // Fix switch between windows
            if(dialog.css('z-index') == "auto"){
                dialog.css('z-index',1);
            }

            return this._super();
        }
    });

})(jQuery);