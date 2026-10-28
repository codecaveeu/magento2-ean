# Flows

Two flows. The order state flow is in `02-payment-method.md`; this file holds the path of the EAN details from the
form to the order and back to the screens. Core line numbers are from Magento 2.4.6-p15.

## EAN details from checkout or the admin to the order

The two columns are the two ways an EAN order is made with this module alone. Both meet at the same observer.

```flowkit
{
  "flowkit": 1,
  "id": "ean-details-to-order",
  "title": "EAN details from the form to the order",
  "subtitle": "Luma checkout and admin Create New Order, as this module ships them.",
  "columns": [
    { "id": "shop", "title": "Luma checkout" },
    { "id": "admin", "title": "Admin Create New Order" }
  ],
  "rows": [
    { "id": "start", "label": "Who starts" },
    { "id": "form", "label": "Form" },
    { "id": "check", "label": "Check" },
    { "id": "send", "label": "Sent as" },
    { "id": "assign", "label": "On the quote" },
    { "id": "order", "label": "On the order" },
    { "id": "show", "label": "Shown" }
  ],
  "nodes": [
    { "id": "s-start", "col": "shop", "row": "start", "kind": "start", "title": "Buyer picks EAN at checkout" },
    { "id": "s-form", "col": "shop", "row": "form", "title": "Knockout form with three fields", "body": "payment/ean.html: EAN-nummer, Institution, Reference, all required." },
    { "id": "s-check", "col": "shop", "row": "check", "kind": "question", "title": "Fields pass the browser rules?" },
    { "id": "s-stop", "col": "shop", "row": "check", "lane": "side", "kind": "outcome", "title": "Order is not sent", "body": "EAN number must be 13 characters with no blanks; the others must not be empty." },
    { "id": "s-send", "col": "shop", "row": "send", "title": "additional_data in payment-information", "body": "getData() sends ean_number, ean_company (always empty), ean_ins and ean_ref to the cart or guest-cart REST route." },
    { "id": "s-assign", "col": "shop", "row": "assign", "title": "Observer writes four keys", "body": "importData() calls assignData(), which fires payment_method_assign_data_ean. DataAssignObserver sets the keys on the quote payment. No server check." },
    { "id": "s-order", "col": "shop", "row": "order", "title": "Keys copied to the order payment", "body": "ToOrderPayment adds method_title; sales_order_payment.additional_information holds them as JSON. State and status: processing." },
    { "id": "s-show", "col": "shop", "row": "show", "kind": "outcome", "title": "Info block on pages, e-mails and PDFs", "body": "Admin pages and PDFs use the adminhtml template; account pages and e-mails use the frontend one." },

    { "id": "a-start", "col": "admin", "row": "start", "kind": "start", "title": "Admin creates an order" },
    { "id": "a-form", "col": "admin", "row": "form", "kind": "question", "title": "can_use_internal is 1?" },
    { "id": "a-off", "col": "admin", "row": "form", "lane": "side", "kind": "outcome", "title": "EAN is not offered", "body": "This module does not set it. partyvikings.dk sets it in its own config.xml." },
    { "id": "a-check", "col": "admin", "row": "check", "title": "form/ean.phtml with three required inputs", "body": "No length rule. Posts payment[ean_number], payment[ean_ref] and payment[ean_ins]." },
    { "id": "a-send", "col": "admin", "row": "send", "title": "Loose keys moved into additional_data", "body": "Quote Payment::convertPaymentData() moves every key it does not know into additional_data." },
    { "id": "a-assign", "col": "admin", "row": "assign", "title": "Same observer writes four keys", "body": "ean_company stays empty: the admin form has no such field." },
    { "id": "a-order", "col": "admin", "row": "order", "title": "Keys copied to the order payment", "body": "Same copy as the checkout, same processing state and status." },
    { "id": "a-show", "col": "admin", "row": "show", "kind": "outcome", "title": "Info block on pages, e-mails and PDFs", "body": "Same templates as the checkout column." }
  ],
  "edges": [
    { "from": "s-start", "to": "s-form" },
    { "from": "s-form", "to": "s-check", "label": "Place Order" },
    { "from": "s-check", "to": "s-send", "kind": "yes" },
    { "from": "s-check", "to": "s-stop", "kind": "no" },
    { "from": "s-send", "to": "s-assign", "label": "REST" },
    { "from": "s-assign", "to": "s-order", "label": "order placed" },
    { "from": "s-order", "to": "s-show" },
    { "from": "a-start", "to": "a-form" },
    { "from": "a-form", "to": "a-check", "kind": "yes" },
    { "from": "a-form", "to": "a-off", "kind": "no" },
    { "from": "a-check", "to": "a-send", "label": "Submit Order" },
    { "from": "a-send", "to": "a-assign" },
    { "from": "a-assign", "to": "a-order", "label": "order placed" },
    { "from": "a-order", "to": "a-show" }
  ],
  "footer": { "source": "Drawn from 96480ac: ean.html, ean-method.js, form/ean.phtml, DataAssignObserver.php, and Magento 2.4.6-p15 Quote Payment, ToOrderPayment and Order Payment." }
}
```

| Step | Checkout proof | Admin proof |
|---|---|---|
| Form | `view/frontend/web/template/payment/ean.html:19-67` | `vendor/magento/module-sales/Block/Adminhtml/Order/Create/Billing/Method/Form.php:52`, `view/adminhtml/templates/form/ean.phtml:7-33` |
| Check | `view/frontend/web/template/payment/ean.html:28`, `45`, `61`; `view/frontend/web/js/view/payment/method-renderer/ean-method.js:44-47` | `required-entry` classes only (`view/adminhtml/templates/form/ean.phtml:12`, `21`, `30`) |
| Sent as | `view/frontend/web/js/view/payment/method-renderer/ean-method.js:28-38`, `vendor/magento/module-checkout/view/frontend/web/js/action/place-order.js:23-29` | `vendor/magento/module-quote/Model/Quote/Payment.php:212-235` |
| On the quote | `vendor/magento/module-quote/Model/Quote/Payment.php:187-202`, `etc/events.xml:3-5`, `Observer/DataAssignObserver.php:21-30` | Same |
| On the order | `vendor/magento/module-quote/Model/Quote/Payment/ToOrderPayment.php:69-73`, `vendor/magento/module-sales/Model/Order/Payment.php:365-398` | Same |
| Shown | `05-order-display.md` | Same |

## Where the stored details are read back

| Reader | Area | Template |
|---|---|---|
| Admin order, invoice, shipment and credit memo pages | adminhtml | `view/adminhtml/templates/info/ean.phtml` |
| Admin PDFs | adminhtml | `view/adminhtml/templates/info/ean.phtml` through `Block\Info\Ean::toPdf()` |
| Customer account order pages | frontend | `view/frontend/templates/info/ean.phtml` |
| Sales e-mails | frontend, emulated per store | `view/frontend/templates/info/ean.phtml` |
| Admin Create New Order | adminhtml | `view/adminhtml/templates/form/ean.phtml`, prefilled from the current quote payment (`Block/Adminhtml/Form/Ean.php:12-15`) |

Proof for each row is in `05-order-display.md`.
