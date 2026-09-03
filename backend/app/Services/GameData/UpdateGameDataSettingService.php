<?php

namespace App\Services\GameData;

use App\Contracts\Audit\AuditWriter;
use App\Contracts\GameData\GameDataSettingRepository;
use App\Data\Auth\ClientContext;
use App\Data\GameData\GameDataVersions;
use App\Models\User;
use Illuminate\Validation\ValidationException;

final readonly class UpdateGameDataSettingService
{
    public function __construct(private GameDataSettingRepository $settings, private AuditWriter $audit) {}

    public function update(string $clientVersion, string $serverVersion, User $operator, ClientContext $context): GameDataVersions
    {
        $available = $this->settings->available();
        $errors = [];
        if (! in_array($clientVersion, $available['client'], true)) {
            $errors['clientVersion'] = [__('messages.game_data_client_version_invalid')];
        }
        if (! in_array($serverVersion, $available['server'], true)) {
            $errors['serverVersion'] = [__('messages.game_data_server_version_invalid')];
        }
        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
        $before = $this->settings->current();
        $updated = $this->settings->save(new GameDataVersions($clientVersion, $serverVersion));
        $this->audit->write('settings.game_data_updated', $context, $operator, metadata: [
            'before' => $before->toArray(), 'after' => $updated->toArray(),
        ]);

        return $updated;
    }
}
