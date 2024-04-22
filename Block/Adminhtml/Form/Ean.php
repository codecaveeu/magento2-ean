<?php

namespace CodeCave\EAN\Block\Adminhtml\Form;

use Magento\Payment\Block\Form;

class Ean extends Form
{
    protected $_template = 'CodeCave_EAN::form/ean.phtml';


    public function getAdditionalInfo($field)
    {
        return $this->getMethod()->getInfoInstance()->getAdditionalInformation($field);
    }
}
