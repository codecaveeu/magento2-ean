<?php
declare(strict_types=1);

namespace CodeCave\EAN\Test\Integration;

use CodeCave\EAN\Test\Integration\Support\CartBuilder;
use Magento\Checkout\Api\GuestPaymentInformationManagementInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\PaymentInterfaceFactory;
use Magento\Quote\Api\PaymentMethodManagementInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Magento\TestFramework\Fixture\Config;
use Magento\TestFramework\Fixture\DbIsolation;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

class PlaceOrderTest extends TestCase
{
    private const MESSAGE = 'The EAN number must be exactly 13 digits.';

    #[DbIsolation(true)]
    #[Config('payment/ean/order_status', 'pending_payment', 'store', 'default')]
    public function testOrderLandsInProcessingDespiteStaleOrderStatusRow(): void
    {
        $method = Bootstrap::getObjectManager()->get(PaymentHelper::class)->getMethodInstance('ean');
        $this->assertSame('pending_payment', $method->getConfigData('order_status', 1));

        $cartId = CartBuilder::guestCart('DK');
        $orderId = $this->placeAsGuest($cartId, ['ean_number' => '5798000012345']);

        $order = $this->loadOrder($orderId);
        $this->assertSame(Order::STATE_PROCESSING, $order->getState());
        $this->assertSame(Order::STATE_PROCESSING, $order->getStatus());
        $this->assertSame('5798000012345', $order->getPayment()->getAdditionalInformation('ean_number'));
    }

    #[DbIsolation(true)]
    public function testTwelveDigitNumberIsRefusedAtPlacement(): void
    {
        $cartId = CartBuilder::guestCart('DK');

        try {
            $this->placeAsGuest($cartId, ['ean_number' => '579800001234']);
            $this->fail('A 12-digit EAN number was accepted.');
        } catch (CouldNotSaveException $e) {
            $this->assertSame(self::MESSAGE, $e->getMessage());
        }

        $this->assertSame(0, $this->countOrdersFor($cartId));
    }

    #[DbIsolation(true)]
    public function testSpacedAndDashedNumberIsStoredAsThirteenDigits(): void
    {
        $cartId = CartBuilder::guestCart('DK');
        $this->selectEanWithoutData($cartId);
        $quote = CartBuilder::load($cartId);
        $quote->getPayment()->setAdditionalInformation('ean_number', '5798 000-012345');
        Bootstrap::getObjectManager()->get(CartRepositoryInterface::class)->save($quote);

        $orderId = (int) Bootstrap::getObjectManager()->get(CartManagementInterface::class)->placeOrder($cartId);

        $order = $this->loadOrder($orderId);
        $this->assertSame('5798000012345', $order->getPayment()->getAdditionalInformation('ean_number'));
        $this->assertSame(Order::STATE_PROCESSING, $order->getStatus());
    }

    #[DbIsolation(true)]
    public function testSelectingTheMethodWithEmptyDataIsNotRefused(): void
    {
        $cartId = CartBuilder::guestCart('DK');

        $this->selectEanWithoutData($cartId);
        $this->assertSame('ean', CartBuilder::load($cartId)->getPayment()->getMethod());

        try {
            Bootstrap::getObjectManager()->get(CartManagementInterface::class)->placeOrder($cartId);
            $this->fail('An order without an EAN number was placed.');
        } catch (LocalizedException $e) {
            $this->assertSame(self::MESSAGE, $e->getMessage());
        }
        $this->assertSame(0, $this->countOrdersFor($cartId));
    }

    private function placeAsGuest(int $cartId, array $additionalData): int
    {
        $payment = Bootstrap::getObjectManager()->create(PaymentInterfaceFactory::class)->create();
        $payment->setMethod('ean');
        $payment->setAdditionalData($additionalData);

        return (int) Bootstrap::getObjectManager()->get(GuestPaymentInformationManagementInterface::class)
            ->savePaymentInformationAndPlaceOrder(
                CartBuilder::maskedId($cartId),
                CartBuilder::load($cartId)->getCustomerEmail(),
                $payment
            );
    }

    private function selectEanWithoutData(int $cartId): void
    {
        $payment = Bootstrap::getObjectManager()->create(PaymentInterfaceFactory::class)->create();
        $payment->setMethod('ean');
        Bootstrap::getObjectManager()->get(PaymentMethodManagementInterface::class)->set($cartId, $payment);
    }

    private function loadOrder(int $orderId): Order
    {
        return Bootstrap::getObjectManager()->create(OrderRepositoryInterface::class)->get($orderId);
    }

    private function countOrdersFor(int $cartId): int
    {
        return Bootstrap::getObjectManager()->create(OrderCollectionFactory::class)->create()
            ->addFieldToFilter('quote_id', $cartId)
            ->getSize();
    }
}
