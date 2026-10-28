# Feature register

One entry per feature of `CodeCave_EAN`. Add or change an entry in the same change as its feature; a Delivered card
links its entry.

All features below came in the first commit `990fd7f` (2024-04-22, "First version") and were first tagged as `1.0.0`
(`1926837`). Tag `1.0.1` (`96480ac`) only adds README lines and Composer skips it (`01-layout.md`, "Version field
and tags"). No owner decision is recorded for this module: a Judge search for "ean" in project `mod-ean` returned 0
results on 2026-10-28.

"Run" below means the check was run on 2026-10-28 in a hub lane on Magento 2.4.6-p15; "not run" means it is the
expected result from the code.

## EAN payment method

- **What:** a payment method `ean` that records EAN details on the order instead of taking money.
- **Since:** 2024-04-22, commit `990fd7f`; tag `1.0.0`.
- **Decisions:** none recorded.
- **Use:** `composer require codecave/magento2-ean:1.0.0`, `bin/magento module:enable CodeCave_EAN`,
  `bin/magento setup:upgrade`. The method is on by default (`etc/config.xml:6`); settings under Stores >
  Configuration > Sales > Payment Methods > "European Article Number (EAN)".
- **Check:** `bin/magento module:status CodeCave_EAN` prints `CodeCave_EAN : Module is enabled` (run).
- **State:** live. partyvikings.dk requires `1.0.0` and enables the module (`README.md`, "Who uses it"); whether the
  method is switched on in its stores was not checked.

## EAN fields in the Luma checkout

- **What:** under the EAN radio button, three required fields: EAN-nummer (exactly 13 characters, no blanks),
  Institution and Reference.
- **Since:** 2024-04-22, commit `990fd7f`; tag `1.0.0`.
- **Decisions:** none recorded.
- **Use:** any storefront on the Luma checkout; nothing to configure (`03-checkout.md`).
- **Check:** in the checkout pick EAN, type a 12-character EAN number and press Place Order: the form shows a
  validation error and sends nothing; with 13 characters and both other fields filled the order is placed (not run).
- **State:** live for the Luma checkout. Hyvä Checkout gets no EAN fields from this module; the shop brings its own form.

## EAN details stored on the order

- **What:** the observer writes `ean_number`, `ean_ref`, `ean_ins` and `ean_company` (always empty) into the payment's
  additional information; the order payment gets them plus `method_title` (`04-payment-data.md`).
- **Since:** 2024-04-22, commit `990fd7f`; tag `1.0.0`.
- **Decisions:** none recorded.
- **Use:** automatic for every EAN order whose checkout sends the fields in `additional_data`.
- **Check:** after an EAN order,
  `SELECT additional_information FROM sales_order_payment WHERE method = 'ean' ORDER BY entity_id DESC LIMIT 1;`
  prints JSON with `ean_number`, `ean_ref`, `ean_ins`, `ean_company` and `method_title` (not run). The observer step
  alone was run: `assignData()` left exactly the four keys on the payment.
- **State:** live. No server-side check of the values; GraphQL checkouts store four empty strings.

## EAN details on order pages, e-mails and PDFs

- **What:** the payment block shows the method title, the EAN number, the reference and the institution
  (`05-order-display.md`).
- **Since:** 2024-04-22, commit `990fd7f`; tag `1.0.0`.
- **Decisions:** none recorded.
- **Use:** automatic on admin order, invoice, shipment and credit memo pages, admin PDFs, customer account order pages
  and sales e-mails.
- **Check:** open an EAN order under Sales > Orders: "Payment & Shipping Method" shows `EAN-nummer`, `Reference` and
  `Institution` with their values (not run on a real order; both templates were rendered with sample values, run).
- **State:** live. The admin and storefront templates list the fields in a different order.

## EAN fields in admin Create New Order

- **What:** the same three fields (no length rule) when an admin creates an order (`05-order-display.md`).
- **Since:** 2024-04-22, commit `990fd7f`; tag `1.0.0`.
- **Decisions:** none recorded.
- **Use:** set `payment/ean/can_use_internal` to `1`, which this module neither sets nor offers in `system.xml`;
  partyvikings.dk sets it in `app/code/PartyVikings/Core/etc/config.xml`.
- **Check:** with that set, Sales > Orders > Create New Order lists EAN with the three fields (not run). Without it,
  the method instance's `canUseInternal()` returns false (run).
- **State:** off by default.

## Admin settings for the method

- **What:** Enabled, Title, New Order Status, Payment from Applicable Countries, Payment from Specific Countries,
  Instructions and Sort Order (`02-payment-method.md`, "Admin settings").
- **Since:** 2024-04-22, commit `990fd7f`; tag `1.0.0`.
- **Decisions:** none recorded.
- **Use:** Stores > Configuration > Sales > Payment Methods > "European Article Number (EAN)".
- **Check:** `bin/magento config:show payment/ean` lists the saved fields once the group has been saved; on a fresh
  install it prints `The "payment/ean" path doesn't exist.` because only `etc/config.xml` defaults apply (run).
- **State:** live with limits. Enabled, Title and Sort Order take effect. New Order Status is replaced by
  `processing` at placement; the two country fields and Instructions have no effect.
