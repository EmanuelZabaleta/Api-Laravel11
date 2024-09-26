<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Products;
use App\Models\ProductStock;
use App\Models\Warehouse;
use App\Models\WarehouseProductMove;
use Exception;
use Illuminate\Http\Request;
use Dotenv\Exception\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\Rule;

class WarehouseController extends Controller
{
    public function index(Request $request)
    {
        try {
            $searchTerm = $request->input('search', '');
            $order = $request->input('order', 'desc');

            $query = Warehouse::query()->orderBy('created_at', $order);

            if (!empty($searchTerm)) {
                $query->where(function ($subQuery) use ($searchTerm) {
                    $subQuery->where('name', 'like', "%{$searchTerm}%");
                });
            }
            // Si no se proporciona un parámetro de paginación, devolver todas los almacenes
            if ($request->has('paginate') && $request->input('paginate') == 'false') {
                $warehouse = $query->get();
            } else {
                // De lo contrario, aplicar la paginación
                $warehouse = $query->paginate(10);
            }
            return ApiResponse::success('Lista de almacenes', 200, $warehouse);
        } catch (Exception $e) {
            return ApiResponse::error('Error al obtener los almacenes: ' . $e->getMessage(), 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'slug' => 'required|string|max:255|unique:warehouses,slug',
                'address' => 'required|string|max:255',
                'city' => 'required|string|max:255',
                'state_province' => 'required|string|max:255',
                'postal_code' => 'required|string|max:20',
                'country' => 'required|string|max:255',
                'company_id' => 'required|exists:companies,id',
            ]);


            $warehouse = Warehouse::create($request->all());
            return ApiResponse::success('Almacen creado exitosamente', 201, $warehouse);
        } catch (ValidationException $e) {
            return ApiResponse::error('Error de validación: ' . $e->getMessage(), 422);
        }
    }

    public function show($id)
    {
        try {
            $user = Warehouse::findOrFail($id);
            return ApiResponse::success('Almacen obtenido exitosamente', 200, $user);
        } catch (ModelNotFoundException $e) {
            return ApiResponse::error('Almacen no encontrado', 404);
        }
    }


    public function update(Request $request, $id)
    {
        try {
            $warehouse = Warehouse::findOrFail($id);
            $request->validate([
                'name' => ['required', Rule::unique('warehouses')->ignore($warehouse)],
                'slug' => ['required', Rule::unique('warehouses')->ignore($warehouse)],
                'address' => 'required|string|max:255',
                'city' => 'required|string|max:255',
                'state_province' => 'required|string|max:255',
                'postal_code' => 'required|string|max:20',
                'country' => 'required|string|max:255',
                'register' => 'nullable|date',
            ]);
            $warehouse->update($request->all());
            return ApiResponse::success('Articulo actualizado exitosamente', 200, $warehouse);
        } catch (ModelNotFoundException $e) {
            return ApiResponse::error('Almacen no encontrado', 404);
        } catch (ValidationException $e) {
            $errors = $e->errors();
            return ApiResponse::error('Error: ' . $e->getMessage(), 422, $errors);
        }
    }


    public function destroy($id)
    {
        try {
            $warehouse = Warehouse::findOrFail($id);

            // Eliminar los productos asociados a este almacén
            $warehouse->productStock()->delete();

            // Luego, eliminar el almacén
            $warehouse->delete();

            return ApiResponse::success('Almacén eliminado exitosamente', 200);
        } catch (ModelNotFoundException $e) {
            return ApiResponse::error('Almacén no encontrado', 404);
        }
    }

    public function products(Request $request, $id)
    {
        try {
            $order = $request->input('order', 'desc');
            $searchTerm = $request->input('search', '');

            // Buscar el almacén con los productos relacionados
            $warehouse = Warehouse::with(['productStock' => function ($query) use ($searchTerm) {
                // Aplicar el filtro de búsqueda global
                if (!empty($searchTerm)) {
                    $query->whereHas('product', function ($subQuery) use ($searchTerm) {
                        $subQuery->where('name', 'like', "%{$searchTerm}%");
                    });
                }
            }, 'productStock.product'])->find($id);

            if (!$warehouse) {
                return response()->json(['message' => 'Warehouse not found'], 404);
            }

            // Obtener los productos con la información del stock y el campo `created_at`
            $products = $warehouse->productStock->map(function ($productStock) {
                return [
                    'id' => $productStock->product->id,
                    'name' => $productStock->product->name,
                    'price' => $productStock->product->price,
                    'stock' => $productStock->stock,
                    'created_at' => $productStock->created_at->toDateTimeString(), // Incluye `created_at`
                ];
            });

            // Ordenar los productos por `created_at`
            $products = $order === 'asc'
                ? $products->sortBy('created_at')->values()
                : $products->sortByDesc('created_at')->values();

            // Paginar manualmente si es necesario
            $perPage = 10; // Número de productos por página
            $currentPage = LengthAwarePaginator::resolveCurrentPage();
            $currentItems = $products->slice(($currentPage - 1) * $perPage, $perPage)->all();
            $paginatedProducts = new LengthAwarePaginator($currentItems, $products->count(), $perPage);


            // Retornar la respuesta paginada
            return ApiResponse::success('data', 200, $paginatedProducts);
        } catch (ModelNotFoundException $e) {
            return ApiResponse::error('Almacen no encontrado', 404);
        }
    }

    public function moveProduct(Request $request, $id)
    {
        $request->validate([
            'new_warehouse_id' => 'required|exists:warehouses,id',
            'moveOption' => 'required|string|in:all,partial',
            'quantity' => 'required_if:moveOption,partial|integer|min:1',
            'product_id' => 'required|exists:products,id',
        ]);

        try {
            $currentWarehouse = Warehouse::findOrFail($id);
            $newWarehouse = Warehouse::findOrFail($request->new_warehouse_id);

            $productStock = ProductStock::where('warehouse_id', $currentWarehouse->id)
                ->where('product_id', $request->product_id)
                ->firstOrFail();

            // Consultar el nombre más reciente del producto
            $product = Products::findOrFail($request->product_id);
            $productName = $product->name;

            $quantityToMove = ($request->moveOption === 'all') ? $productStock->stock : $request->quantity;

            if ($request->moveOption === 'all') {
                $productStock->delete();
            } else {
                if ($quantityToMove > $productStock->stock) {
                    return response()->json(['message' => 'La cantidad a mover excede el stock actual'], 422);
                }
                $productStock->stock -= $quantityToMove;
                $productStock->save();
            }

            $newProductStock = ProductStock::firstOrNew([
                'warehouse_id' => $newWarehouse->id,
                'product_id' => $request->product_id,
            ]);

            $newProductStock->stock += $quantityToMove;
            $newProductStock->product_name = $productName;
            $newProductStock->save();

            WarehouseProductMove::create([
                'product_id' => $request->product_id,
                'from_warehouse_id' => $currentWarehouse->id,
                'to_warehouse_id' => $request->new_warehouse_id,
                'quantity' => $quantityToMove,
            ]);

            return response()->json(['message' => 'Producto movido exitosamente'], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Almacén o producto no encontrado'], 404);
        } catch (Exception $e) {
            return response()->json(['message' => 'Error al mover producto: ' . $e->getMessage()], 500);
        }
    }
}
