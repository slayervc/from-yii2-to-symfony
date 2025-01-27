<?php

declare(strict_types=1);

namespace src\Common\Infrastructure\Yii2\Http;

use yii\web\Response;

class SilentResponse extends Response
{
    public function send(): void
    {
        if ($this->isSent) {
            return;
        }

        $this->trigger(self::EVENT_BEFORE_SEND);
        $this->prepare();
        $this->trigger(self::EVENT_AFTER_PREPARE);
        $this->trigger(self::EVENT_AFTER_SEND);
        $this->isSent = true;
    }
}