define(
    [
        'Magento_Checkout/js/view/payment/default',
        'jquery',
        "mage/validation"
    ],
    function (Component, $) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'CodeCave_EAN/payment/ean',
                eanNumber: '',
                eanCompany: '',
                eanIns: '',
                eanRef: ''
            },

            initObservable: function () {
                this._super()
                    .observe('eanNumber')
                    .observe('eanCompany')
                    .observe('eanIns')
                    .observe('eanRef');
                return this;
            },

            getData: function () {
                return {
                    "method": this.item.method,
                    "additional_data": {
                        'ean_number': this.eanNumber(),
                        'ean_company': this.eanCompany(),
                        'ean_ins': this.eanIns(),
                        'ean_ref': this.eanRef()
                    }
                };
            },

            getCode: function() {
                return 'ean';
            },

            validate: function () {
                var form = 'form[data-role=ean-form]';
                return $(form).validation() && $(form).validation('isValid');
            },

            /** Returns send check to info */
            getMailingAddress: function () {
                return window.checkoutConfig.payment.checkmo.mailingAddress;
            },
        });
    }
);
