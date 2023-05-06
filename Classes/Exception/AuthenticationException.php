<?php

namespace EHAERER\Uppload\Exception;

/**
 * This file is part of the "uppload" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */


class AuthenticationException extends \InvalidArgumentException
{
    protected $code = 100;
}
