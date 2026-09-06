<?php

namespace App\Policies;

use App\Enums\Permiso;
use App\Models\Actuacion;
use App\Models\User;

class ActuacionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permiso::ActuacionesVer->value);
    }

    public function view(User $user, Actuacion $actuacion): bool
    {
        return $user->can(Permiso::ActuacionesVer->value) && $user->can('view', $actuacion->causa);
    }

    public function create(User $user): bool
    {
        return $user->can(Permiso::ActuacionesCrear->value);
    }

    public function update(User $user, Actuacion $actuacion): bool
    {
        return $user->can(Permiso::ActuacionesEditar->value) && $user->can('view', $actuacion->causa);
    }

    public function delete(User $user, Actuacion $actuacion): bool
    {
        return $user->can(Permiso::ActuacionesEliminar->value) && $user->can('view', $actuacion->causa);
    }

    public function restore(User $user, Actuacion $actuacion): bool
    {
        return $user->can(Permiso::ActuacionesEliminar->value) && $user->can('view', $actuacion->causa);
    }

    public function forceDelete(User $user, Actuacion $actuacion): bool
    {
        return $user->can(Permiso::ActuacionesEliminar->value) && $user->can('view', $actuacion->causa);
    }
}
