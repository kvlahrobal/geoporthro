/**
 *
 * @author Andriy Oblivantsev <eslider@gmail.com>
 */
/**
 * Form helper plugin for jQuery
 *
 * @author Andriy Oblivantsev <eslider@gmail.com>
 * @copyright 02.02.2015 by WhereGroup GmbH & Co. KG
 *
 */
$.fn.formData = function(values) {
    var form = $(this);
    var inputs = $(':input', form).get();
    var hasNewValues = typeof values == 'object';
    var textElements = $(".form-group.text", form);


    if (hasNewValues) {

        $.each(textElements, function() {
            var textElementContainer = $(this);
            var textElement = $('.text', textElementContainer);
            var declaration = textElementContainer.data('item');
            if(declaration.hasOwnProperty('text')) {
                var html = "";
                try {
                    var data = values;
                    eval('html=' + declaration.text + ';');
                } catch (e) {
                    console.error("The defenition", declaration, " of ", textElement, ' is erroneous.', e);
                }
                textElement.html(html);
            }
        });

        $.each(inputs, function() {
            var input = $(this);
            var value = values[this.name];
            var declaration = input.data('declaration');

            if(values.hasOwnProperty(this.name)) {

                switch (this.type) {
                    case 'select-multiple':
                        //var declaration = input.data('declaration');
                        var type = declaration.fieldType ? declaration.fieldType : 'text';

                        if(type == 'text' && value ) {
                            var separator = declaration.separator ? declaration.separator : ',';
                            var vals = $.isArray(value) ? value : value.split(separator);
                            $.each(vals, function(i, optionValue) {
                                $("option[value='" + optionValue + "']", input).prop("selected", true);
                            });
                        } else {
                            input.val(value);
                        }
                        break;

                    case 'checkbox':
                        input.prop('checked', value !== null && value);
                        break;
                    case 'radio':
                        if (value === null) {
                            input.prop('checked', false);
                        } else if (input.val() == value) {
                            input.prop("checked", true);
                        }
                        break;
                    default:
                        input.val(value);
                }
            }
        });
        return form;
    } else {
        values = {};
        var firstInput;
        $.each(inputs, function() {
            var input = $(this);
            var value;
            var declaration = input.data('declaration');

            if(this.name == ""){
                return;
            }

            switch (this.type) {
                case 'checkbox':
                case 'radio':
                    if(values.hasOwnProperty(this.name) && values[this.name] != null){
                        return;
                    }
                    value = input.is(':checked') ? input.val() : null;
                    break;
                default:
                    value = input.val();
            }

            if(value === ""){
                value = null;
            }

            if(declaration){
                if(declaration.hasOwnProperty('mandatory') && declaration.mandatory ){
                    var isDataReady = false;
                    if(typeof declaration.mandatory === "function"){
                        isDataReady = declaration.mandatory(input, declaration, value);
                    } else{
                        isDataReady = input.data('warn')(value);
                    }
                    if(!isDataReady && !firstInput && input.is(":visible")){
                        firstInput = input;
                        input.focus();
                    }
                }
                values[this.name] = value;
            }else{
                values[this.name] = value;
            }

        });
        return values;
    }
};

$.fn.disableForm = function() {
    var form = this;
    var inputs = $(" :input", form);
    form.attr('readonly', true);
    form.css('cursor', 'wait');
    $.each(inputs, function(idx, el) {
        var $el = $(el);
        if($el.is(':checkbox') || $el.is(':radio') || $el.is('select')) {
            $el.attr('disabled', 'disabled');
        } else {
            $el.attr('readonly', 'true');
        }
    })
};

$.fn.enableForm = function() {
    var form = this;
    var inputs = $(" :input", form);
    form.css('cursor', '');
    $.each(inputs, function(idx, el) {
        var $el = $(el);
        if($el.is(':checkbox') || $el.is(':radio') || $el.is('select')) {
            $el.removeAttr('disabled', 'disabled');
        } else {
            $el.removeAttr('readonly', 'true');
        }
    })
};

