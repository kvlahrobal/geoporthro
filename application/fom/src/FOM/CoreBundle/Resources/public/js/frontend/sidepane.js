$(function() {

    var switchButton = $(".toggleSideBar");
    var sidePane = switchButton.closest("div.sidePane");
    var templateWrapper = $('#templateWrapper');
    var speed = 300;
    var animation = {};

    sidePane.data('isOpened', true);

    sidePane.css({
        '-webkit-transition': 'none !important',
        '-moz-transition':    'none !important',
        '-o-transition':      'none !important',
        '-ms-transition':     'none !important',
        'transition':         'none !important'
    });

    // closing bugfix
    sidePane.width(sidePane.width());

    if(sidePane.data('closed')){
        sidePane.data('isOpened', false);
        sidePane.find('.sideContent').css('display','none');
        sidePane.css({
            'transition': 'none'
        });
        sidePane.css({right: (sidePane.outerWidth(true)*-1) + "px"});
    }
    sidePane.width(sidePane.width());
    sidePane.show(0);

    switchButton.on('click', function() {
        var isOpened = sidePane.data('isOpened');
        var align = sidePane.hasClass('right') ? 'right' : 'left';
        if(isOpened) {
            animation[align] =  "-" + sidePane.outerWidth(true) + "px"; //, "swing"];
            sidePane.animate(animation,speed, function() {
                templateWrapper.removeClass("sidePaneOpened");
                sidePane.data('isOpened', !isOpened);
                sidePane.find('.sideContent').css('display','none');
            });
        } else {
            templateWrapper.addClass("sidePaneOpened");
            animation[align] = "0px";
            sidePane.find(".sideContent").css('display','block');
            sidePane.animate(animation, speed, function() {
                sidePane.data('isOpened', !isOpened);
            });
        }
    });
});
