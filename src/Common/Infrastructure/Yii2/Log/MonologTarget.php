<?php

declare(strict_types=1);

namespace src\Common\Infrastructure\Yii2\Log;

use common\models\user\UserBookingModule;
use yii\base\Application;
use yii\base\InvalidConfigException;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;
use yii\log\Logger;
use yii\log\Target;
use yii\web\IdentityInterface;
use yii\web\Request;
use yii\web\User;

class MonologTarget extends Target
{
    public string $app = 'common';
    private ?array $userInfo = null;

    public function export(): void
    {
        $formattedMessages = array_map([$this, 'formatMessage'], $this->messages);
        foreach ($formattedMessages as $message) {
            \Yii::$app->getLogger()->log(
                $message['level'],
                $message['message'],
                $message['context']
            );
        }
    }

    public function formatMessage($log): array
    {
        [$message, $level, $category] = $log;
        $traces = self::formatTracesIfExists($log);
        $record = [
            'level' => $this->yiiLogLevelToPsr($level),
            'context' => [
                'user' => $this->getUserInfo(),
                'category' => $category,
            ],
            'message' => $this->extractMessage($message),
        ];

        if (!empty($traces)) {
            $record['context']['traces'] = $traces;
        }

        return $record;
    }

    private function yiiLogLevelToPsr(int $yiiLogLevel): string
    {
        $levelMap = [
            Logger::LEVEL_TRACE => 'debug',
            Logger::LEVEL_PROFILE_BEGIN => 'debug',
            Logger::LEVEL_PROFILE_END => 'debug',
            Logger::LEVEL_PROFILE => 'debug',
            Logger::LEVEL_INFO => 'info',
            Logger::LEVEL_WARNING => 'warning',
            Logger::LEVEL_ERROR => 'error',
        ];

        return $levelMap[$yiiLogLevel] ?? 'info';
    }

    protected static function formatTracesIfExists($log): array
    {
        try {
            $traces = ArrayHelper::getValue($log, 4, []);
            $formattedTraces = array_map(static function ($trace) {
                return "in {$trace['file']}:{$trace['line']}";
            }, $traces);

            $message = ArrayHelper::getValue($log, 0);
            if ($message instanceof \Exception) {
                $tracesFromException = explode("\n", $message->getTraceAsString());
                $formattedTraces = array_merge($formattedTraces, $tracesFromException);
            }
            return $formattedTraces;
        } catch (\Throwable $ex) {
            return [];
        }
    }

    protected function extractMessage($message): string
    {
        $result = $message;

        if ($message instanceof \Exception) {
            $file = $message->getFile() . ':' . $message->getLine();
            $result = [
                'message' => $message->getMessage(),
                'file' => $file,
            ];
        }

        return Json::encode($result);
    }

    protected function getUserInfo(): array
    {
        //...
        return [];
    }
}