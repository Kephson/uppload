<?php

namespace EHAERER\Uppload\Signal;

use In2code\Powermail\Controller\FormController;
use In2code\Powermail\Domain\Model\Answer;
use In2code\Powermail\Domain\Model\Mail;

/**
 * This file is part of the "uppload" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */
class SaveImage
{

    /**
     * Manipulate message object short before powermail send the mail
     *
     * @param Mail $mail
     * @param string $hash
     * @param FormController $formController
     */
    public function saveImage(Mail $mail, string $hash, FormController $formController)
    {
        /*
        $answers = $mail->getAnswers();
        @var Answer $answer
        foreach ($answers as $answer) {
            $value = $answer->getValue();

        }
        */
    }
}
