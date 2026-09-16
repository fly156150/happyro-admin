<?php

namespace App\Data\GameServer;

enum GameServerCommandStatus: string
{
    case Pending = 'pending';
    case Running = 'running';
    case Succeeded = 'succeeded';
    case Failed = 'failed';
    case Indeterminate = 'indeterminate';

    public function canTransitionTo(self $next): bool
    {
        return match ($this) {
            self::Pending, self::Failed => $next === self::Running,
            self::Running => in_array($next, [self::Succeeded, self::Failed, self::Indeterminate], true),
            self::Indeterminate => in_array($next, [self::Running, self::Succeeded, self::Failed], true),
            self::Succeeded => false,
        };
    }
}
