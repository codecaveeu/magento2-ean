# Payment data

## The observer

`Adapter::assignData()` fires `payment_method_assign_data_ean`
(`vendor/magento/module-payment/Model/Method/Adapter.php:621-630`). `etc/events.xml:3-5` hangs
`CodeCave\EAN\Observer\DataAssignObserver` on it, in the global area, so it runs for storefront, admin and web API
requests alike.

`DataAssignObserver::execute()` (`Observer/DataAssignObserver.php:17-31`):

1. Reads `additional_data` from the event's data object (line 21).
2. Returns without writing anything when `additional_data` is not an array (lines 22-24).
3. Otherwise writes four keys into the payment's additional information, each `''` when absent (lines 27-30).
   Any other key in `additional_data` does not reach the additional information.

## The four keys

| Key | Filled from | Shown by |
|---|---|---|
| `ean_number` | Storefront field EAN-nummer, admin field EAN-Nummer | Both info templates |
| `ean_ref` | Storefront and admin field Reference | Both info templates |
| `ean_ins` | Storefront and admin field Institution | Both info templates |
| `ean_company` | Nothing: no storefront or admin input fills it | No template (a commented-out line in `view/frontend/templates/info/ean.phtml:13-15`) |

Run in the 2.4.6-p15 lane (2026-10-28): `assignData()` with `ean_number` `123`, `ean_ref`, `ean_ins` and one unknown
key left `ean_number`, `ean_ref`, `ean_ins` and `ean_company` (empty); the unknown key was gone, and `validate()`
accepted the 3-character number. Without `additional_data` nothing was written.

## Where the keys are stored

| Step | Where | Proof |
|---|---|---|
| Quote | `quote_payment.additional_information` | Column at `vendor/magento/module-quote/etc/db_schema.xml:432`, `458` |
| Order | `sales_order_payment.additional_information`, as JSON | Column at `vendor/magento/module-sales/etc/db_schema.xml:603`, `686`; serialised field at `vendor/magento/module-sales/Model/ResourceModel/Order/Payment.php:21`, JSON serializer at `vendor/magento/framework/Model/ResourceModel/AbstractResource.php:254-257` |

At order placement `ToOrderPayment` copies the quote payment's additional information to the order payment and adds
`method_title` (the method's title at that moment) (`vendor/magento/module-quote/Model/Quote/Payment/ToOrderPayment.php:69-73`,
`vendor/magento/module-payment/Model/Method/Substitution.php:24`). An EAN order payment therefore holds `ean_number`,
`ean_ref`, `ean_ins`, `ean_company` and `method_title`. This module adds no column and no table.

## How the data reaches the observer

| Path | Entry | What turns the input into `additional_data` |
|---|---|---|
| Luma checkout | `POST /V1/carts/mine/payment-information` or the guest route | The browser sends `additional_data` itself (`03-checkout.md`); `PaymentInformationManagement::savePaymentInformation()` calls `PaymentMethodManagement::set()` (`vendor/magento/module-checkout/Model/PaymentInformationManagement.php:206`), which calls `Quote\Payment::importData()` (`vendor/magento/module-quote/Model/PaymentMethodManagement.php:79`) |
| Admin Create New Order | The admin form posts `payment[ean_number]`, `payment[ean_ref]`, `payment[ean_ins]` (`view/adminhtml/templates/form/ean.phtml:10`, `20`, `29`) | `Quote\Payment::importData()` moves every key it does not know into `additional_data` (`vendor/magento/module-quote/Model/Quote/Payment.php:212-235`) |
| REST clients | Any call that sets payment method `ean` with `additional_data` | The same `importData()` |
| GraphQL clients | `setPaymentMethodOnCart` with code `ean` | This module registers no additional data provider, so `additional_data` is `[]` (`vendor/magento/module-quote-graph-ql/Model/Cart/Payment/AdditionalDataProviderPool.php:35-43`, `vendor/magento/module-quote-graph-ql/Model/Cart/Payment/PaymentMethodBuilder.php:56-63`) and the observer stores four empty strings |

`importData()` checks that the method is available, then calls `assignData()` and `validate()`
(`vendor/magento/module-quote/Model/Quote/Payment.php:187-202`). Because `validate()` does nothing for this method
(`02-payment-method.md`), the server stores whatever the client sends, including empty strings.

## Known limits

- No server-side check: an API client can store an empty or malformed EAN number.
- GraphQL checkouts cannot send the EAN fields (no additional data provider).
- `ean_company` is always empty.
- A checkout that sends the fields outside `additional_data` (or not at all) stores no EAN details: the observer
  returns early.
