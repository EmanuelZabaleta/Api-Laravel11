<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Image;
use App\Models\Products;
use Dotenv\Exception\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Exception;
use App\Http\Responses\ApiResponse;

class ProductImagesController extends Controller
{
    public function show($productId)
    {
        try {
            $product = Products::with('images')->findOrFail($productId);
            return ApiResponse::success('Listado de las imagenes del producto', 200, $product->images);
        } catch (Exception $e) {
            return ApiResponse::error('Error al obtener las imagenes del producto: ' . $e->getMessage(), 500);
        }
    }

    public function store(Request $request, $productId)
    {
        $request->validate([
            'images.*' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'product_name' => 'required|string|max:255',
        ]);

        try {
            $product = Products::findOrFail($productId);
            $productName = $request->input('product_name');
            $uploadedImages = [];
            foreach ($request->file('images') as $image) {
                $path = $image->store('products', 'public');
                $imageRecord = Image::create([
                    'url' => $path,
                    'imageable_id' => $product->id,
                    'imageable_type' => Products::class,
                    'product_name' => $productName,
                ]);
                $uploadedImages[] = $imageRecord;
            }
            return ApiResponse::success('Imágenes subidas exitosamente', 200, $uploadedImages);
        } catch (ModelNotFoundException $e) {
            return ApiResponse::error('Producto no encontrado', 404);
        } catch (Exception $e) {
            return ApiResponse::error('Error al subir las imágenes: ' . $e->getMessage(), 422);
        }
    }

    public function destroy($productId, $imageId)
    {
        try {
            $image = Image::where('imageable_id', $productId)
                ->where('id', $imageId)
                ->where('imageable_type', Products::class)
                ->firstOrFail();
            Storage::disk('public')->delete($image->url);
            $image->delete();

            return ApiResponse::success('Imagen eliminada exitosamente', 200);
        } catch (ModelNotFoundException $e) {
            return ApiResponse::error('Imagen no encontrada', 404);
        } catch (Exception $e) {
            return ApiResponse::error('Error al eliminar las imágenes: ' . $e->getMessage(), 500);
        }
    }
}
