<?php
namespace CodeCave\EAN\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;

class ConfigProvider implements ConfigProviderInterface
{
    const CODE = 'ean';
    protected $_code = self::CODE;

    /** Checkout config */
    public function getConfig()
    {
        return [
            'payment' => [
                $this->_code => [

                ]
            ]
        ];
    }
}
