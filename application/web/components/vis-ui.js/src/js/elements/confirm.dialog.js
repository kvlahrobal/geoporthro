/**
 * Confirm dialog
 *
 * Example:
 *     confirmDialog({html: "Remove?", title: "Please confirm!", onSuccess:function(){
                  return false;
       }});
 * @param options
 * @returns {*}
 */
window.confirmDialog = function(options) {
    var dialog = $("<div class='confirm-dialog'>" + (options.hasOwnProperty('html') ? options.html : "") + "</div>").popupDialog({
        title:       options.hasOwnProperty('title') ? options.title : "",
        maximizable: false,
        dblclick:    false,
        minimizable: false,
        resizable:   false,
        collapsable: false,
        modal:       true,
        buttons:     [{
            text:  "OK",
            click: function(e) {
                if(!options.hasOwnProperty('onSuccess') || options.onSuccess(e) !== false) {
                    dialog.popupDialog('close');
                }
                return false;
            }
        }, {
            text:    "Cancel",
            'class': 'critical',
            click:   function(e) {
                if(!options.hasOwnProperty('onCancel') || options.onCancel(e) !== false) {
                    dialog.popupDialog('close');
                }
                return false;
            }
        }]
    });
    return dialog;
};