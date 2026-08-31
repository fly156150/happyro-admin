<?php

namespace App\Services\Auth;

use App\Contracts\Audit\AuditWriter;
use App\Contracts\Auth\UserProvisioner;
use App\Data\Auth\ClientContext;
use App\Data\Auth\CreateUserData;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\DatabaseManager;

final class EloquentUserProvisioner implements UserProvisioner
{
    public function __construct(
        private readonly DatabaseManager $database,
        private readonly AuditWriter $audit,
    ) {}

    public function create(CreateUserData $data): User
    {
        return $this->database->transaction(function () use ($data): User {
            $role = Role::query()->firstOrCreate(
                ['name' => $data->role],
                ['label' => $this->roleLabel($data->role)],
            );
            $user = User::query()->create([
                'username' => $data->username,
                'name' => $data->name,
                'password' => $data->password,
                'is_active' => true,
            ]);
            $user->roles()->attach($role);
            $this->audit->write(
                'user.created',
                new ClientContext(null, 'artisan'),
                $user,
                $user->username,
                ['role' => $role->name, 'actor' => 'console'],
            );

            return $user;
        });
    }

    private function roleLabel(string $role): string
    {
        return match ($role) {
            'super_admin' => '超级管理员',
            'administrator' => '管理员',
            'operator' => '运营人员',
            'auditor' => '审计员',
        };
    }
}
