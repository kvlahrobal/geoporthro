vis-ui.js
=========

This package provides JavaScript UI generator based on [Bootstrap](http://www.bootstrap.com) and [jQuery UI](www.jquery-ui.com). 

## Features

* Generate form and input elements
* Check and fill form 
* Tab navigation

## Examples 

* [Basic](https://rawgit.com/eSlider/vis-ui.js/master/index.html)

## Elements

Description how to use element generator.

---
### Input 
```javascript
$("<div/>").generateElements({
    type: "input",
    name: "name",
    title: "Input",
    placeholder: "Enter the name",
    mandatory: true,
    cssClass: "input-css", 
    css: {width: "80%"}
})
```
#### Options
* name:  Field name 
* title: Title string
* placeholder: Place holder in the input field
* mandatory: Mandatory field: true, false or ReExpr ("/^\d+$/g" - only decimals)
* cssClass: CSS class name
* css: Custom CSS styles object. Example: {width: "80%"}
* value: Default value

---
### Text area  
```javascript
$("<div/>").generateElements({
    type: "textArea",
    name: "description" ,
    title: "Description",
    placeholder: "Enter the description",
})
```
#### Options
* name:  Field name 
* title: Title string
* placeholder: Place holder in the input field
* mandatory: Mandatory field: true, false or ReExpr ("/^\d+$/g" - only decimals)
* cssClass: CSS class name
* css: Custom CSS styles object. Example: {width: "80%"}
* value: Default value

---

### Check box 
```javascript
$("<div/>").generateElements({
    type: "checkbox",
    name: "check1",
    title: "Checkbox",
    mandatory: true,
    cssClass: "input-css", 
    css: {width: "80%"}
})
```
#### Options
* name:  Field name 
* title: Title string
* placeholder: Place holder in the input field
* mandatory: Mandatory field: true, false or ReExpr ("/^\d+$/g" - only decimals)
* cssClass: CSS class name
* css: Custom CSS styles object. Example: {width: "80%"}
* value: Default value


---

### Radio button
```javascript
$("<div/>").generateElements({children:[{
        type: "radio",
        name: "yesNo",
        title: "Yes",
        css: {width: 50%}
    },{
        type: "radio",
        name: "yesNo",
        title: "no",
        css: {width: 50%}
}]})
```
#### Options
* name:  Field name 
* title: Title string
* placeholder: Place holder in the input field
* mandatory: Mandatory field: true, false or ReExpr ("/^\d+$/g" - only decimals)
* cssClass: CSS class name
* css: Custom CSS styles object. Example: {width: "80%"}
* value: Default value

### Select
```javascript
$("<div/>").generateElements({type: "select", value: "de", options: {en:"English", de: "German"} })
```
#### Options
* name:  Field name 
* title: Title string
* mandatory: Mandatory field: true, false or ReExpr ("/^\d+$/g" - only decimals)
* cssClass: CSS class name
* css: Custom CSS styles object. Example: {width: "80%"}
* value: Default value
* options: key/values object or array
* multiply: Multiply selection. Default false

---

### Basic usage example:
```javascript

var $div = $("<div/>");
$div.generateElements({children:[{
    type:  'input',
    title: "Input",
    placeholder: "placeholder value",
    mandatory: true
},{
    type:  'input',
    name: "input2",
    title: "Input #2",
    placeholder: "placeholder value #2"
}]});

$div.popupDialog({
    title:       "Demo",
    maximizable: true,
    buttons:     [{
        text:  "OK",
        click: function(e) {
            var div = $(e.currentTarget).closest(".ui-dialog").find(".popup-dialog");
            div.popupDialog('close');
        }
    }]
});
```

## Integration 

Extend composer dependencies with *eslider/vis-ui.js* and update composer.
```sh
composer update
```

Load JS library to HTML.
```html
<script src="web/assets/vis-ui.js/vis-ui.js-built.js"></script>
```

Add CSS styles.
```html
<link media="all" type="text/css" rel="stylesheet" href="web/assets/require.css">
```



Package Managers
----------------

* [Composer](http://packagist.org/packages/viscreation/vis-ui-js): `viscreation/vis-ui.js`
