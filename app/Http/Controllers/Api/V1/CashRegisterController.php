<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\CashRegister;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class CashRegisterController extends Controller
{
    public function openRegister(Request $request)
    {
        // Verificar si el usuario tiene una caja abierta
        $openRegister = CashRegister::where('user_id', $request->user()->id)
            ->whereNull('closed_at')
            ->first();

        if ($openRegister) {
            return response()->json(['message' => 'You already have an open register. Please close it before opening a new one.'], 400);
        }

        // Crear nueva caja
        $cashRegister = CashRegister::create([
            'user_id' => $request->user()->id,
            'opened_at' => now(),
            'initial_balance' => $request->initial_balance,
        ]);

        return response()->json($cashRegister, 201);
    }

    public function closeRegister(Request $request, $id)
    {
        try {
            $cashRegister = CashRegister::find($id);

            if (!$cashRegister) {
                return response()->json(['message' => 'Cash register not found.'], 404);
            }

            if ($cashRegister->user_id !== $request->user()->id || $cashRegister->closed_at) {
                return response()->json(['message' => 'Unauthorized or the register is already closed.'], 403);
            }

            // Calcula la diferencia
            $difference = $request->input('final_balance') - $cashRegister->initial_balance;

            $cashRegister->update([
                'closed_at' => now(),
                'final_balance' => $request->input('final_balance'),
                'difference' => $difference,
                'observations' => $request->input('observations', ''),
            ]);

            return ApiResponse::success('Caja cerrada exitosamente', 200, $cashRegister);
        } catch (ModelNotFoundException $e) {
            return ApiResponse::error('Caja no encontrada', 404);
        } catch (ValidationException $e) {
            $errors = $e->errors();
            return ApiResponse::error('Error: ' . $e->getMessage(), 422, $errors);
        }
    }


    public function registerHistory(Request $request)
    {
        try {
            // Obtener todas las cajas abiertas del usuario (donde closed_at es null)
            $openRegisters = CashRegister::where('user_id', $request->user()->id)
                ->whereNull('closed_at')
                ->orderBy('opened_at', 'desc')
                ->get();

            // Obtener todas las cajas cerradas del usuario
            $closedRegisters = CashRegister::where('user_id', $request->user()->id)
                ->whereNotNull('closed_at')
                ->orderBy('opened_at', 'desc')
                ->get();

            // Devolver ambas listas en la respuesta
            return ApiResponse::success('Historial de cajas', 200, [
                'open_registers' => $openRegisters,
                'closed_registers' => $closedRegisters,
            ]);
        } catch (Exception $e) {
            return ApiResponse::error('Error al obtener el historial de cajas: ' . $e->getMessage(), 500);
        }
    }
}
