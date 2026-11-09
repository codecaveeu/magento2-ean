<?php
namespace CodeCave\EAN\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\Escaper;
use Magento\Payment\Gateway\ConfigInterface;

class ConfigProvider implements ConfigProviderInterface
{
    public const CODE = 'ean';

    /**
     * @var string
     */
    protected $_code = self::CODE;

    /**
     * @var ConfigInterface
     */
    private $config;

    /**
     * @var Escaper
     */
    private $escaper;

    /**
     * @param ConfigInterface $config
     * @param Escaper $escaper
     */
    public function __construct(ConfigInterface $config, Escaper $escaper)
    {
        $this->config = $config;
        $this->escaper = $escaper;
    }

    /**
     * @inheritdoc
     */
    public function getConfig()
    {
        return [
            'payment' => [
                $this->_code => [
                    'instructions' => $this->getInstructions(),
                ]
            ]
        ];
    }

    /**
     * Store's Instructions text as HTML-safe markup with line breaks kept
     *
     * @return string
     */
    private function getInstructions(): string
    {
        $instructions = trim((string) $this->config->getValue('instructions'));

        return $instructions === '' ? '' : nl2br($this->escaper->escapeHtml($instructions));
    }
}
