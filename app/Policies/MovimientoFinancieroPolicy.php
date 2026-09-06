<?php

namespace App\Policies;

use App\Enums\Permiso;
use App\Models\MovimientoFinanciero;
use App\Models\User;

class MovimientoFinancieroPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permiso::MovimientosVer->value);
    }

    public function view(User $user, MovimientoFinanciero $movimientoFinanciero): bool
    {
        return $user->can(Permiso::MovimientosVer->value) && $user->can('view', $movimientoFinanciero->causa);
    }

    public function create(User $user): bool
    {
        return $user->can(Permiso::MovimientosCrear->value);
    }

    public function update(User $user, MovimientoFinanciero $movimientoFinanciero): bool
    {
        return $user->can(Permiso::MovimientosEditar->value) && $user->can('view', $movimientoFinanciero->causa);
    }

    public function delete(User $user, MovimientoFinanciero $movimientoFinanciero): bool
    {
        return $user->can(Permiso::MovimientosEliminar->value) && $user->can('view', $movimientoFinanciero->causa);
    }

    public function restore(User $user, MovimientoFinanciero $movimientoFinanciero): bool
    {
        return $user->can(Permiso::MovimientosEliminar->value) && $user->can('view', $movimientoFinanciero->causa);
    }

    public function forceDelete(User $user, MovimientoFinanciero $movimientoFinanciero): bool
    {
        return $user->can(Permiso::MovimientosEliminar->value) && $user->can('view', $movimientoFinanciero->causa);
    }
}
