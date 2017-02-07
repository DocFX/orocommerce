/*jslint nomen:true*/
/*global define*/
define(function(require) {
    'use strict';

    var ProductUnitComponent;
    var BaseComponent = require('oroui/js/app/components/base/component');
    var LoadingMaskView = require('oroui/js/app/views/loading-mask-view');
    var routing = require('routing');
    var _ = require('underscore');
    var $ = require('jquery');
    var __ = require('orotranslation/js/translator');

    ProductUnitComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            productSelector: '.product-product [data-name="field__product"]',
            quantitySelector: '.product-quantity input',
            unitSelector: '.product-unit select',
            routeName: 'oro_product_unit_product_units',
            routingParams: {},
            errorMessage: 'Sorry, an unexpected error has occurred.',
            loadingMaskEnabled: true,
            dropQuantityOnLoad: true,
            defaultValues: null
        },

        /**
         * @property {LoadingMaskView}
         */
        loadingMaskView: null,

        /**
         * @property {jQuery.Element}
         */
        productSelector: null,

        /**
         * @property {jQuery.Element}
         */
        quantitySelector: null,

        /**
         * @property {jQuery.Element}
         */
        unitSelector: null,

        /**
         * {@inheritDoc}
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);

            this.initializeLoadingMask(options);

            this.options._sourceElement
                .on('change', this.options.productSelector, _.bind(this.onProductChange, this));

            this.quantitySelector = this.options._sourceElement.find(this.options.quantitySelector);
            this.unitSelector = this.options._sourceElement.find(this.options.unitSelector);
            this.productSelector = this.options._sourceElement.find(this.options.productSelector);
            if (!this.productSelector.val()) {
                this._dropValues();
            }
        },

        initializeLoadingMask: function(options) {
            if (options.loadingMaskEnabled) {
                this.loadingMaskView = new LoadingMaskView({container: this.options._sourceElement});
            }
        },

        /**
         * @param {jQuery.Event} e
         */
        onProductChange: function(e) {
            this.unitSelector.trigger('value:changing');
            var value = e.target.value;

            if (!value) {
                this._dropValues();

                return;
            }

            var routeParams = $.extend({}, this.options.routingParams, {'id': value});
            $.ajax({
                url: routing.generate(this.options.routeName, routeParams),
                beforeSend: $.proxy(this._beforeSend, this),
                success: $.proxy(this._success, this),
                complete: $.proxy(this._complete, this),
                errorHandlerMessage: __(this.options.errorMessage),
                error: $.proxy(this._dropValues, this)
            });
        },

        /**
         * @private
         */
        _beforeSend: function() {
            if (this.loadingMaskView) {
                this.loadingMaskView.show();
            }
        },

        /**
         * @private
         */
        _dropValues: function() {
            if (this.options.dropQuantityOnLoad) {
                this.handleQuantityState(true);
            }
            this.handleUnitsState(this.options.defaultValues);
        },

        /**
         * @param {Object} data
         *
         * @private
         */
        _success: function(data) {
            this.handleQuantityState(false);
            this.handleUnitsState(data.units);
        },

        /**
         * @private
         */
        _complete: function() {
            if (this.loadingMaskView) {
                this.loadingMaskView.hide();
            }
        },

        /**
         * @param {Boolean} disabled
         */
        handleQuantityState: function(disabled) {
            this.quantitySelector.prop('disabled', disabled);
            if (this.options.dropQuantityOnLoad) {
                this.quantitySelector.val(null);
            }
        },

        /**
         * @param {Object} units
         */
        handleUnitsState: function(units) {
            var self = this;
            var disabled = _.isEmpty(units);
            var value = self.unitSelector.val();

            this.unitSelector
                .prop('disabled', disabled)
                .val(null)
                .find('option')
                .filter(function() {
                    return this.value || $.trim(this.value).length;
                })
                .remove();

            if (units) {
                $.each(units, function(code, label) {
                    if (!self.unitSelector.find('option[value=' + code + ']').length) {
                        self.unitSelector.append($('<option/>').val(code).text(label));
                    }
                });
                self.unitSelector.find('option[value=""]').hide();
                self.unitSelector.val(value);
                if (self.unitSelector.val() === null) {
                    self.unitSelector.val(_.keys(units)[0]);
                }
            } else {
                self.unitSelector.find('option[value=""]').show();
                self.unitSelector.val('');
            }

            var $unitSelectorContainer = this.unitSelector.inputWidget('container');
            if ($unitSelectorContainer) {
                $unitSelectorContainer.toggleClass('disabled', disabled);
            }

            this.unitSelector
                .trigger('value:changed')
                .trigger('change');
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }

            this.options._sourceElement.off();

            ProductUnitComponent.__super__.dispose.call(this);
        }
    });

    return ProductUnitComponent;
});
