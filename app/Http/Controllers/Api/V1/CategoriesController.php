<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Categorie;
use Exception;
use GrahamCampbell\ResultType\Success;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CategoriesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $order = $request->input('order', 'desc');
            $searchTerm = $request->input('search', '');

            $query = Categorie::query()->orderBy('created_at', $order);
            // Aplicar el filtro de búsqueda global
            if (!empty($searchTerm)) {
                $query->where(function ($subQuery) use ($searchTerm) {
                    $subQuery->where('name', 'like', "%{$searchTerm}%");
                });
            }

            // Si no se proporciona un parámetro de paginación, devolver todas las categorías
            if ($request->has('paginate') && $request->input('paginate') == 'false') {
                $categorias = $query->get();
            } else {
                // De lo contrario, aplicar la paginación
                $categorias = $query->paginate(10);
            }
            return ApiResponse::success('Lista de categorias', 200, $categorias);
        } catch (Exception $e) {
            return ApiResponse::error('Error al obtener las categorias: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|unique:categories',
                'slug' => 'required|unique:categories'
            ]);
            $categoria = Categorie::create($request->all());
            return ApiResponse::success('Categoria creada exitosamente', 201, $categoria);
        } catch (ValidationException $e) {
            $errors = $e->errors();
            return ApiResponse::error('Error de validacion cat: ' . $e->getMessage(), 422, $errors);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        try {
            $categoria = Categorie::findOrFail($id);
            return ApiResponse::success('Categoria obtenida exitosamente', 200, $categoria);
        } catch (ModelNotFoundException $e) {
            return ApiResponse::error('Categoria no encontrada', 404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        try {
            $categoria = Categorie::findOrFail($id);
            $request->validate([
                'name' => ['required', Rule::unique('categories')->ignore($categoria)],
                'slug' => ['required', Rule::unique('categories')->ignore($categoria)]
            ]);
            $categoria->update($request->all());
            return ApiResponse::success('Categoria actualizada exitosamente', 200, $categoria);
        } catch (ModelNotFoundException $e) {
            return ApiResponse::error('Categoria no encontrada', 404);
        } catch (ValidationException $e) {
            $errors = $e->errors();
            return ApiResponse::error('Error: ' . $e->getMessage(), 422, $errors);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {
            $categoria = Categorie::findOrFail($id);
            $categoria->delete();
            return ApiResponse::success('Categoria eliminada exitosamnete', 200);
        } catch (ModelNotFoundException $e) {
            return ApiResponse::error('Categoria no encontrada', 404);
        }
    }

    public function articulosPorCategoria($id)
    {
        try {
            $categoria = Categorie::with('product')->findOrFail($id);
            return ApiResponse::success('Categoria y lista de articulos', 200, $categoria);
        } catch (ModelNotFoundException $e) {
            return ApiResponse::error('Categoria no encontrada', 404);
        }
    }
}
