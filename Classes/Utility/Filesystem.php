<?php

namespace EHAERER\Uppload\Utility;

/**
 * This file is part of the "uppload" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

use EHAERER\Uppload\Exception\InvalidArgument\InvalidUploadDirectoryException;
use Exception;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Filesystem.
 */
class Filesystem
{
    /**
     * Check if path is allowed and valid.
     *
     * @param $path
     * @return bool
     */
    public static function isPathValid($path): bool
    {
        return (strlen($path) > 0 && GeneralUtility::isAllowedAbsPath(self::getPublicPath() . $path));
    }

    /**
     * Create upload folder.
     *
     * @param string $uploadPath
     */
    public static function createFolder($uploadPath): void
    {
        if (file_exists($uploadPath)) {
            return;
        }

        // Create target dir
        try {
            GeneralUtility::mkdir_deep(self::getPublicPath() . $uploadPath);
        } catch (Exception $exception) {
            throw new InvalidUploadDirectoryException('Failed to create upload directory.', $exception->getCode(), $exception);
        }
    }

    /**
     * @return string
     */
    public static function getPublicPath(): string
    {
        return Environment::getPublicPath() . '/';
    }

    /**
     * Generate random string.
     *
     * @param int $length
     * @return string
     * @throws Exception
     */
    public static function getRandomDirName($length = 10): string
    {
        $set = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIKLMNPQRSTUVWXYZ0123456789';
        $string = '';

        for ($i = 1; $i <= $length; ++$i) {
            $string .= $set[random_int(0, (strlen($set) - 1))];
        }

        return $string;
    }
}
