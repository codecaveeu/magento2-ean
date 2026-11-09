<?php
declare(strict_types=1);

namespace CodeCave\EAN\Test\Unit\Observer;

use CodeCave\EAN\Observer\NormaliseEanNumberObserver;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Payment\Model\InfoInterface;
use Magento\Quote\Model\Quote\Payment as QuotePayment;
use Magento\Sales\Model\Order\Payment as OrderPayment;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class NormaliseEanNumberObserverTest extends TestCase
{
    /**
     * @dataProvider typedNumbers
     */
    #[DataProvider('typedNumbers')]
    public function testSpacesAndDashesAreStrippedOnEanOrderPayment(string $typed, string $stored): void
    {
        $payment = $this->payment(OrderPayment::class, 'ean', $typed);

        $this->place($payment);

        $this->assertSame($stored, $payment->getAdditionalInformation('ean_number'));
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function typedNumbers(): array
    {
        return [
            'space and dash' => ['5798 000-012345', '5798000012345'],
            'tab and newline' => ["5798000\t012345\n", '5798000012345'],
            'no-break space and en dash' => ["5798\u{00A0}000\u{2013}012345", '5798000012345'],
            'already clean' => ['5798000012345', '5798000012345'],
            'letters kept for the validator' => ['5798-000-ABC', '5798000ABC'],
        ];
    }

    public function testOtherPaymentMethodsAreLeftAlone(): void
    {
        $payment = $this->payment(OrderPayment::class, 'checkmo', '5798 000-012345');

        $this->place($payment);

        $this->assertSame('5798 000-012345', $payment->getAdditionalInformation('ean_number'));
    }

    public function testQuotePaymentIsLeftAlone(): void
    {
        $payment = $this->payment(QuotePayment::class, 'ean', '5798 000-012345');

        $this->place($payment);

        $this->assertSame('5798 000-012345', $payment->getAdditionalInformation('ean_number'));
    }

    public function testMissingNumberStaysMissing(): void
    {
        $payment = $this->payment(OrderPayment::class, 'ean', null);

        $this->place($payment);

        $this->assertSame([], $payment->getAdditionalInformation());
    }

    private function place(InfoInterface $payment): void
    {
        (new NormaliseEanNumberObserver())->execute(
            new Observer(['event' => new Event(['payment' => $payment])])
        );
    }

    /**
     * @param class-string<InfoInterface> $class
     * @param string $method
     * @param string|null $eanNumber
     * @return InfoInterface
     */
    private function payment(string $class, string $method, ?string $eanNumber): InfoInterface
    {
        $payment = (new ReflectionClass($class))->newInstanceWithoutConstructor();
        $payment->setData('method', $method);
        if ($eanNumber !== null) {
            $payment->setAdditionalInformation('ean_number', $eanNumber);
        }

        return $payment;
    }
}
