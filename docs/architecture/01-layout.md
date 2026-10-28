# Layout

24 tracked files at `96480ac`. 23 of them came in the first commit `990fd7f` (2024-04-22); later commits added only
`LICENSE_AFL.txt` (`1926837`) and five lines of `README.md` (`96480ac`).

## Files

| Path | Role |
|---|---|
| `registration.php` | Registers module `CodeCave_EAN` from the repo root (`registration.php:5-9`) |
| `etc/module.xml` | Declares `CodeCave_EAN`, `setup_version="1.0.0"`, no `<sequence>` (`etc/module.xml:4-5`) |
| `composer.json` | Package `codecave/magento2-ean`, type `magento2-module` (`composer.json:2-4`) |
| `etc/config.xml` | Defaults under `payment/ean` (`etc/config.xml:5-14`) |
| `etc/payment.xml` | Payment group `offline` and method `ean` with `allow_multiple_address` 1 (`etc/payment.xml:5-12`) |
| `etc/di.xml` | The method facade and its config value handler, all virtual types (`etc/di.xml:4-29`) |
| `etc/events.xml` | Observer on `payment_method_assign_data_ean` (`etc/events.xml:3-5`) |
| `etc/frontend/di.xml` | Adds the checkout config provider (`etc/frontend/di.xml:3-9`) |
| `etc/adminhtml/system.xml` | The admin settings group `payment/ean` (`etc/adminhtml/system.xml:6-43`) |
| `Model/Ui/ConfigProvider.php` | Method code constant `CODE = 'ean'` and the checkout config (`Model/Ui/ConfigProvider.php:8-21`) |
| `Model/Config/Source/Order/Status/Pendingpayment.php` | Options for "New Order Status" (`Model/Config/Source/Order/Status/Pendingpayment.php:8-10`) |
| `Observer/DataAssignObserver.php` | Copies the EAN fields onto the payment (`Observer/DataAssignObserver.php:17-31`) |
| `Block/Info/Ean.php` | Payment info block, template `CodeCave_EAN::info/ean.phtml` (`Block/Info/Ean.php:12`) |
| `Block/Adminhtml/Form/Ean.php` | Admin order-create form block, template `CodeCave_EAN::form/ean.phtml` (`Block/Adminhtml/Form/Ean.php:9`) |
| `view/adminhtml/templates/info/ean.phtml` | Info template for the admin area |
| `view/adminhtml/templates/form/ean.phtml` | Form template for Create New Order |
| `view/frontend/templates/info/ean.phtml` | Info template for the storefront area (account pages, e-mails) |
| `view/frontend/layout/checkout_index_index.xml` | Puts the `ean` renderer into the Luma checkout (`view/frontend/layout/checkout_index_index.xml:19-25`) |
| `view/frontend/web/js/view/payment/ean.js` | Registers renderer type `ean` (`view/frontend/web/js/view/payment/ean.js:11-16`) |
| `view/frontend/web/js/view/payment/method-renderer/ean-method.js` | The Knockout renderer |
| `view/frontend/web/template/payment/ean.html` | The Knockout template with the three fields |
| `README.md`, `LICENSE.txt`, `LICENSE_AFL.txt` | Readme, OSL 3.0 text, AFL text |

## Composer package

- `"version": "1.0.0"` is written in `composer.json:5`, at both tags. Composer reads that field instead of the tag
  name, so tag `1.0.1` is not installable: see "Version field and tags" below.
- Requires only `php` `~8.1` (`composer.json:8-10`): no Magento package is required, so Composer does not stop an
  install on a project without `magento/module-payment`, `magento/module-checkout` or `magento/module-sales`.
- `"license": []` is empty (`composer.json:6-7`) although the repo ships `LICENSE.txt` (OSL 3.0) and
  `LICENSE_AFL.txt`.
- Autoload: `registration.php` as a file, PSR-4 `CodeCave\EAN\` at the repo root (`composer.json:11-16`).

### Version field and tags

| Tag | Commit | `version` in its `composer.json` | What Composer does |
|---|---|---|---|
| `1.0.0` | `1926837` | `1.0.0` | Imports it as `1.0.0` |
| `1.0.1` | `96480ac` | `1.0.0` | Skips it: "Skipped tag 1.0.1, tag (1.0.1.0) does not match version (1.0.0.0) in composer.json" |

Composer 2.10.3 (`vendor/composer/composer/src/Composer/Repository/VcsRepository.php:260-286`) takes the version from
the `version` field when it is present and skips a tag whose name normalises to something else. A
`composer show --all codecave/magento2-ean -vvv` against this repo lists `versions : dev-master, 1.0.0`, with `1.0.0` at
`1926837`. The only difference between the two tags is `README.md`, so no code is lost today.

## Areas and DI

| Area | File | What it adds |
|---|---|---|
| global | `etc/di.xml` | `CodeCaveFacade` (`Magento\Payment\Model\Method\Adapter`) with code `ean`, info block `CodeCave\EAN\Block\Info\Ean`, form block `CodeCave\EAN\Block\Adminhtml\Form\Ean` and value handler pool `CodeCaveValueHandlerPool` (`etc/di.xml:4-12`) |
| global | `etc/di.xml` | `CodeCaveValueHandlerPool` with one `default` handler, `CodeCaveConfigValueHandler`, reading `CodeCaveConfig` (`Magento\Payment\Gateway\Config\Config` for method code `ean`) (`etc/di.xml:13-29`) |
| global | `etc/events.xml` | `DataAssignObserver` on `payment_method_assign_data_ean` (`etc/events.xml:3-5`) |
| frontend | `etc/frontend/di.xml` | `CodeCave\EAN\Model\Ui\ConfigProvider` as item `ean_config_provider` of `Magento\Checkout\Model\CompositeConfigProvider` (`etc/frontend/di.xml:3-9`) |
| adminhtml | `etc/adminhtml/system.xml` | The settings group (see `02-payment-method.md`) |

The facade has no command pool (the argument is commented out at `etc/di.xml:10`) and no validator pool. What that
means for capture, refunds and country checks is in `02-payment-method.md`.

`etc/config.xml:11` names the facade as the method model (`<model>CodeCaveFacade</model>`), which is how
`Magento\Payment\Helper\Data::getMethodInstance('ean')` finds it (`vendor/magento/module-payment/Helper/Data.php:112-125`). At runtime the instance is
`Magento\Payment\Model\Method\Adapter` with code `ean` (checked on Magento 2.4.6-p15, 2026-10-28).

## Load order

`etc/module.xml:4-5` has no `<sequence>`. The module extends `Magento_Payment`, `Magento_Checkout` and `Magento_Sales`
classes and layout (`Block/Info/Ean.php:5`, `Model/Ui/ConfigProvider.php:4`,
`Model/Config/Source/Order/Status/Pendingpayment.php:5-6`, `view/frontend/layout/checkout_index_index.xml:4`) without
declaring them, so Magento may load it before them. Its config and layout only add new keys (`payment/ean`, the
`ean` renderer), so the order matters only if another module writes the same keys.
