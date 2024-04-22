<?php

namespace CodeCave\EAN\Observer;

use Magento\Framework\Event\Observer;
use Magento\Payment\Observer\AbstractDataAssignObserver;
use Magento\Quote\Api\Data\PaymentInterface;

class DataAssignObserver extends AbstractDataAssignObserver
{
    /**
     * Assign additional payment information
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $data = $this->readDataArgument($observer);

        $additionalData = $data->getData(PaymentInterface::KEY_ADDITIONAL_DATA);
        if (!is_array($additionalData)) {
            return;
        }

        $paymentInfo = $this->readPaymentModelArgument($observer);
        $paymentInfo->setAdditionalInformation('ean_number', $additionalData['ean_number'] ?? '');
        $paymentInfo->setAdditionalInformation('ean_ref', $additionalData['ean_ref'] ?? '');
        $paymentInfo->setAdditionalInformation('ean_ins', $additionalData['ean_ins'] ?? '');
        $paymentInfo->setAdditionalInformation('ean_company', $additionalData['ean_company'] ?? '');
    }
}
