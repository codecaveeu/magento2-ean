define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';
        rendererList.push(
            {
                type: 'ean',
                component: 'CodeCave_EAN/js/view/payment/method-renderer/ean-method'
            }
        );
        return Component.extend({});
    }
);
