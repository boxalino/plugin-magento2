define([
    'jquery',
    'underscore',
    'jquery/ui',
    'mage/mage'
], function ($, _) {
    'use strict';

    /**
     * Check whether the incoming string is not empty or if doesn't consist of spaces.
     *
     * @duplicate $.mage.quickSearch
     */
    function isEmpty(value) {
        return value.length === 0 || value == null || /^\s+$/.test(value);
    }

    $.widget('boxalino.searchAllowEmpty', $.mage.quickSearch, {
        options:{
            minSearchLength: 0,
            submitBtn: 'button[type="submit"]',
            suggestionDelay: 305 // bigger than $.mage.quickSearch.options.suggestionDelay value (default 300)
        },

        /**
         * connect to quickSearch defined elements
         * @private
         */
        _create: function() {
            this.searchForm = $(this.options.formSelector);
            this.submitBtn = this.searchForm.find(this.options.submitBtn)[0];
            this.element = $(this.options.element);

            this.submitBtn.disabled = false;

            _.bindAll(this, '_onPropertyChange', '_onSubmit');
            this.element.on("input propertychange", _.debounce(this._onPropertyChange, this.options.suggestionDelay));

            this.searchForm.on('submit', $.proxy(function (e) {
                this._onSubmit(e);
            }, this));
        },

        _onPropertyChange: function () {
            this.submitBtn.disabled = false;
        },

        _onSubmit: function (e) {
            var value = this.element.val();
            if (isEmpty(value)) {
                /**
                 * ignoring original $.mage.quickSearch submit logic
                 */
                this.searchForm.off("submit");
                this.searchForm.submit();
            }
        }
    });

    return $.boxalino.searchAllowEmpty;
});