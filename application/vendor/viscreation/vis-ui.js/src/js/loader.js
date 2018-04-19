/**
 *
 * @author Andriy Oblivantsev <eslider@gmail.com>
 */
var requieredFiles = [
    'utils/DataUtil.js',
    'utils/EventDispatcher.js',
    'utils/fn.formData.js',
    'elements/data.result-table.js',
    'elements/date.selector.js',
    'elements/geo.toolset.js',
    'elements/popup.dialog.js',
    'elements/tab.navigator.js',
    'elements/confirm.dialog.js',
    'jquery.form.generator.js'
];

var loadedFiles = 0;
var onComplete = null;
var sourcesPath = "src/js/";

function checkLoad() {
    loadedFiles++;
    if(requieredFiles.length == loadedFiles) {
        if(onComplete){
            onComplete();
        }
    }
}

function onLoadError(e) {
    console.log("Something goes wrong by load VI UI", this);
}

function loadElements(completeHandler) {
    if(completeHandler){
        onComplete  = completeHandler;
    }
    $.each(requieredFiles, function(i, fileName) {
        jQuery.getScript(sourcesPath+fileName + "?ver=" + Math.random(), checkLoad).error(onLoadError);
    })
}

