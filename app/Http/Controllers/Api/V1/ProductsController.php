<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Products;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Exception;
use App\Http\Responses\ApiResponse;
use App\Models\Categorie;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProductsController extends Controller
{
    public function index(Request $request)
    {
        try {
            $searchTerm = $request->input('search', '');
            $order = $request->input('order', 'desc');
            $categoryFilter = $request->input('category', '');

            $query = Products::with(['assignedWarehouse.warehouse', 'category', 'subcategory']);
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
                    $subQuery->where('name', 'like', "%{$searchTerm}%")
                        ->orWhere('code', 'like', "%{$searchTerm}%");
                });
            }
            // Si no se proporciona un parámetro de paginación, devolver todas las subcategorías
            if ($request->has('paginate') && $request->input('paginate') == 'false') {
                $products = $query->orderBy('created_at')->get();
            } else {
                // De lo contrario, aplicar la paginación
                $products = $query->orderBy('created_at', $order)->paginate(10);
            }

            return ApiResponse::success('Listado de productos', 200, $products);
        } catch (Exception $e) {
            return ApiResponse::error('Error al obtener los productos: ' . $e->getMessage(), 500);
        }
    }


    public function store(Request $request)
    {
        try {

            $request->validate([
                'code' => 'required|string|max:255|unique:products,code',
                'name' => 'required|string|max:255',
                'slug' => 'required|string|max:255|unique:products,slug',
                'description' => 'required|string',
                'price' => 'required|numeric|regex:/^\d+(\.\d{1,2})?$/',
                'category_id' => 'required|exists:categories,id',
                'subcategory_id' => 'nullable|exists:subcategories,id',
                'user_id' => 'nullable|exists:users,id',
                'company_id' => 'nullable|exists:companies,id',
            ]);
            $product = Products::create($request->all());
            return ApiResponse::success('Producto creado exitosamente', 201, $product);
        } catch (ValidationException $e) {
            $errors = $e->errors();
            return ApiResponse::error('Error de validacion: ' . $e->getMessage(), 422, $errors);
        }
    }

    public function show($id)
    {
        try {
            $product = Products::findOrFail($id);
            return ApiResponse::success('Producto obtenido exitosamente', 200, $product);
        } catch (ModelNotFoundException $e) {
            return ApiResponse::error('Producto no encontrado', 404);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $product = Products::findOrFail($id);

            $request->validate([
                'code' => ['required', Rule::unique('products')->ignore($product)],
                'name' => ['required', Rule::unique('products')->ignore($product)],
                'slug' => ['required', Rule::unique('products')->ignore($product)],
                'description' => 'sometimes|required|string',
                'price' => 'required|numeric|regex:/^\d+(\.\d{1,2})?$/',
                'category_id' => 'sometimes|required|exists:categories,id',
                'subcategory_id' => 'nullable|exists:subcategories,id',
                'user_id' => 'nullable|exists:users,id',
                'company_id' => 'nullable|exists:companies,id',
            ]);
            $product->update($request->all());
            return ApiResponse::success('Producto actualizado exitosamente', 200, $product);
        } catch (ModelNotFoundException $e) {
            return ApiResponse::error('Producto no encontrado', 404);
        } catch (ValidationException $e) {
            $errors = $e->errors();
            return ApiResponse::error('Error: ' . $e->getMessage(), 422, $errors);
        }
    }


    public function destroy($id)
    {
        try {
            // Encuentra el producto por su ID
            $product = Products::findOrFail($id);

            // Elimina los registros relacionados en la tabla product_stocks
            DB::table('product_stocks')->where('product_id', $id)->delete();
            // Obtén todas las imágenes relacionadas con el producto
            $images = $product->images;

            // Elimina las imágenes del almacenamiento (storage)
            foreach ($images as $image) {
                Storage::disk('public')->delete($image->url);
            }

            // Elimina los registros de imágenes en la tabla images
            DB::table('images')->where('imageable_id', $id)->where('imageable_type', Products::class)->delete();



            // Elimina el producto
            $product->delete();

            return ApiResponse::success('Producto y stock eliminado exitosamente', 200);
        } catch (ModelNotFoundException $e) {
            return ApiResponse::error('Producto no encontrado', 404);
        } catch (\Exception $e) {
            return ApiResponse::error('Error al eliminar el producto: ' . $e->getMessage(), 500);
        }
    }
}
