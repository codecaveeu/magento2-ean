# CodeCave_EAN architecture

`CodeCave_EAN` (package `codecave/magento2-ean`) adds one payment method, code `ean`. At checkout the buyer types
an EAN number, an institution and a reference instead of paying; the order keeps those three values and shows them on
the order pages, in the order e-mails and in the PDFs. No money moves: the method has no capture, refund or gateway
call (`etc/di.xml:4-12` configures no command pool).

Described from `master` at `96480ac` (tag `1.0.1`) on 2026-10-28. Runtime facts were checked in a hub lane on
Magento 2.4.6-p15; core line numbers (`vendor/magento/...`) are from that version.

## How this folder works

- One file per area, in reading order. Each area has a Judge state component `arch-<area>`: yellow while the area
  changes, green when its file matches the code.
- Every statement about behaviour cites the code as `path:line`. A change to an area updates its file in the same
  change as the code.
- Flows are FlowKit blocks (fenced as `flowkit`); Judge draws them.
- [`features.md`](features.md) is the feature register: one entry per feature, with how to use it, how to check it and
  its state.

| File | What it covers |
|---|---|
| [`01-layout.md`](01-layout.md) | Files, registration, Composer package, DI wiring and which area loads what |
| [`02-payment-method.md`](02-payment-method.md) | The `ean` method: defaults, admin settings, what it can do, order status at placement |
| [`03-checkout.md`](03-checkout.md) | The storefront form (Luma Knockout renderer) and what the browser sends |
| [`04-payment-data.md`](04-payment-data.md) | The observer, the four stored keys, where they are stored, the admin order path |
| [`05-order-display.md`](05-order-display.md) | The info block and its two templates, the admin form block, e-mails and PDFs |
| [`06-flows.md`](06-flows.md) | EAN details from checkout or the admin to the order and back to the screens |
| [`features.md`](features.md) | The feature register |

## Parts of the module

| Part | Files |
|---|---|
| Payment method | `etc/config.xml`, `etc/payment.xml`, `etc/di.xml`, `etc/adminhtml/system.xml`, `Model/Config/Source/Order/Status/Pendingpayment.php` |
| Storefront checkout | `view/frontend/layout/checkout_index_index.xml`, `view/frontend/web/js/view/payment/ean.js`, `view/frontend/web/js/view/payment/method-renderer/ean-method.js`, `view/frontend/web/template/payment/ean.html`, `Model/Ui/ConfigProvider.php`, `etc/frontend/di.xml` |
| Payment data | `etc/events.xml`, `Observer/DataAssignObserver.php` |
| Order display | `Block/Info/Ean.php`, `Block/Adminhtml/Form/Ean.php`, `view/adminhtml/templates/info/ean.phtml`, `view/frontend/templates/info/ean.phtml`, `view/adminhtml/templates/form/ean.phtml` |

Not in this module: database schema, setup patches, cron, message queues, web API routes, GraphQL, console commands,
plugins, i18n CSV files and tests.

## Who uses it

partyvikings.dk: its `origin/main` (`69bbf95d0f`) requires `"codecave/magento2-ean": "1.0.0"` (`composer.json:40`),
locks it at `1926837` and enables `CodeCave_EAN` in `app/etc/config.php`. The shop adds what this module lacks: a Hyvä
Checkout form (`app/design/frontend/CodeCave/partyvikings/Hyva_CheckoutCodeCaveEAN/templates/component/payment/method/ean.phtml`)
and `payment/ean/can_use_internal = 1` for admin orders (`app/code/PartyVikings/Core/etc/config.xml:10-13`).
