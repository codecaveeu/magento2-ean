<?php
declare(strict_types=1);

namespace CodeCave\EAN\Test\Unit\Model\Ui;

use CodeCave\EAN\Model\Ui\ConfigProvider;
use Magento\Framework\Escaper;
use Magento\Payment\Gateway\ConfigInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ConfigProviderTest extends TestCase
{
    /**
     * @dataProvider storedInstructions
     */
    #[DataProvider('storedInstructions')]
    public function testCheckoutGetsTheStoreInstructions(?string $stored, string $shown): void
    {
        $config = $this->createStub(ConfigInterface::class);
        $config->method('getValue')->willReturnCallback(
            static fn (string $field) => $field === 'instructions' ? $stored : null
        );

        $this->assertSame(
            ['payment' => ['ean' => ['instructions' => $shown]]],
            (new ConfigProvider($config, new Escaper()))->getConfig()
        );
    }

    /**
     * @return array<string, array{?string, string}>
     */
    public static function storedInstructions(): array
    {
        return [
            'not set' => [null, ''],
            'empty' => ['', ''],
            'only whitespace' => ["  \n ", ''],
            'plain text' => ['Alle EAN-ordrer valideres', 'Alle EAN-ordrer valideres'],
            'line breaks kept, markup escaped' => [
                "Betaling via EAN.\nSe <b>vilkår</b> & betingelser",
                "Betaling via EAN.<br />\nSe &lt;b&gt;vilkår&lt;/b&gt; &amp; betingelser",
            ],
        ];
    }
}
