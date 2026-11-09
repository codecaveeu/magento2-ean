<?php
declare(strict_types=1);

namespace CodeCave\EAN\Test\Integration;

use CodeCave\EAN\Test\Integration\Support\CartBuilder;
use Magento\Payment\Model\Method\AbstractMethod;
use Magento\Sales\Model\AdminOrder\Create;
use Magento\TestFramework\Fixture\AppArea;
use Magento\TestFramework\Fixture\DbIsolation;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

class AdminPaymentDataTest extends TestCase
{
    #[AppArea('adminhtml')]
    #[DbIsolation(true)]
    public function testAdminOrderFormStoresTheEanFields(): void
    {
        $quote = CartBuilder::load(CartBuilder::guestCart('DK'));
        $orderCreate = Bootstrap::getObjectManager()->create(Create::class);
        $orderCreate->setQuote($quote);

        $orderCreate->setPaymentData([
            'method' => 'ean',
            'ean_number' => '5798000012345',
            'ean_ref' => CartBuilder::PREFIX . 'ref',
            'ean_ins' => CartBuilder::PREFIX . 'ins',
            'checks' => [
                AbstractMethod::CHECK_USE_INTERNAL,
                AbstractMethod::CHECK_USE_FOR_COUNTRY,
                AbstractMethod::CHECK_USE_FOR_CURRENCY,
                AbstractMethod::CHECK_ORDER_TOTAL_MIN_MAX,
                AbstractMethod::CHECK_ZERO_TOTAL,
            ],
        ]);

        $payment = $quote->getPayment();
        $this->assertSame('ean', $payment->getMethod());
        $this->assertSame('5798000012345', $payment->getAdditionalInformation('ean_number'));
        $this->assertSame(CartBuilder::PREFIX . 'ref', $payment->getAdditionalInformation('ean_ref'));
        $this->assertSame(CartBuilder::PREFIX . 'ins', $payment->getAdditionalInformation('ean_ins'));
    }
}
