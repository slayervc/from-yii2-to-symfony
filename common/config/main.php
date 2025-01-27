<?php

return [
    'components' => [
        'errorHandler' => null,
        'response' => [
            'class' => \src\Common\Infrastructure\Yii2\Http\SilentResponse::class
        ],
        'log' => [
            'targets' => [
                [
                    'class' => \src\Common\Infrastructure\Yii2\Log\MonologTarget::class
                ],
            ],
        ],
    ],
];
