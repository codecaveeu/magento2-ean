<?php
declare(strict_types=1);

namespace CodeCave\EAN\Test\Integration;

use CodeCave\EAN\Test\Integration\Support\CartBuilder;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Payment\Model\Config as PaymentConfig;
use Magento\Quote\Api\Data\PaymentInterfaceFactory;
use Magento\Quote\Api\PaymentMethodManagementInterface;
use Magento\TestFramework\Fixture\Config;
use Magento\TestFramework\Fixture\DbIsolation;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

class MethodAvailabilityTest extends TestCase
{
    #[DbIsolation(true)]
    #[Config('payment/ean/allowspecific', '1', 'store', 'default')]
    #[Config('payment/ean/specificcountry', 'DK', 'store', 'default')]
    public function testMethodIsUnavailableOutsideTheAllowedCountries(): void
    {
        $swedishCartId = CartBuilder::guestCart('SE');
        $danishCartId = CartBuilder::guestCart('DK');

        $this->assertNotContains('ean', $this->availableCodes($swedishCartId));
        $this->assertContains('ean', $this->availableCodes($danishCartId));

        $payment = Bootstrap::getObjectManager()->create(PaymentInterfaceFactory::class)->create();
        $payment->setMethod('ean');
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('The requested Payment Method is not available.');
        Bootstrap::getObjectManager()->get(PaymentMethodManagementInterface::class)->set($swedishCartId, $payment);
    }

    public function testMethodCanBeUsedOnAdminOrders(): void
    {
        $method = Bootstrap::getObjectManager()->get(PaymentHelper::class)->getMethodInstance('ean');

        $this->assertTrue($method->canUseInternal());
    }

    public function testMethodIsKeptOutOfMultiAddressCheckout(): void
    {
        $methodsInfo = Bootstrap::getObjectManager()->get(PaymentConfig::class)->getMethodsInfo();

        $this->assertEmpty($methodsInfo['ean']['allow_multiple_address'] ?? null);
    }

    private function availableCodes(int $cartId): array
    {
        $methods = Bootstrap::getObjectManager()->get(PaymentMethodManagementInterface::class)->getList($cartId);

        return array_map(static fn ($method) => $method->getCode(), $methods);
    }
}
