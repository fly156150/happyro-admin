<?php

namespace App\Support;

use App\Exceptions\GameServerGatewayException;

final class GameServerErrorMessage
{
    public static function for(string $code, ?string $fallback = null): string
    {
        $key = "messages.{$code}";
        $message = __($key);

        return $message === $key ? ($fallback ?? '游戏服务暂时不可用') : $message;
    }

    public static function from(GameServerGatewayException $exception): string
    {
        return self::for($exception->errorCode, $exception->getMessage());
    }
}
