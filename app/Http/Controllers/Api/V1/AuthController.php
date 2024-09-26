<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8'
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password)
        ]);
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()
            ->json(['data' => $user, 'access_token' => $token, 'token_type' => 'Bearer',]);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email|max:255',
            'password' => 'required|string|min:8',
        ]);
        
        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json(['message' => 'Email y/o contraseña son incorrectos'], 401);
        }
        $user = User::where('email', $request['email'])->firstOrFail();
        $token = $user->createToken('auth_token')->plainTextToken;

        // Obtener el company_id de la tabla user_company
        $company_id = DB::table('user_company')
            ->where('user_id', $user->id)
            ->value('company_id');

        // Si no existe en user_company, buscar en companies
        if (!$company_id) {
            $company_id = DB::table('companies')
                ->where('user_id', $user->id)
                ->value('id');
        }
        return response()
            ->json([
                'message' => 'Hola ' . $user->name,
                'accessToken' => $token,
                'token_type' => 'Bearer',
                'user' => $user,
                'company_id' => $company_id,
            ]);
    }

    public function logout(Request $request)
    {
        // Verifica que el usuario esté autenticado
        $user = $request->user();
        if ($user) {
            $user->tokens()->delete();
            return response()->json(['message' => 'Logout successful']);
        }

        return response()->json(['message' => 'Unauthenticated.'], 401);
    }
}
