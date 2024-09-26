<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('roles')->insert([
            ['name' => 'Admin', 'description' => 'El administrador tiene permisos completos para gestionar todos los aspectos del sistema, incluyendo la creación y eliminación de usuarios, la gestión de productos y categorías, y la supervisión de pedidos y pagos, así como la gestión de la información de la empresa.'],
            ['name' => 'Usuario', 'description' => 'El usuario regular puede navegar por el catálogo de productos, realizar compras, dejar reseñas y gestionar su propio perfil y direcciones de envío.'],
            ['name' => 'Cajero', 'description' => 'El cajero puede gestionar el procesamiento de pedidos, registrar pagos y actualizar el estado de los pedidos. Este rol es ideal para empleados que manejan las ventas y la atención al cliente en un entorno físico.'],
            ['name' => 'Gerente de Inventario', 'description' => 'Este rol se encarga de supervisar y gestionar el inventario, incluyendo el registro de movimientos de productos entre almacenes y la actualización de las cantidades en stock.'],
            ['name' => 'Soporte Técnico', 'description' => 'El personal de soporte técnico puede ayudar a los usuarios con problemas técnicos, gestionar solicitudes de ayuda y asegurarse de que el sistema funcione sin problemas.'],
            ['name' => 'Marketing', 'description' => 'Los usuarios en este rol pueden gestionar las campañas de marketing, promociones y descuentos, así como analizar datos de ventas y comportamiento de los usuarios para mejorar las estrategias de marketing.']
        ]);
    }
}
