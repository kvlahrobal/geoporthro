/**
 * 
 * @param {boolean} nullable true if the radio button value can be set to null.
 */
var initRadioButton = function(nullable) {
    var me = $(this);
    var parent = me.parent(".radioWrapper");
    if (nullable) {
        parent.attr('data-nullable', true);
    }
    if (me.is(":checked")) {
        parent.addClass("iconRadioActive");
    } else {
        parent.removeClass("iconRadioActive");
    }

    if (me.is(":disabled")) {
        parent.addClass("radioboxDisabled");
    } else {
        parent.removeClass("radioboxDisabled");
    }
};
$(function() {
    var toggleRadioBox = function() {
        var me = $(this);
        var radiobox = me.find(".radiobox");
        $('input[type="radio"][name="' + radiobox.attr('name') + '"').each(function() {
            var rdb = $(this);
            var rbgwrp = rdb.parents('.radioWrapper:first');
            var nullable = rbgwrp.attr("data-nullable");
            if (rdb.is(":disabled")) {
                rbgwrp.addClass("radioboxDisabled");
            } else {
                if (rdb.attr('id') === radiobox.attr('id')) {
                    if (nullable) {
                        if (rdb.is(":checked")) {
                            rbgwrp.removeClass("iconRadioActive");
                            rdb.get(0).checked = false;
                        } else {
                            rbgwrp.addClass("iconRadioActive");
                            rdb.get(0).checked = true;
                        }
                    } else {
                        rbgwrp.addClass("iconRadioActive");
                        rdb.get(0).checked = true;
                    }
                } else {
                    rbgwrp.removeClass("iconRadioActive");
                    rdb.get(0).checked = false;
                }
            }
        });
        radiobox.trigger('change');
    };
    $('.radiobox').each(function() {
        initRadioButton.call(this);
    });
    $(document).on("click", ".radioWrapper", toggleRadioBox);
});