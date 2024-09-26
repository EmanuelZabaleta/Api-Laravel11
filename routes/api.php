<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    //Rutas publicas
    Route::post('register', 'Api\V1\AuthController@register');
    Route::post('login', 'Api\V1\AuthController@login')->middleware('throttle:10,1');
    //Rutas compañia publicas
    Route::resource('company', 'Api\V1\CompaniesController');
    //Rutas producto publicas
    Route::resource('products', 'Api\V1\ProductsController');
    //Rutas stock publicas
    Route::resource('stock', 'Api\V1\ProductsStockController');


    Route::middleware(['auth:sanctum'])->group(function () {
        Route::post('logout', 'Api\V1\AuthController@logout');

        Route::middleware(['rol:1,5'])->group(function () { // Admin
            //Rutas roles
            Route::resource('roles', 'Api\V1\RolesController');
            Route::post('roles/{id}', 'Api\V1\RolesController@update');
            Route::delete('roles/{id}', 'Api\V1\RolesController@destroy');
            //Rutas usuarios
            Route::resource('users', 'Api\V1\UsersController');
            Route::post('users/{id}', 'Api\V1\UsersController@update');
            Route::delete('users/{id}', 'Api\V1\UsersController@destroy');
            //ruta compañia
            Route::post('company/{id}', 'Api\V1\CompaniesController@update');
            //Rutas Almacen
            Route::resource('warehouse', 'Api\V1\WarehouseController');
            Route::post('warehouse/{id}', 'Api\V1\WarehouseController@update');
            Route::delete('warehouse/{id}', 'Api\V1\WarehouseController@destroy');
            Route::get('warehouse/{id}/products', 'Api\V1\WarehouseController@products');
            Route::post('warehouse/{id}/move', 'Api\V1\WarehouseController@moveProduct');
            //Rutas categorias
            Route::resource('categories', 'Api\V1\CategoriesController');
            Route::post('categories/{id}', 'Api\V1\CategoriesController@update');
            Route::delete('categories/{id}', 'Api\V1\CategoriesController@destroy');
            Route::get('categories/{id}/product', 'Api\V1\CategoriesController@articulosPorCategoria');
            //Rutas subcategorias
            Route::resource('subcategories', 'Api\V1\SubCategoriesController');
            Route::post('subcategories/{id}', 'Api\V1\SubCategoriesController@update');
            Route::delete('subcategories/{id}', 'Api\V1\SubCategoriesController@destroy');
            Route::get('subcategories/{id}/product', 'Api\V1\SubCategoriesController@articulosPorCategoria');
            //Rutas productos
            Route::post('products/{id}', 'Api\V1\ProductsController@update');
            Route::delete('products/{id}', 'Api\V1\ProductsController@destroy');
            // Ruta para subir imágenes
            Route::post('products/{id}/images', 'Api\V1\ProductImagesController@store');
            Route::get('products/{id}/images', 'Api\V1\ProductImagesController@show');
            Route::delete('products/{id}/images/{imageId}', 'Api\V1\ProductImagesController@destroy');
            //Rutas stockproductos
            Route::post('stock/{product_id}', 'Api\V1\ProductsStockController@update');
            Route::delete('stock/{id}', 'Api\V1\ProductsStockController@destroy');
            //Ruta Auditoria
            Route::resource('audits', 'Api\V1\AuditController');
            //Rutas cajas
            Route::post('/cash-registers/open', 'Api\V1\CashRegisterController@openRegister');
            Route::post('/cash-registers/{cashRegister}/close', 'Api\V1\CashRegisterController@closeRegister');
            Route::get('/cash-registers/history', 'Api\V1\CashRegisterController@registerHistory');
            //Rutas clientes
            Route::resource('customers', 'Api\V1\CustomersController')->except(['show']);
            Route::post('customers/{id}', 'Api\V1\CustomersController@update');
            Route::delete('customers/{id}', 'Api\V1\CustomersController@destroy');
            Route::delete('customers/{id}/force', 'Api\V1\CustomersController@forceDelete');
            Route::put('customers/{id}/restore', 'Api\V1\CustomersController@restore');
            Route::get('customers/trashed', 'Api\V1\CustomersController@trashedAccounts');
        });
    });
});
