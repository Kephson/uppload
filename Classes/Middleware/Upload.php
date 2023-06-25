<?php

namespace EHAERER\Uppload\Middleware;

/**
 * This file is part of the "uppload" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

use EHAERER\Uppload\Exception\InvalidArgument\InvalidMimeTypeException;
use Exception;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Resource\Exception\ResourceDoesNotExistException;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use EHAERER\Uppload\Exception\AuthenticationException;
use EHAERER\Uppload\Exception\InvalidArgumentException;
use EHAERER\Uppload\Exception\InvalidArgument;
use EHAERER\Uppload\Utility\Filesystem;
use EHAERER\Uppload\Utility\FileValidation;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;

/**
 * This class uploads files.
 */
class Upload implements MiddlewareInterface
{
    /**
     * the extension key
     */
    const EXTKEY = 'uppload';

    /**
     * @var string
     */
    public const SESSION_KEY_PREFIX = 'uppload_';

    private bool $chunkedUpload = false;

    private ?FrontendUserAuthentication $feUserObj = null;

    private ?int $uid = null;

    private ?array $config = [
        'upload_path' => '1:form_uploads',
        'feuser_required' => true,
        'feuser_field' => '',
        'save_session' => true,
        'obscure_dir' => false,
        'check_mime' => true,
        'extensions' => 'jpg,png,jpeg',
    ];

    private string $uploadPath = '';

    private string $filepath = '';

    /**
     * @inheritDoc
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $uid = $request->getParsedBody()['uppload'] ?? $request->getQueryParams()['uppload'] ?? null;

        if ((int)$uid !== 1) {
            return $handler->handle($request);
        }
        if ($request->getMethod() !== 'POST') {
            throw new InvalidArgumentException('No file submitted.');
        }
        try {
            $this->config = $this->getUploadConfig();
            $this->feUserObj = $request->getAttribute('frontend.user');

            $this->handleUpload();

            // Return JSON-RPC response if upload process is successfully finished
            return $this->getResponse([
                'status' => 'ok',
                'url' => '/' . $this->filepath
            ]);
        } catch (AuthenticationException $exception) {
            return $this->getResponse($this->getErrorResponseContent($exception), 403);
        } catch (InvalidArgumentException $exception) {
            return $this->getResponse($this->getErrorResponseContent($exception), 410);
        } catch (Exception $exception) {
            return $this->getResponse($this->getErrorResponseContent($exception), 404);
        }
    }

    /**
     * Get JSON payload for error responses and add message to session.
     */
    protected function getErrorResponseContent(Exception $exception): array
    {
        $className = str_replace('Exception', '', get_class($exception));
        $classNameShort = substr($className, strrpos($className, '\\') + 1);
        $key = GeneralUtility::camelCaseToLowerCaseUnderscored($classNameShort);

        $data = [
            'message' => $exception->getMessage(),
            'messageKey' => $key,
            'messageArguments' => [
                'filename' => empty($_FILES) ? null : $this->getFileName(),
            ],
        ];

        if ($this->config['save_session']) {
            $this->updateDataInSession($data, $this->uid . '_messages');
        }

        return [
            'error' => array_merge($data, [
                'code' => $exception->getCode(),
            ]),
            'id' => '',
        ];
    }

    /**
     * Get JSON response for uppload
     */
    protected function getResponse(array $payload, int $status = 200): ResponseInterface
    {
        $response = new JsonResponse([], $status, [
            'Expires' => 'Mon, 26 Jul 1997 05:00:00 GMT',
            'Last-Modified' => gmdate('D, d M Y H:i:s') . ' GMT',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, post-check=0, pre-check=0',
        ]);

        $response->setPayload(array_merge([
            'jsonrpc' => '2.0',
        ], $payload));

        return $response;
    }

    /**
     * Handles incoming upload requests.
     */
    public function handleUpload()
    {
        if (!count($this->config)) {
            throw new InvalidArgument\InvalidConfigurationException('Configuration record not found or invalid.');
        }

        $this->processConfig();
        $this->checkUploadConfig();

        // Check for valid FE user
        if ($this->config['feuser_required'] && empty($this->getFeUser()->user['username'])) {
            throw new AuthenticationException('TYPO3 user session invalid.');
        }

        // One file or chunked?
        $this->chunkedUpload = (isset($_REQUEST['chunks']) && (int)$_REQUEST['chunks'] > 1);

        // Check file extension
        FileValidation::checkFileExtension($this->getFileName(), $this->config['extensions']);

        // Get upload path
        $this->uploadPath = $this->getUploadDir(
            $this->config['upload_path'],
            $this->getUserDirectory(),
            $this->config['obscure_dir']
        );
        Filesystem::createFolder($this->uploadPath);

        $this->uploadFile();
    }

    protected function getFeUser(): ?FrontendUserAuthentication
    {
        return $this->feUserObj;
    }

    /**
     * Get subdirectory based upon user data.
     */
    protected function getUserDirectory(): string
    {
        $record = $this->getFeUser()->user;
        $field = $this->config['feuser_field'];

        switch ($field) {
            case 'name':
            case 'username':
                $directory = $record[$field];
                break;

            case 'fullname':
                $parts = [$record['first_name'], $record['middle_name'], $record['last_name']];
                $directory = implode('_', array_values(array_filter($parts)));
                break;

            case 'uid':
            case 'pid':
                $directory = (string)$record[$field];
                break;

            case 'lastlogin':
                try {
                    $date = new \DateTime('@' . $record[$field]);
                    $date->setTimezone(new \DateTimeZone(date_default_timezone_get()));
                    $directory = strftime('%Y%m%d-%H', $date->format('U'));
                } catch (Exception $exception) {
                    $directory = 'checkTimezone';
                }

                break;

            default:
                $directory = '';
        }

        return preg_replace('#[^0-9a-zA-Z\-\.]#', '_', $directory);
    }

    protected function checkUploadConfig()
    {
        if ((string)$this->config['extensions'] === '') {
            throw new InvalidArgument\InvalidConfigurationException('Missing allowed file extension configuration.');
        }

        if (!Filesystem::isPathValid($this->config['upload_path'])) {
            throw new InvalidArgument\InvalidUploadDirectoryException('Upload directory not valid.');
        }
    }

    /**
     * Gets the plugin configuration.
     */
    protected function getUploadConfig(): array
    {
        $extSettings = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get(self::EXTKEY);
        return [
            'upload_path' => $extSettings['upload_path'] ?: '1:form_uploads',
            'feuser_required' => $extSettings['frontend_user_required'] ?: true,
            'feuser_field' => '',
            'save_session' => $extSettings['save_session'] ?: true,
            'obscure_dir' => $extSettings['obscure_dir'] ?: false,
            'check_mime' => $extSettings['check_mime'] ?: true,
            'extensions' => $extSettings['file_extensions'] ?: 'jpg,png,jpeg',
        ];
    }

    /**
     * Process the configuration.
     *
     * @return void
     * @throws ResourceDoesNotExistException
     */
    protected function processConfig()
    {
        // Make sure FAL references work
        $resourceFactory = GeneralUtility::makeInstance(ResourceFactory::class);
        $this->config['upload_path'] = $resourceFactory
            ->retrieveFileOrFolderObject($this->config['upload_path'])
            ->getPublicUrl();

        // Make sure no user based path is added when there is no user available
        if (!$this->config['feuser_required']) {
            $this->config['feuser_field'] = '';
        }
    }

    /**
     * Gets the uploaded file name from request.
     *
     * @return string
     */
    protected function getFileName()
    {
        $filename = uniqid('file_', true);

        if (isset($_REQUEST['name'])) {
            $filename = $_REQUEST['name'];
        } elseif (!empty($_FILES)) {
            $filename = $_FILES['file']['name'];
        }

        return preg_replace('#[^\w\._]+#', '_', $filename);
    }

    /**
     * Checks and creates the upload directory.
     *
     * @param string $path
     * @param string $subDirectory
     * @param bool $obscure
     *
     * @return string
     */
    protected function getUploadDir($path, $subDirectory = '', $obscure = false)
    {
        if ($this->chunkedUpload) {
            $chunkedPath = $this->getSessionData('chunk_path');
            if ($chunkedPath && file_exists($chunkedPath . DIRECTORY_SEPARATOR . $this->getFileName() . '.part')) {
                return $chunkedPath;
            } else {
                // Reset session
                $this->saveDataInSession(null, 'chunk_path');
            }
        }

        // Make sure we have no trailing slash
        $path = GeneralUtility::dirname($path);

        // Subdirectory
        if ($subDirectory !== '') {
            $path = $path . DIRECTORY_SEPARATOR . $subDirectory;
        }

        // Obscure directory
        if ($obscure) {
            $path = $path . DIRECTORY_SEPARATOR . Filesystem::getRandomDirName(20);
        }

        return $path;
    }

    /**
     * Handles file upload.
     *
     * Copyright 2013, Moxiecode Systems AB
     * Released under GPL License.
     *
     * License: http://www.plupload.com/license
     * Contributing: http://www.plupload.com/contributing
     */
    protected function uploadFile()
    {
        // Get additional parameters
        $chunk = isset($_REQUEST['chunk']) ? (int)$_REQUEST['chunk'] : 0;
        $chunks = isset($_REQUEST['chunks']) ? (int)$_REQUEST['chunks'] : 0;

        // Clean the fileName for security reasons
        $filename = $this->getFileName();
        $filePath = $this->uploadPath . DIRECTORY_SEPARATOR . $filename;

        if (is_file($filePath)) {
            $filePath = $this->uploadPath . DIRECTORY_SEPARATOR . uniqid() . '_' . $filename;
        }

        // Open temp file
        if (!$out = @fopen(sprintf('%s.part', $filePath), $chunks !== 0 ? 'ab' : 'wb')) {
            throw new InvalidArgumentException('Failed to open output stream.', 102);
        }

        if (!empty($_FILES)) {
            if ($_FILES['file']['error'] || !is_uploaded_file($_FILES['file']['tmp_name'])) {
                throw new InvalidArgumentException('Failed to move uploaded file.', 103);
            }

            // Read binary input stream and append it to temp file
            if (!$in = @fopen($_FILES['file']['tmp_name'], 'rb')) {
                throw new InvalidArgumentException('Failed to open input stream.', 101);
            }
        } elseif (!$in = @fopen('php://input', 'rb')) {
            throw new InvalidArgumentException('Failed to open input stream.', 101);
        }

        while ($buff = fread($in, 4096)) {
            fwrite($out, $buff);
        }

        @fclose($out);
        @fclose($in);

        // Check if file has been uploaded
        if (!$chunks || $chunk == $chunks - 1) {
            // Strip the temp .part suffix off
            rename($filePath . '.part', $filePath);
            $this->processFile($filePath);
        }

        // save chunked upload dir
        if ($this->chunkedUpload) {
            $this->saveDataInSession($this->uploadPath, 'chunk_path');
        }
    }

    /**
     * Process uploaded file.
     *
     * @param string $filePath
     *
     */
    protected function processFile(string $filePath)
    {
        // we already checked if the file extension is allowed,
        // so we need to check if the mime type is adequate.
        // if mime type is not allowed: remove file
        if ($this->config['check_mime'] && !FileValidation::checkMimeType($filePath)) {
            @unlink($filePath);
            throw new InvalidMimeTypeException('File mime type is not allowed.');
        }

        GeneralUtility::fixPermissions($filePath);

        if ($this->config['save_session']) {
            $this->updateDataInSession($filePath, $this->uid . '_files');
        }
        $this->filepath = $filePath;
    }

    /**
     * Store file in session.
     *
     * @param mixed $filePath
     * @param string $key
     */
    protected function updateDataInSession($filePath, string $key = 'files')
    {
        $currentData = $this->getSessionData($key);

        if (!is_array($currentData)) {
            $currentData = [];
        }

        $currentData[] = $filePath;

        $this->saveDataInSession($currentData, $key);
    }

    /**
     * Store session data.
     *
     * @param mixed $data
     * @param string $key
     */
    protected function saveDataInSession(mixed $data, string $key = 'data')
    {
        $this->getFeUser()->setAndSaveSessionData(self::SESSION_KEY_PREFIX . $key, $data);
    }

    /**
     * Get session data.
     *
     * @param string $key
     *
     * @return mixed
     */
    protected function getSessionData(string $key = 'data')
    {
        return $this->getFeUser()->getSessionData(self::SESSION_KEY_PREFIX . $key);
    }
}
