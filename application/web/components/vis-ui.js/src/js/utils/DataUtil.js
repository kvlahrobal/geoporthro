/**
 *
 * @author Andriy Oblivantsev <eslider@gmail.com>
 * @copyright 25.08.2015 by WhereGroup GmbH & Co. KG
 */

window.DataUtil = new function() {

    var self = this;

    /**
     * Check and replace values recursive if they should be translated.
     * For checking used "translationReg" variable
     *
     *
     * @param items
     */
    self.eachItem = function(items, callback) {
        var isArray = items instanceof Array;
        if(isArray) {
            for (var k in items) {
                self.eachItem(items[k], callback);
            }
        } else {
            if(typeof items["type"] !== 'undefined') {
                callback(items);
            }
            if(typeof items["children"] !== 'undefined') {
                self.eachItem(items["children"], callback);
            }
        }
    };

    /**
     * Check if object has a key
     *
     * @param obj
     * @param key
     * @returns {boolean}
     */
    self.has = function(obj, key) {
        return typeof obj[key] !== 'undefined';
    };

    /**
     * Get value from object by the key or return default given.
     *
     * @param obj
     * @param key
     * @param defaultValue
     * @returns {*}
     */
    self.getVal = function(obj, key, defaultValue) {
        return has(obj, key) ? obj[key] : defaultValue;
    }
};