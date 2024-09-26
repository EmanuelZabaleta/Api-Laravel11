<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Responses\ApiResponse;
use Exception;
use App\Models\Audit;
use App\Models\Categorie;
use App\Models\Company;
use App\Models\Image;
use App\Models\Products;
use App\Models\ProductStock;
use App\Models\Rol;
use App\Models\SubCategories;
use App\Models\Warehouse;

class AuditController extends Controller
{
    public function index(Request $request)
    {
        try {
            // Parámetros de la solicitud
            $order = $request->input('order', 'desc');
            $event = $request->input('event', '');
            $userId = $request->input('user_id');
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');
            $searchTerm = $request->input('search', '');

            // Construir la consulta
            $query = Audit::with(['user:id,name,email'])->orderBy('created_at', $order);

            // Aplicar filtros condicionales
            if (!empty($event)) {
                $query->where('event', $event);
            }

            if (!empty($userId)) {
                $query->where('user_id', $userId);
            }

            if (!empty($startDate) && !empty($endDate)) {
                $query->whereBetween('created_at', [$startDate, $endDate]);
            }

            if (!empty($searchTerm)) {
                $query->where(function ($subQuery) use ($searchTerm) {
                    $subQuery->whereHas('user', function ($q) use ($searchTerm) {
                        $q->where('name', 'like', "%{$searchTerm}%")
                            ->orWhere('email', 'like', "%{$searchTerm}%");
                    })
                        ->orWhere('event', 'like', "%{$searchTerm}%");
                });
            }

            // Cargar relaciones basadas en el tipo de auditable
            $query->with([
                'auditable' => function ($query) {
                    $type = $query->getModel();
                    $selections = [
                        Warehouse::class => ['id', 'name'],
                        Products::class => ['id', 'name'],
                        Categorie::class => ['name'],
                        SubCategories::class => ['id', 'name'],
                        Company::class => ['id', 'name'],
                        Image::class => ['id', 'product_id'],
                    ];

                    if (isset($selections[get_class($type)])) {
                        $query->select($selections[get_class($type)]);
                    } else {
                        $query->select('id');
                    }
                }
            ]);

            $audits = $query->paginate(10);

            // Transformar las auditorías
            $audits->getCollection()->transform(function ($audit) {
                $audit = $this->enrichAuditData($audit);
                $audit->auditable_name = $this->getAuditableName(
                    $audit->auditable_type,
                    $audit->auditable_id,
                    $audit->old_values,
                    $audit->new_values
                );

                $audit->new_values = $this->transformValues($audit->new_values);
                $audit->old_values = $this->transformValues($audit->old_values);

                return $audit;
            });

            return ApiResponse::success('Lista de auditoría', 200, $audits);
        } catch (Exception $e) {
            return ApiResponse::error('Error al obtener las auditorías: ' . $e->getMessage(), 500);
        }
    }

    private function transformValues(array $values)
    {
        $mappings = [
            'warehouse_id' => ['name' => 'warehouse_name', 'model' => Warehouse::class],
            'from_warehouse_id' => ['name' => 'from', 'model' => Warehouse::class],
            'to_warehouse_id' => ['name' => 'to', 'model' => Warehouse::class],
            'product_id' => ['name' => 'product_name', 'model' => Products::class],
            'rol_id' => ['name' => 'rol', 'model' => Rol::class],
            'imageable_id' => ['name' => 'product_name', 'model' => Products::class],
            'category_id' => ['name' => 'category_name', 'model' => Categorie::class],
            'subcategory_id' => ['name' => 'subcategory_name', 'model' => SubCategories::class],
            'company_id' => ['name' => 'company_name', 'model' => Company::class],
        ];

        foreach ($mappings as $key => $mapping) {
            if (isset($values[$key])) {
                $entity = $mapping['model']::find($values[$key]);

                if ($mapping['model'] === Image::class && $entity && isset($entity->product_id)) {
                    $product = Products::find($entity->product_id);
                    $values[$mapping['name']] = $product ? $product->name : null;
                } else {
                    $values[$mapping['name']] = $entity ? $entity->name : null;
                }

                unset($values[$key]);
            }
        }

        if (isset($values['password'])) {
            $values['password'] = str_repeat('•', 10);
        }

        return $values;
    }

    private function getAuditableName($type, $id, $oldValues = null, $newValues = null)
    {
        if ($type === Image::class) {
            return $oldValues['product_name'] ?? $newValues['product_name'] ?? 'Unknown Product Name';
        }

        $models = [
            'App\\Models\\Products' => Products::class,
            'App\\Models\\Warehouse' => Warehouse::class,
            'App\\Models\\Categorie' => Categorie::class,
            'App\\Models\\SubCategories' => SubCategories::class,
            'App\\Models\\Company' => Company::class,
        ];

        if (isset($models[$type])) {
            $model = $models[$type]::find($id);
            return $model ? $model->name : 'Unknown Name';
        }

        return 'Unknown Name';
    }
    private function enrichAuditData($audit)
    {
        if ($audit->auditable_type === ProductStock::class) {
            $productStock = ProductStock::with(['product', 'warehouse'])->find($audit->auditable_id);

            // Trabajar con copias de old_values y new_values
            $oldValues = $audit->old_values;
            $newValues = $audit->new_values;

            $oldValues['product_name'] = $oldValues['product_name'] ?? $productStock->product->name ?? 'Unknown Product';
            $oldValues['warehouse_name'] = $oldValues['warehouse_name'] ?? $productStock->warehouse->name ?? 'Unknown Warehouse';

            $newValues['product_name'] = $newValues['product_name'] ?? $productStock->product->name ?? 'Unknown Product';
            $newValues['warehouse_name'] = $newValues['warehouse_name'] ?? $productStock->warehouse->name ?? 'Unknown Warehouse';

            $audit->setAttribute('transformed_old_values', $oldValues);
            $audit->setAttribute('transformed_new_values', $newValues);
        }

        return $audit;
    }
}
