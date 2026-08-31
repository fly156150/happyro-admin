<?php

namespace App\Http\Resources\Auth;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CurrentUserResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $this->resource->loadMissing('roles.permissions');
        $roles = $this->resource->roles->pluck('name')->values();
        $permissions = $roles->contains('super_admin')
            ? collect(['*'])
            : $this->resource->roles
                ->flatMap->permissions
                ->pluck('name')
                ->unique()
                ->values();

        return [
            'id' => $this->resource->getKey(),
            'userid' => $this->resource->username,
            'username' => $this->resource->username,
            'name' => $this->resource->name,
            'access' => $roles->contains('super_admin') ? 'admin' : 'user',
            'roles' => $roles,
            'permissions' => $permissions,
        ];
    }
}
