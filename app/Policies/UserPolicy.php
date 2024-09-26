<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
class UserPolicy
{
    use HandlesAuthorization;
    /**
     * Create a new policy instance.
     */
    public function delete(User $authUser, User $user)
    {
        // Permitir la eliminación solo si el usuario autenticado es el mismo que el usuario a eliminar
        // y si el ID del usuario a eliminar no es 1
        if ($user->id === 1) {
            return false; // No se puede eliminar el usuario con ID 1
        }

        return true;
    }

    public function update(User $authUser, User $user)
    {
        // Permitir solo si el ID del usuario a actualizar no es 1
        // y también verificar si el usuario autenticado no es el mismo
        return $user->id !== 1 && $authUser->id !== $user->id;
    }
}
