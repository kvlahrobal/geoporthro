$(function() {    
        var popup;
    // Edit element
    $(".showReport").bind("click", function() {
        var self = $(this);
        if (popup) {
            popup = popup.destroy();
        }
        popup = new Mapbender.Popup2({
            title: Mapbender.trans("mapbender.workflowbundle.scheduler.report.popup.title"),
            draggable: true,
            modal: false,
            closeButton: true,
            closeOnESC: true,
            resizable: true,
            width: 800,
            height: 600,
            content: [
                $.ajax({
                    url: self.attr("data-url")
                })
            ],
            buttons: {
                'cancel': {
                    label: Mapbender.trans("mapbender.workflowbundle.scheduler.report.popup.button.cancel"),
                    cssClass: 'button buttonCancel critical right',
                    callback: function() {
                        this.close();
                    }
                }
            }
        });
        return false;
    });
});