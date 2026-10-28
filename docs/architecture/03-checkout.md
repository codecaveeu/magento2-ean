# Storefront checkout

The module ships one storefront form: a Knockout renderer for the Luma checkout (`Magento_Checkout`). It ships nothing
for Hyvä Checkout; a shop on Hyvä Checkout brings its own form (partyvikings.dk does, see `README.md`).

## How the renderer is wired

| Step | File | What happens |
|---|---|---|
| 1 | `view/frontend/layout/checkout_index_index.xml:19-25` | Adds component `CodeCave_EAN/js/view/payment/ean` under `checkout.root` > billing-step > payment > renders, with method `ean` and `isBillingAddressRequired` true |
| 2 | `view/frontend/web/js/view/payment/ean.js:11-16` | Pushes `{type: 'ean', component: 'CodeCave_EAN/js/view/payment/method-renderer/ean-method'}` into `Magento_Checkout/js/model/payment/renderer-list` |
| 3 | `view/frontend/web/js/view/payment/method-renderer/ean-method.js:10-17` | Extends `Magento_Checkout/js/view/payment/default`, template `CodeCave_EAN/payment/ean` |
| 4 | `view/frontend/web/template/payment/ean.html` | Radio button, the three fields, the billing address region, agreements and the Place Order button |
| 5 | `Model/Ui/ConfigProvider.php:12-21`, `etc/frontend/di.xml:3-9` | Adds `payment.ean` to `window.checkoutConfig`, as an empty array |

`isBillingAddressRequired` gives the method its own billing address form when the store shows the billing address on
the payment method (`vendor/magento/module-checkout/Block/Checkout/LayoutProcessor.php:241-250`, `270-283`).

## The form

All in `view/frontend/web/template/payment/ean.html`, inside `<form data-role="ean-form">` (line 14).

| Label | Input name | Knockout value | Rule in the browser | Lines |
|---|---|---|---|---|
| EAN-nummer | `payment[ean_number]` | `eanNumber` | required, exactly 13 characters, no blanks | 19-34 (rule on 28) |
| Institution | `payment[ean_ins]` | `eanIns` | required | 36-51 (rule on 45) |
| Reference | `payment[ean_ref]` | `eanRef` | required | 52-67 (rule on 61) |

- The rules on line 28 are `required`, `validate-length`, `minlength` 13, `maxlength` 13 and `no-whitespace`; none
  of them asks for digits only.
- The rules run only in the browser: `validate()` checks the form with jQuery validation
  (`view/frontend/web/js/view/payment/method-renderer/ean-method.js:44-47`), and core's `placeOrder()` calls it before
  sending (`vendor/magento/module-checkout/view/frontend/web/js/view/payment/default.js:129-139`). The server accepts
  any value (`04-payment-data.md`).
- Labels and placeholders go through `i18n` and `$t`, but the module ships no translation CSV. The labels are
  `EAN-nummer`, `Institution`, `Reference` and the placeholders `EAN nr.`, `Institution`, `Reference`.

## What the browser sends

`getData()` (`view/frontend/web/js/view/payment/method-renderer/ean-method.js:28-38`) returns:

```json
{
  "method": "ean",
  "additional_data": {
    "ean_number": "<eanNumber>",
    "ean_company": "<eanCompany>",
    "ean_ins": "<eanIns>",
    "ean_ref": "<eanRef>"
  }
}
```

Core's place-order action posts it as `paymentMethod` to `/V1/carts/mine/payment-information` or
`/V1/guest-carts/:quoteId/payment-information`
(`vendor/magento/module-checkout/view/frontend/web/js/action/place-order.js:23-29`). From there
`04-payment-data.md` takes over.

## Dead code

- `eanCompany` is an observable (`view/frontend/web/js/view/payment/method-renderer/ean-method.js:14`, `22`) that no
  input binds, so `ean_company` is always sent empty.
- `getMailingAddress()` reads `window.checkoutConfig.payment.checkmo.mailingAddress`
  (`view/frontend/web/js/view/payment/method-renderer/ean-method.js:50-52`); the template never calls it.
- `getCode()` returns the literal `'ean'` (`view/frontend/web/js/view/payment/method-renderer/ean-method.js:40-42`).
- `ConfigProvider::getConfig()` returns an empty `payment.ean` entry (`Model/Ui/ConfigProvider.php:14-20`); the
  renderer reads nothing from it.
