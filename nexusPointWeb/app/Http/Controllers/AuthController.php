<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        try {
            $response = Http::timeout(120)->post(env('NEXUSPOINT_API_URL') . '/auth/login', [
                'correo'      => $request->email,
                'contrasenia' => $request->password,
            ]);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            return response()->json([
                'success' => false,
                'message' => 'El servidor está iniciando, espera unos segundos e intenta de nuevo.',
            ], 503);
        }

        if ($response->successful()) {
            $data  = $response->json();
            $token = $data['access_token'];

            Session::put('api_token', $token);

            $resMe = Http::timeout(60)->get(env('NEXUSPOINT_API_URL') . '/auth/me', [
                'token' => $token,
            ]);

            if ($resMe->successful()) {
                Session::put('user_data', $resMe->json());
            }

            return response()->json([
                'success'  => true,
                'redirect' => route('admin.dashboard'),
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $response->json()['detail'] ?? 'Credenciales incorrectas',
        ], 401);
    }

    public function logout()
    {
        Session::forget(['api_token', 'user_data']);
        return redirect()->route('login');
    }
}