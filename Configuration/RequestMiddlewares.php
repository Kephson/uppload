<?php

return [
    'frontend' => [
        'ehaerer/uppload/upload' => [
            'target' => \EHAERER\Uppload\Middleware\Upload::class,
            'after' => [
                'typo3/cms-frontend/authentication',
            ],
            'before' => [
                'typo3/cms-frontend/base-redirect-resolver',
                'typo3/cms-redirects/redirecthandler',
                'typo3/cms-frontend/site-resolver',
            ],
        ],
    ],
];
