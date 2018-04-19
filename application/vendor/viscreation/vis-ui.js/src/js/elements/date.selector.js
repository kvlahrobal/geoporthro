(function($) {

    /**
     * Date selector based on $.ui.datepicker
     *
     * Widget can't be extended:
     * http://bugs.jqueryui.com/ticket/6228
     *
     * @author Andriy Oblivantsev <eslider@gmail.com>
     */
    $.widget("vis-ui-js.dateSelector", {
        _init: function() {
            var widget = this;
            var element = widget.element;
            var dialog;
            var datePicker = element.datepicker($.extend({
                changeMonth:       true,
                changeYear:        true,
                gotoCurrent:       true,
                firstDay:          1, //showWeek:          true,
                dayNamesMin:       ["So", "Mo", "Di", "Mi", "Do", "Fr", "Sa"],
                monthNamesShort:   ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Okt", "Nov", "Dez"], //showButtonPanel: true,
                dateFormat:        'dd.mm.yy',
                onChangeMonthYear: function(input, instance) {
                    widget._trigger('changeMonthYear');
                    setTimeout(function() {
                        widget._trigger('render');
                    }, 1);
                }
            }, widget.options)).data('datepicker');

            dialog = datePicker.dpDiv;
            dialog.on('show', function(e) {
                widget._trigger('show');
                widget._trigger('render');
            });
            dialog.on('hide', function(e) {
                widget._trigger('hide');
            });

            element.bind('dateselectorrender',function(e){
                var header = $(".ui-datepicker-header", dialog);
                var headerButtons = header.find('a').addClass('button');
                header.find('select').addClass('form-control');
            });

            dialog.addClass('dropdown-menu').addClass('modal-body');
        }
    });

})(jQuery);