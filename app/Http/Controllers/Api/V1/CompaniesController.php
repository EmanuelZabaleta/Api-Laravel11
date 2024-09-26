<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Company;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class CompaniesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $company = Company::all();
            $company->transform(function ($company) {
                $company->image_url = url('storage/' . $company->image_url);
                return $company;
            });
            return ApiResponse::success('Lista de companies', 200, $company);
        } catch (Exception $e) {
            return ApiResponse::error('Error al obtener las companies: ' . $e->getMessage(), 500);
        }
    }


    public function update(Request $request, $id)
    {
        try {
            $company = Company::findOrFail($id);

            // Validar los campos de entrada
            $validatedData = $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'address' => 'nullable|string|max:255',
                'phone_number' => 'nullable|string|max:20|regex:/^[0-9]+$/',
                'email' => 'nullable|string|email|max:255|unique:companies,email,' . $id,
                'instagram' => 'nullable|string|max:255',
                'facebook' => 'nullable|string|max:255',
                'twitter' => 'nullable|string|max:255',
                'image_url' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
            ]);

            $company->name = $validatedData['name'];
            $company->address = $validatedData['address'];
            $company->phone_number = $validatedData['phone_number'];
            $company->email = $validatedData['email'];
            $company->instagram = $validatedData['instagram'];
            $company->facebook = $validatedData['facebook'];
            $company->twitter = $validatedData['twitter'];


            if ($request->hasFile('image_url')) {
                // Eliminar la imagen anterior si existe
                if ($company->image_url && Storage::exists('public/' . $company->image_url)) {
                    Storage::delete('public/' . $company->image_url);
                }

                // Subir la nueva imagen
                $image = $request->file('image_url');
                $imageName = 'company/' . time() . '_' . $image->getClientOriginalName();
                $image->storeAs('public', $imageName);

                // Guardar la URL de la imagen en la base de datos
                $company->image_url = $imageName;
            }

            // Guardar los cambios
            $company->save();

            return ApiResponse::success('Compañia actualizada exitosamente', 200, $company);
        } catch (ModelNotFoundException $e) {
            return ApiResponse::error('Compañia no encontrado', 404);
        } catch (ValidationException $e) {
            $errors = $e->errors();
            return ApiResponse::error('Error: ' . $e->getMessage(), 422, $errors);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Company $company)
    {
        //
    }
}
