<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Exception;
use App\Http\Responses\ApiResponse;
use App\Models\ProductStock;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ProductsStockController extends Controller
{
    public function index()
    {
        try {
            $productStock = ProductStock::all();
            return ApiResponse::success('Listado de Stock', 200, $productStock);
        } catch (Exception $e) {
            return ApiResponse::error('Error al obtener los Stock: ' . $e->getMessage(), 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'product_id' => 'required|exists:products,id',
                'warehouse_id' => 'required|exists:warehouses,id',
                'stock' => 'required|integer|min:0',
                'product_name' => 'required|string|max:255',
            ]);
            $productStock = ProductStock::create($request->all());
            return ApiResponse::success('Asignacion de stock establecido exitosamente', 201, $productStock);
        } catch (ValidationException $e) {
            $errors = $e->errors();
            return ApiResponse::error('Error de validacion: ' . $e->getMessage(), 422, $errors);
        }
    }

    public function show($id)
    {
        try {
            $productStock = ProductStock::findOrFail($id);
            return ApiResponse::success('Producto y stock obtenido exitosamente', 200, $productStock);
        } catch (ModelNotFoundException $e) {
            return ApiResponse::error('Producto no encontrado', 404);
        }
    }

    public function update(Request $request, $productId)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'stock' => 'required|integer|min:0',
            'product_name' => 'required|string|max:255',
        ]);

        try {
            $productStock = ProductStock::where('product_id', $productId)
                ->where('warehouse_id', $request->warehouse_id)
                ->firstOrFail();

            $productStock->update([
                'stock' => $request->stock,
                'product_name' => $request->product_name,
            ]);

            return ApiResponse::success('Producto y stock actualizado exitosamente', 200, $productStock);
        } catch (ModelNotFoundException $e) {
            return ApiResponse::error('Producto no encontrado en el almacén especificado', 404);
        } catch (Exception $e) {
            return ApiResponse::error('Error: ' . $e->getMessage(), 422);
        }
    }

    public function destroy($id)
    {
        try {
            $productStock = ProductStock::findOrFail($id);
            $productStock->delete();
            return ApiResponse::success('Producto y stock eliminado exitosamente', 200);
        } catch (ModelNotFoundException $e) {
            return ApiResponse::error('Producto no encontrado', 404);
        } catch (\Exception $e) {
            return ApiResponse::error('Error al eliminar el producto y stock: ' . $e->getMessage(), 500);
        }
    }
}
