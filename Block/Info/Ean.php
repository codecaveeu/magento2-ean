<?php

namespace CodeCave\EAN\Block\Info;

use Magento\Payment\Block\Info;

class Ean extends Info
{
    /**
     * @var string
     */
    protected $_template = 'CodeCave_EAN::info/ean.phtml';

	public function toPdf()
    {
        $this->setTemplate('CodeCave_EAN::info/ean.phtml');
        return $this->toHtml();
    }
}
