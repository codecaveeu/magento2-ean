<?php
declare(strict_types=1);

namespace CodeCave\EAN\Gateway\Validator;

use Magento\Payment\Gateway\Validator\AbstractValidator;
use Magento\Payment\Gateway\Validator\ResultInterface;
use Magento\Sales\Model\Order\Payment;

class EanValidator extends AbstractValidator
{
    public const FIELD = 'ean_number';

    /**
     * @inheritdoc
     */
    public function validate(array $validationSubject): ResultInterface
    {
        $payment = $validationSubject['payment'] ?? null;
        // quote payments pass so selecting the method never needs the fields filled in
        if (!$payment instanceof Payment) {
            return $this->createResult(true);
        }

        $eanNumber = $payment->getAdditionalInformation(self::FIELD);
        if (is_string($eanNumber) && preg_match('/^[0-9]{13}\z/', $eanNumber) === 1) {
            return $this->createResult(true);
        }

        return $this->createResult(false, [__('The EAN number must be exactly 13 digits.')]);
    }
}
