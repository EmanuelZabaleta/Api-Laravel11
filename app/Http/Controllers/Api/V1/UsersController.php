<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Responses\ApiResponse;
use App\Models\Rol;
use Dotenv\Exception\ValidationException;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Gate;

class UsersController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $order = $request->input('order', 'desc');
            $searchTerm = $request->input('search', '');
            $roleFilter = $request->input('role', '');

            $query = User::with('rol')->orderBy('created_at', $order);

            // Aplicar el filtro de evento solo si está presente
            if (!empty($roleFilter)) {
                if (is_numeric($roleFilter)) {
                    $query->where('rol_id', $roleFilter);
                } else {
                    $roleId = Rol::where('name', $roleFilter)->pluck('id')->first();
                    if ($roleId) {
                        $query->where('rol_id', $roleId);
                    }
                }
            }
            // Aplicar el filtro de búsqueda global
            if (!empty($searchTerm)) {
                $query->where(function ($subQuery) use ($searchTerm) {
                    $subQuery->where('name', 'like', "%{$searchTerm}%")
                        ->orWhere('email', 'like', "%{$searchTerm}%");
                });
            }

            $users = $query->paginate(10);
            return ApiResponse::success('Lista de usuarios', 200, $users);
        } catch (Exception $e) {
            return ApiResponse::error('Error al obtener los usuarios: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8',
                'rol_id' => 'required|exists:roles,id',
            ]);

            // Crear el usuario
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => bcrypt($request->password),
                'rol_id' => $request->rol_id,
            ]);

            // Asignar roles al usuario si se proporcionan
            if ($request->has('roles')) {
                $user->roles()->attach($request->roles);
            }

            $user->companies()->attach(1);
            return ApiResponse::success('Usuario creado exitosamente', 201, $user);
        } catch (ValidationException $e) {
            return ApiResponse::error('Error de validación: ' . $e->getMessage(), 422);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        try {
            $user = User::findOrFail($id);
            return ApiResponse::success('Usuario obtenido exitosamente', 200, $user);
        } catch (ModelNotFoundException $e) {
            return ApiResponse::error('Usuario no encontrado', 404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        try {
            // Verificar si el usuario autenticado tiene permiso para actualizar al usuario especificado
            $userToUpdate = User::findOrFail($id);


            // Validar los campos de entrada
            $validatedData = $request->validate([
                'name' => ['required', Rule::unique('users', 'name')->ignore($userToUpdate->id)],
                'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($userToUpdate->id)],
                'password' => ['sometimes', 'string', 'min:8'],
                'rol_id' => ['required', 'exists:roles,id'],
            ]);

            // Actualizar la contraseña solo si se proporciona
            if ($request->filled('password')) {
                $userToUpdate->password = Hash::make($request->input('password'));
            }

            // Actualizar otros campos
            $userToUpdate->name = $validatedData['name'];
            $userToUpdate->email = $validatedData['email'];
            $userToUpdate->rol_id = $validatedData['rol_id'];

            // Guardar los cambios
            $userToUpdate->save();

            return ApiResponse::success('Usuario actualizado exitosamente', 200, $userToUpdate);
        } catch (ModelNotFoundException $e) {
            return ApiResponse::error('Usuario no encontrado', 404);
        } catch (ValidationException $e) {
            return ApiResponse::error('Error: ' . $e->getMessage(), 422);
        }
    }
    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {
            // Verifica si el usuario con el id 1 está intentando ser eliminado
            if ($id == 1) {
                return ApiResponse::error('No se puede eliminar el usuario con id 1.', 403);
            }

            // Encuentra al usuario por id
            $user = User::findOrFail($id);

            // Verifica que el usuario tenga permisos para eliminar el recurso
            if (Gate::denies('delete', $user)) {
                return ApiResponse::error('No tienes permiso para eliminar este usuario.', 403);
            }

            // Elimina al usuario
            $user->delete();

            return ApiResponse::success('Usuario eliminado exitosamente', 200);
        } catch (ModelNotFoundException $e) {
            return ApiResponse::error('Usuario no encontrado', 404);
        } catch (\Exception $e) {
            return ApiResponse::error('Error al eliminar el usuario: ' . $e->getMessage(), 500);
        }
    }
}
