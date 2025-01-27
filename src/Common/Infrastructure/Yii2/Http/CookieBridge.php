<?php

declare(strict_types=1);

namespace src\Common\Infrastructure\Yii2\Http;

use src\Common\Infrastructure\Yii2\Application;
use Symfony\Component\HttpFoundation\Cookie;

abstract class CookieBridge
{
    /**
     * @param Application $app
     * @return Cookie[]
     */
    public static function getYiiApplicationCookiesAsSymfonyCookies(Application $app): array
    {
        $result = [];
        $yiiCookies = $app->response->getCookies()->toArray();
        $request = $app->getRequest();
        if ($request->enableCookieValidation && !empty($request->cookieValidationKey)) {
            $validationKey = $request->cookieValidationKey;
        }

        foreach ($yiiCookies as $cookie) {
            $value = $cookie->value;
            if ($cookie->expire != 1 && isset($validationKey)) {
                $value = $app->getSecurity()->hashData(serialize([$cookie->name, $value]), $validationKey);
            }

            $result[] = new Cookie(
                $cookie->name,
                $value,
                $cookie->expire,
                $cookie->path,
                $cookie->domain,
                $cookie->secure,
                $cookie->httpOnly,
                false,
                $cookie->sameSite
            );
        }

        return $result;
    }
}