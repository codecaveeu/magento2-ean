<?php
declare(strict_types=1);

namespace CodeCave\EAN\Test\Integration;

use CodeCave\EAN\Model\Ui\ConfigProvider;
use Magento\TestFramework\Fixture\AppArea;
use Magento\TestFramework\Fixture\Config;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

class CheckoutConfigTest extends TestCase
{
    #[AppArea('frontend')]
    #[Config('payment/ean/instructions', "Betaling via EAN\n<b>inden 30 dage</b>", 'store', 'default')]
    public function testCheckoutConfigCarriesTheStoreInstructions(): void
    {
        $config = Bootstrap::getObjectManager()->create(ConfigProvider::class)->getConfig();

        $this->assertSame(
            "Betaling via EAN<br />\n&lt;b&gt;inden 30 dage&lt;/b&gt;",
            $config['payment']['ean']['instructions'] ?? null
        );
    }
}
