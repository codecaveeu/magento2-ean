<?php
declare(strict_types=1);

namespace CodeCave\EAN\Test\Unit\Gateway\Validator;

use CodeCave\EAN\Gateway\Validator\EanValidator;
use Magento\Payment\Gateway\Validator\Result;
use Magento\Payment\Gateway\Validator\ResultInterface;
use Magento\Payment\Gateway\Validator\ResultInterfaceFactory;
use Magento\Payment\Model\InfoInterface;
use Magento\Quote\Model\Quote\Payment as QuotePayment;
use Magento\Sales\Model\Order\Payment as OrderPayment;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class EanValidatorTest extends TestCase
{
    /**
     * @dataProvider thirteenDigits
     */
    #[DataProvider('thirteenDigits')]
    public function testOrderPaymentWithThirteenDigitsPasses(string $eanNumber): void
    {
        $result = $this->validate($this->payment(OrderPayment::class, $eanNumber));

        $this->assertTrue($result->isValid());
        $this->assertSame([], $result->getFailsDescription());
    }

    /**
     * @return array<string, array{string}>
     */
    public static function thirteenDigits(): array
    {
        return [
            'Danish GLN' => ['5798000012345'],
            'leading zero' => ['0123456789012'],
        ];
    }

    /**
     * @dataProvider notThirteenDigits
     */
    #[DataProvider('notThirteenDigits')]
    public function testOrderPaymentWithoutThirteenDigitsIsRefused(?string $eanNumber): void
    {
        $result = $this->validate($this->payment(OrderPayment::class, $eanNumber));

        $this->assertFalse($result->isValid());
        $this->assertSame(
            ['The EAN number must be exactly 13 digits.'],
            array_map('strval', $result->getFailsDescription())
        );
    }

    /**
     * @return array<string, array{?string}>
     */
    public static function notThirteenDigits(): array
    {
        return [
            '12 digits' => ['579800001234'],
            '14 digits' => ['57980000123456'],
            'letters' => ['579800001234A'],
            'space left in' => ['5798 00012345'],
            'trailing newline' => ["5798000012345\n"],
            'empty' => [''],
            'never filled in' => [null],
        ];
    }

    /**
     * @dataProvider anyValue
     */
    #[DataProvider('anyValue')]
    public function testQuotePaymentAlwaysPasses(?string $eanNumber): void
    {
        $this->assertTrue($this->validate($this->payment(QuotePayment::class, $eanNumber))->isValid());
    }

    /**
     * @return array<string, array{?string}>
     */
    public static function anyValue(): array
    {
        return [
            'never filled in' => [null],
            'empty' => [''],
            '12 digits' => ['579800001234'],
        ];
    }

    private function validate(InfoInterface $payment): ResultInterface
    {
        $factory = $this->createStub(ResultInterfaceFactory::class);
        $factory->method('create')->willReturnCallback(
            static fn (array $data): Result => new Result(
                $data['isValid'],
                $data['failsDescription'],
                $data['errorCodes']
            )
        );

        return (new EanValidator($factory))->validate(['payment' => $payment, 'storeId' => 1]);
    }

    /**
     * @param class-string<InfoInterface> $class
     * @param string|null $eanNumber
     * @return InfoInterface
     */
    private function payment(string $class, ?string $eanNumber): InfoInterface
    {
        $payment = (new ReflectionClass($class))->newInstanceWithoutConstructor();
        $payment->setData('method', 'ean');
        if ($eanNumber !== null) {
            $payment->setAdditionalInformation('ean_number', $eanNumber);
        }

        return $payment;
    }
}
