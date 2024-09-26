<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Rol;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class RolesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try{
            $rol = Rol::all();
            return ApiResponse::success('Listado de rol',200,$rol);
        } catch(Exception $e){
            return ApiResponse::error('Error al obtener los roles: '.$e->getMessage(),500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try{
            $request->validate([
                'nombre'=>'required|unique:roles',
                'slug'=>'required|unique:roles'
            ]);
            $rol = Rol::create($request->all());
            return ApiResponse::success('Rol creado exitosamente',201,$rol);
        }catch(ValidationException $e){
            return ApiResponse::error('Error de validacion: '.$e->getMessage(),422);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        try{
            $rol= Rol::findOrFail($id);
            return ApiResponse::success('Rol obtenido exitosamente',200,$rol);
        }catch(ModelNotFoundException $e){
            return ApiResponse::error('Rol no encontrado',404);

        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        try{
            $rol = Rol::findOrFail($id);
            $request->validate([
                'nombre'=>['required',Rule::unique('roles')->ignore($rol)],
                'slug'=>['required',Rule::unique('roles')->ignore($rol)]
            ]);
                $rol->update($request->all());
                return ApiResponse::success('Rol actualizado exitosamente',200,$rol);
        } catch(ModelNotFoundException $e){
            return ApiResponse::error('Rol no encontrado',404);
        }catch(Exception $e){
            return ApiResponse::error('Error: '.$e->getMessage(),422);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
       try{
        $rol = Rol::findOrFail($id);
        $rol->delete();
        return ApiResponse::success('Rol eliminado exitosamente',200);
       }catch(ModelNotFoundException $e){
            return ApiResponse::error('Rol no encontrado',404);
       }
    }
}
