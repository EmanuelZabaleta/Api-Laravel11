<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Responses\ApiResponse;
use App\Models\Customers;
use Exception;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class CustomersController extends Controller
{
    public function index(Request $request)
    {
        try {
            $order = $request->input('order', 'desc');
            $searchTerm = $request->input('search', '');

            $query = Customers::query()->orderBy('created_at', $order);


            // Aplicar el filtro de búsqueda global
            if (!empty($searchTerm)) {
                // Divide el término de búsqueda en palabras
                $searchTerms = explode(' ', $searchTerm);

                $query->where(function ($subQuery) use ($searchTerms) {
                    foreach ($searchTerms as $term) {
                        $subQuery->where(function ($nestedQuery) use ($term) {
                            $nestedQuery->where('name', 'like', "%{$term}%")
                                ->orWhere('lastname', 'like', "%{$term}%")
                                ->orWhere('national_id', 'like', "%{$term}%")
                                ->orWhere('account_number', 'like', "%{$term}%");
                        });
                    }
                });
            }

            $users = $query->paginate(10);
            return ApiResponse::success('Lista de usuarios', 200, $users);
        } catch (Exception $e) {
            return ApiResponse::error('Error al obtener los usuarios: ' . $e->getMessage(), 500);
        }
    }


    public function store(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'lastname' => 'required|string|max:255',
                'address' => 'required|string',
                'phone_number' => 'nullable|string|max:20',
                'birthdate' => 'nullable|date',
                'national_id' => 'nullable|string|max:50|unique:customers,national_id',
                'status' => 'required|in:Active,Inactive,Suspended',
                'gender' => 'nullable|in:male,female,other',
            ]);

            // Genera un número de cuenta único
            $accountNumber = $this->generateUniqueAccountNumber();

            // Crear el cliente con el número de cuenta
            $customer = Customers::create(array_merge($request->all(), [
                'account_number' => $accountNumber,
            ]));

            return ApiResponse::success('Cliente creado exitosamente', 201, $customer);
        } catch (ValidationException $e) {
            $errors = $e->errors();
            return ApiResponse::error('Error de validación: ' . $e->getMessage(), 422, $errors);
        } catch (\Exception $e) {
            return ApiResponse::error('Error al crear el cliente: ' . $e->getMessage(), 500);
        }
    }

    private function generateUniqueAccountNumber()
    {
        do {
            // Genera un número aleatorio de 10 dígitos
            $accountNumber = random_int(1000000000, 9999999999);
        } while (Customers::where('account_number', $accountNumber)->exists());

        return $accountNumber;
    }

    public function update(Request $request, $id)
    {
        try {
            // Validar los campos, excepto el 'account_number'
            $request->validate([
                'name' => 'required|string|max:255',
                'lastname' => 'required|string|max:255',
                'address' => 'required|string',
                'phone_number' => 'nullable|string|max:20',
                'birthdate' => 'nullable|date',
                'national_id' => 'nullable|string|max:50|unique:customers,national_id,' . $id,
                'status' => 'required|in:Active,Inactive,Suspended',
                'gender' => 'nullable|in:male,female,other',
            ]);

            // Encontrar al cliente por ID
            $customer = Customers::findOrFail($id);

            // Actualizar los campos, excluyendo 'account_number'
            $customer->update($request->except('account_number'));

            return ApiResponse::success('Cliente actualizado exitosamente', 200, $customer);
        } catch (ValidationException $e) {
            $errors = $e->errors();
            return ApiResponse::error('Error de validación: ' . $e->getMessage(), 422, $errors);
        } catch (\Exception $e) {
            return ApiResponse::error('Error al actualizar el cliente: ' . $e->getMessage(), 500);
        }
    }

    public function destroy($id)
    {
        try {
            $customer = Customers::findOrFail($id);
            $customer->delete();
            return ApiResponse::success('Cliente eliminado exitosamnete', 200);
        } catch (ModelNotFoundException $e) {
            return ApiResponse::error('Cliente no encontrado', 404);
        }
    }

    public function forceDelete($id)
    {
        try {
            $customer = Customers::onlyTrashed()->findOrFail($id);
            $customer->forceDelete();
            return ApiResponse::success('Cliente eliminado exitosamnete', 200);
        } catch (ModelNotFoundException $e) {
            return ApiResponse::error('Cliente no encontrado', 404);
        }
    }

    public function restore($id)
    {
        try {
            $customer = Customers::onlyTrashed()->findOrFail($id); // Buscar en los eliminados
            $customer->restore(); // Restaurar el usuario
            return ApiResponse::success('Cliente restaurado correctamente', 200);
        } catch (\Exception $e) {
            return ApiResponse::error('Error al restaurar el cliente: ' . $e->getMessage(), 500);
        }
    }

    public function trashedAccounts(Request $request)
    {
        try {
            $order = $request->input('order', 'desc');
            $searchTerm = $request->input('search', '');

            $query = Customers::onlyTrashed()->orderBy('created_at', $order);


            // Aplicar el filtro de búsqueda global
            if (!empty($searchTerm)) {
                // Divide el término de búsqueda en palabras
                $searchTerms = explode(' ', $searchTerm);

                $query->where(function ($subQuery) use ($searchTerms) {
                    foreach ($searchTerms as $term) {
                        $subQuery->where(function ($nestedQuery) use ($term) {
                            $nestedQuery->where('name', 'like', "%{$term}%")
                                ->orWhere('lastname', 'like', "%{$term}%")
                                ->orWhere('national_id', 'like', "%{$term}%")
                                ->orWhere('account_number', 'like', "%{$term}%");
                        });
                    }
                });
            }

            $users = $query->paginate(10);
            return ApiResponse::success('Lista de usuarios', 200, $users);
        } catch (Exception $e) {
            return ApiResponse::error('Error al obtener los usuarios: ' . $e->getMessage(), 500);
        }
    }
}
