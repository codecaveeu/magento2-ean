# Order display

## Blocks

| Block | Set by | Template | Used for |
|---|---|---|---|
| `CodeCave\EAN\Block\Info\Ean` (extends `Magento\Payment\Block\Info`) | `infoBlockType` (`etc/di.xml:7`) | `CodeCave_EAN::info/ean.phtml` (`Block/Info/Ean.php:12`) | Every place Magento shows an order's payment |
| `CodeCave\EAN\Block\Adminhtml\Form\Ean` (extends `Magento\Payment\Block\Form`) | `formBlockType` (`etc/di.xml:8`) | `CodeCave_EAN::form/ean.phtml` (`Block/Adminhtml/Form/Ean.php:9`) | The EAN fields in admin Create New Order |

`Block\Info\Ean::toPdf()` sets the same template and returns `toHtml()` (`Block/Info/Ean.php:14-18`), so the PDF gets
the same HTML as the screen.

## Which template renders where

`CodeCave_EAN::info/ean.phtml` exists twice, one per area, and Magento picks the one of the area it renders in.

| Where | Area | Template | Proof |
|---|---|---|---|
| Admin order, invoice, shipment and credit memo pages | adminhtml | `view/adminhtml/templates/info/ean.phtml` | `vendor/magento/module-sales/Block/Adminhtml/Order/Payment.php:63`, placed by `sales_order_view.xml`, `sales_order_invoice_view.xml`, `sales_order_creditmemo_view.xml` and `adminhtml_order_shipment_view.xml` among others |
| Admin PDFs (invoice, shipment, credit memo) | adminhtml | `view/adminhtml/templates/info/ean.phtml`, through `toPdf()` | `vendor/magento/module-sales/Model/Order/Pdf/AbstractPdf.php:467-469` |
| Customer account order pages | frontend | `view/frontend/templates/info/ean.phtml` | `vendor/magento/module-sales/Block/Order/Info.php:72` |
| Order, invoice, shipment and credit memo e-mails | frontend (emulated) | `view/frontend/templates/info/ean.phtml` | `vendor/magento/module-payment/Helper/Data.php:221-232`, called from `vendor/magento/module-sales/Model/Order/Email/Sender/OrderSender.php:170` and the invoice, shipment and credit memo senders |

## What each template shows

Both print the method title, then three label and value pairs read from the payment's additional information, all
escaped with `escapeHtml`.

| Template | Order of fields | Label text |
|---|---|---|
| adminhtml (`view/adminhtml/templates/info/ean.phtml:5-16`) | EAN number, Reference, Institution | `EAN-nummer`, `Reference`, `Institution`, no colon |
| frontend (`view/frontend/templates/info/ean.phtml:11-25`) | EAN number, Institution, Reference | `EAN-nummer:`, `Institution:`, `Reference:` |

The frontend template also carries two HTML comments for `ean_company` (lines 13-15) and `ean_rekv` (lines 19-21);
their PHP is commented out, so the e-mail HTML holds two empty comment blocks. `ean_rekv` is not written anywhere in
the module.

Rendered in the 2.4.6-p15 lane (2026-10-28) for a payment with `ean_number` `123`, `ean_ref` `PROBE-REF`,
`ean_ins` `Probe Institution`:

```text
adminhtml:  EAN | EAN-nummer 123 | Reference PROBE-REF | Institution Probe Institution
frontend:   EAN | EAN-nummer: 123 | Institution: Probe Institution | Reference: PROBE-REF
```

## PDFs

The PDF code does not split this block into rows: the template has no `{{pdf_row_separator}}`, so the whole HTML is
one entry; `<br>` becomes a newline, the text is wrapped at 45 characters and tags are stripped
(`vendor/magento/module-sales/Model/Order/Pdf/AbstractPdf.php:467-474`, `566-573`). How that looks on paper was not
checked.

## Admin Create New Order form

`view/adminhtml/templates/form/ean.phtml` holds three required inputs, `payment[ean_number]` (label `EAN-Nummer`),
`payment[ean_ref]` and `payment[ean_ins]` (lines 7-33), filled from the quote payment through
`Block\Adminhtml\Form\Ean::getAdditionalInfo()` (`Block/Adminhtml/Form/Ean.php:12-15`). The fieldset
`payment_form_ean` starts hidden (`view/adminhtml/templates/form/ean.phtml:6`) and core's order-create script shows
the fieldset of the picked method by that id (`vendor/magento/module-sales/view/adminhtml/web/order/create/scripts.js:530-533`).
There is no 13-character rule here.

The form appears only when `payment/ean/can_use_internal` is 1
(`vendor/magento/module-sales/Block/Adminhtml/Order/Create/Billing/Method/Form.php:52`); this module does not set it
(`02-payment-method.md`).

## Known limits

- The two info templates disagree on field order and colons.
- No translation CSV: `EAN-nummer` stays Danish and `Reference`, `Institution` stay English in every locale.
- The admin info template's docblock names `\CodeCave\Ean\Block\Info\Ean` (`view/adminhtml/templates/info/ean.phtml:2`),
  a different case than the class `CodeCave\EAN\Block\Info\Ean`; it is only a comment.
