<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\SubCategories;
use Illuminate\Http\Request;
use App\Http\Responses\ApiResponse;
use App\Models\Categorie;
use Exception;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\Rule;

class SubCategoriesController extends Controller
{
    public function index(Request $request)
    {
        try {
            $order = $request->input('order', 'desc');
            $searchTerm = $request->input('search', '');
            $categoryFilter = $request->input('category', '');


            $query = SubCategories::with(['category' => function ($query1) {
                $query1->select('id', 'name'); // Solo selecciona 'id' y 'name'
            }])->orderBy('created_at', $order);

            // Aplicar el filtro de evento solo si está presente
            if (!empty($categoryFilter)) {
                if (is_numeric($categoryFilter)) {
                    $query->where('category_id', $categoryFilter);
                } else {
                    $categoryId = Categorie::where('name', $categoryFilter)->pluck('id')->first();
                    if ($categoryId) {
                        $query->where('category_id', $categoryId);
                    }
                }
            }

            if (!empty($searchTerm)) {
                $query->where(function ($subQuery) use ($searchTerm) {
                    $subQuery->where('name', 'like', "%{$searchTerm}%");
                });
            }
            // Si no se proporciona un parámetro de paginación, devolver todas las subcategorías
            if ($request->has('paginate') && $request->input('paginate') == 'false') {
                $subcategoria = $query->get();
            } else {
                // De lo contrario, aplicar la paginación
                $subcategoria = $query->paginate(10);
            }
            return ApiResponse::success('Lista de subcategorias', 200, $subcategoria);
        } catch (Exception $e) {
            return ApiResponse::error('Error al obtener las subcategorias: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|unique:subcategories',
                'slug' => 'required|unique:subcategories',
                'category_id' => 'required|exists:categories,id'
            ]);
            $subcategoria = SubCategories::create($request->all());
            return ApiResponse::success('Subcategoria creada exitosamente', 201, $subcategoria);
        } catch (ValidationException $e) {
            $errors = $e->errors();
            return ApiResponse::error('Error de validacion: ' . $e->getMessage(), 422, $errors);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        try {
            $subcategoria = SubCategories::findOrFail($id);
            return ApiResponse::success('Subcategoria obtenida exitosamente', 200, $subcategoria);
        } catch (ModelNotFoundException $e) {
            return ApiResponse::error('Subcategoria no encontrada', 404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        try {
            $subcategoria = SubCategories::findOrFail($id);
            $request->validate([
                'name' => ['required', Rule::unique('subcategories')->ignore($subcategoria)],
                'slug' => ['required', Rule::unique('subcategories')->ignore($subcategoria)],
                'category_id' => ['required', 'exists:categories,id']
            ]);
            $subcategoria->update($request->all());
            return ApiResponse::success('Subcategoria actualizada exitosamente', 200, $subcategoria);
        } catch (ModelNotFoundException $e) {
            return ApiResponse::error('Subcategoria no encontrada', 404);
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
            $subcategoria = SubCategories::findOrFail($id);
            $subcategoria->delete();
            return ApiResponse::success('Subcategoria eliminada exitosamnete', 200);
        } catch (ModelNotFoundException $e) {
            return ApiResponse::error('Subcategoria no encontrada', 404);
        }
    }

    public function articulosPorCategoria($id)
    {
        try {
            $subcategoria = SubCategories::with('product')->findOrFail($id);
            return ApiResponse::success('Subcategoria y lista de articulos', 200, $subcategoria);
        } catch (ModelNotFoundException $e) {
            return ApiResponse::error('Subcategoria no encontrada', 404);
        }
    }
}
