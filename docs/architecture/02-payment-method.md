# Payment method `ean`

## What it is

- Method code `ean` (`Model/Ui/ConfigProvider.php:8`, `etc/config.xml:5`). The method model is the virtual type
  `CodeCaveFacade` (`etc/config.xml:11`), a `Magento\Payment\Model\Method\Adapter` (`etc/di.xml:4`).
- Every configured value (title, active, flags) is read from `payment/ean/<field>` through one config value handler
  (`etc/di.xml:13-29`); `Adapter::getConfiguredValue()` does the lookup
  (`vendor/magento/module-payment/Model/Method/Adapter.php:374-386`).
- Group `offline`, labelled "Offline Payment Methods" (`etc/config.xml:12`, `etc/payment.xml:5-7`); grouped method
  option lists file it under that label (`vendor/magento/module-payment/Helper/Data.php:277-295`).
- `allow_multiple_address` 1 (`etc/payment.xml:10-12`): Magento_Multishipping reads it
  (`vendor/magento/module-multishipping/Model/Payment/Method/Specification/Enabled.php`). The module has no
  storefront form block template (`view/frontend/templates/` holds only `info/`), so a multishipping billing step
  would have no EAN fields; not run.

## Defaults

From `etc/config.xml:5-14`. No `payment/ean/*` row exists in `core_config_data` on a fresh install (0 rows in the
2.4.6-p15 lane), so these are the values in force until an admin saves the group.

| Path | Default | Read by |
|---|---|---|
| `payment/ean/active` | `1` | `Adapter::isActive()` (`vendor/magento/module-payment/Model/Method/Adapter.php:321-324`) |
| `payment/ean/title` | `EAN` | `Adapter::getTitle()`; shown as the method name at checkout and on the order |
| `payment/ean/order_status` | `pending_payment` | `Order\Payment::place()` (`vendor/magento/module-sales/Model/Order/Payment.php:366`), then replaced: see "Order state and status" |
| `payment/ean/instructions` | empty | Nothing in this module reads it |
| `payment/ean/payment_action` | `true` (a string) | `Order\Payment::place()` (`vendor/magento/module-sales/Model/Order/Payment.php:371-388`) |
| `payment/ean/model` | `CodeCaveFacade` | `Magento\Payment\Helper\Data::getMethodInstance()` (`vendor/magento/module-payment/Helper/Data.php:112-125`) |
| `payment/ean/group` | `offline` | Grouped method option lists (`vendor/magento/module-payment/Helper/Data.php:277-295`) |
| `payment/ean/can_use_checkout` | `1` | `Adapter::canUseCheckout()` (`vendor/magento/module-payment/Model/Method/Adapter.php:224-226`) |

Not set, so read as empty or false: `can_use_internal`, `is_offline`, `is_gateway`, `can_initialize`,
`can_capture`, `can_refund`, `can_void`, `can_order`, `can_authorize`, `sort_order`, `allowspecific`.

## Admin settings

Stores > Configuration > Sales > Payment Methods > "European Article Number (EAN)" (`etc/adminhtml/system.xml:6-8`).

| Field | Path | Scope | Effect |
|---|---|---|---|
| Enabled | `payment/ean/active` | default, website (`etc/adminhtml/system.xml:9-13`) | Turns the method on or off |
| Title | `payment/ean/title` | default, website, store view (`etc/adminhtml/system.xml:14-17`) | Name shown at checkout and in the order's payment block |
| New Order Status | `payment/ean/order_status` | default, website (`etc/adminhtml/system.xml:18-22`) | Options are the statuses of state `pending_payment` only (`Model/Config/Source/Order/Status/Pendingpayment.php:10`). At placement the order goes to state `processing`, where such a status is dropped: see below |
| Payment from Applicable Countries | `payment/ean/allowspecific` | default, website (`etc/adminhtml/system.xml:23-27`) | No effect: see "Country settings" |
| Payment from Specific Countries | `payment/ean/specificcountry` | default, website (`etc/adminhtml/system.xml:28-33`) | No effect: see "Country settings" |
| Instructions | `payment/ean/instructions` | default, website, store view (`etc/adminhtml/system.xml:34-37`) | Saved, never shown: no file in the module reads it |
| Sort Order | `payment/ean/sort_order` | default, website (`etc/adminhtml/system.xml:38-42`) | Position in the method list (`vendor/magento/module-payment/Model/PaymentMethodList.php:59-62`) |

## What the method can do

Read on Magento 2.4.6-p15 from the method instance (2026-10-28):

| Question | Answer | Why |
|---|---|---|
| Offered at checkout | yes | `can_use_checkout` 1 (`etc/config.xml:13`) |
| Offered in admin Create New Order | no | `can_use_internal` is not set; `Adapter::canUseInternal()` returns false (`vendor/magento/module-payment/Model/Method/Adapter.php:216-218`) and the admin method form drops the method (`vendor/magento/module-sales/Block/Adminhtml/Order/Create/Billing/Method/Form.php:52`) |
| Capture, refund online, void, authorize, order | no | No `can_*` flag and no command pool (`etc/di.xml:10`) |
| Gateway | no | `is_gateway` not set (`vendor/magento/module-payment/Model/Method/Adapter.php:256-259`) |
| `isOffline()` | false | `is_offline` not set (`vendor/magento/module-payment/Model/Method/Adapter.php:264-267`), although the group is `offline` |
| Server-side validation | none | No validator pool: `Adapter::validate()` returns at once (`vendor/magento/module-payment/Model/Method/Adapter.php:399-405`); a 3-character EAN number passed |

### Invoices and credit memos

An invoice registers as paid at once: the method cannot capture and is not a gateway, so `Invoice::register()` calls
`pay()` (`vendor/magento/module-sales/Model/Order/Invoice.php:620-634`). Refunds can only be offline credit memos
(`can_refund` is not set).

### Country settings

`Adapter::canUseForCountry()` asks the validator pool for a `country` validator and returns true when there is none
(`vendor/magento/module-payment/Model/Method/Adapter.php:329-339`, `141-147`). `etc/di.xml` configures no validator
pool, so the method is offered for every country whatever "Payment from Applicable Countries" says; at runtime
`canUseForCountry('ZZ')` returned true.

## Order state and status

`payment_action` is the string `true`, which is none of `order`, `authorize` or `authorize_capture`, so
`processAction()` does nothing (`vendor/magento/module-sales/Model/Order/Payment.php:453-473`). The order then gets
state `processing`, and the configured status `pending_payment` is not a status of that state, so Magento falls back
to the state's default status (`vendor/magento/module-sales/Model/Order/Payment.php:384-398`). Running
`Payment::place()` on an unsaved order in the 2.4.6-p15 lane (2026-10-28) gave state `processing`, status
`processing`, and no invoice.

```flowkit
{
  "flowkit": 1,
  "id": "ean-order-status",
  "title": "Order state and status when an EAN order is placed",
  "subtitle": "Order\\Payment::place() in Magento_Sales with the defaults of etc/config.xml.",
  "columns": [
    { "id": "place", "title": "Payment::place() for method ean" }
  ],
  "rows": [
    { "id": "start", "label": "Placement" },
    { "id": "action", "label": "Payment action" },
    { "id": "init", "label": "Initialize" },
    { "id": "state", "label": "State" },
    { "id": "status", "label": "Status" },
    { "id": "check", "label": "Status check" },
    { "id": "end", "label": "Result" }
  ],
  "nodes": [
    { "id": "p-start", "col": "place", "row": "start", "kind": "start", "title": "Order::place() calls Payment::place()", "body": "Status is read from payment/ean/order_status: pending_payment." },
    { "id": "p-action", "col": "place", "row": "action", "kind": "question", "title": "payment_action set?" },
    { "id": "p-new", "col": "place", "row": "action", "lane": "side", "kind": "outcome", "title": "State new, configured status kept", "body": "Not reached with the defaults: payment_action is the string true." },
    { "id": "p-init", "col": "place", "row": "init", "kind": "question", "title": "can_initialize set?" },
    { "id": "p-inityes", "col": "place", "row": "init", "lane": "side", "kind": "note", "title": "initialize() would run", "body": "Not reached: can_initialize is not set." },
    { "id": "p-state", "col": "place", "row": "state", "title": "State becomes processing", "body": "processAction('true') matches no action, so no transaction and no invoice." },
    { "id": "p-status", "col": "place", "row": "status", "title": "Status stays pending_payment for now" },
    { "id": "p-check", "col": "place", "row": "check", "kind": "question", "title": "Status belongs to processing?" },
    { "id": "p-keep", "col": "place", "row": "check", "lane": "side", "kind": "outcome", "title": "Configured status kept", "body": "Only if the chosen status is also assigned to state processing." },
    { "id": "p-end", "col": "place", "row": "end", "kind": "outcome", "title": "Order is processing, status processing", "body": "The state's default status replaces the configured one." }
  ],
  "edges": [
    { "from": "p-start", "to": "p-action" },
    { "from": "p-action", "to": "p-init", "kind": "yes" },
    { "from": "p-action", "to": "p-new", "kind": "no" },
    { "from": "p-init", "to": "p-state", "kind": "no" },
    { "from": "p-init", "to": "p-inityes", "kind": "yes" },
    { "from": "p-state", "to": "p-status" },
    { "from": "p-status", "to": "p-check" },
    { "from": "p-check", "to": "p-end", "kind": "no" },
    { "from": "p-check", "to": "p-keep", "kind": "yes" }
  ],
  "footer": { "source": "Drawn from vendor/magento/module-sales/Model/Order/Payment.php:352-405 (Magento 2.4.6-p15) and etc/config.xml at 96480ac." }
}
```

## Known limits

- "New Order Status" does not reach the order (see above).
- The country settings have no effect (see above).
- "Instructions" is never shown.
- Admin orders need `payment/ean/can_use_internal = 1`, which this module does not set and `system.xml` does not
  offer.
- No server-side check of the EAN number: the 13-character rule lives only in the storefront form
  (`view/frontend/web/template/payment/ean.html:28`).
