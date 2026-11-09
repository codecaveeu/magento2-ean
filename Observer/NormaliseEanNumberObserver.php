<?php
declare(strict_types=1);

namespace CodeCave\EAN\Observer;

use CodeCave\EAN\Gateway\Validator\EanValidator;
use CodeCave\EAN\Model\Ui\ConfigProvider;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order\Payment;

class NormaliseEanNumberObserver implements ObserverInterface
{
    /**
     * @inheritdoc
     */
    public function execute(Observer $observer): void
    {
        $payment = $observer->getEvent()->getData('payment');
        if (!$payment instanceof Payment || $payment->getMethod() !== ConfigProvider::CODE) {
            return;
        }

        $eanNumber = $payment->getAdditionalInformation(EanValidator::FIELD);
        if (!is_string($eanNumber)) {
            return;
        }

        $normalised = preg_replace('/[\s\p{Pd}]+/u', '', $eanNumber);
        if ($normalised !== null && $normalised !== $eanNumber) {
            $payment->setAdditionalInformation(EanValidator::FIELD, $normalised);
        }
    }
}
