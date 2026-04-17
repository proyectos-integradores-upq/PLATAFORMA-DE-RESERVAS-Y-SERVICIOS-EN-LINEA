<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;

class AdminController extends Controller
{
    private function apiUrl(): string
    {
        return rtrim(env('NEXUSPOINT_API_URL', 'https://nexuspoint-api.onrender.com'), '/');
    }

    private function token(): string
    {
        return Session::get('api_token', '');
    }

    private function headers(): array
    {
        return ['Authorization' => "Bearer {$this->token()}", 'Accept' => 'application/json'];
    }

    // ─── DASHBOARD ────────────────────────────────────────────────────────────
    public function dashboard()
    {
        $headers = $this->headers();

        $reservaciones = Http::withHeaders($headers)->timeout(60)->get($this->apiUrl() . '/reservaciones/')->json() ?? [];
        $espacios      = Http::withHeaders($headers)->timeout(60)->get($this->apiUrl() . '/espacios/')->json() ?? [];
        $edificios     = Http::withHeaders($headers)->timeout(60)->get($this->apiUrl() . '/espacios/catalogos/edificios')->json() ?? [];

        $mapPisos     = [];
        $mapEdificios = [];

        foreach ($edificios as $edificio) {
            $idEdificio               = $edificio['id_edificio'];
            $mapEdificios[$idEdificio] = $edificio['nombre_edificio'];

            $pisos = Http::withHeaders($headers)->timeout(60)->get($this->apiUrl() . "/espacios/catalogos/pisos/{$idEdificio}")->json() ?? [];
            foreach ($pisos as $piso) {
                $mapPisos[$piso['id_piso']] = $idEdificio;
            }
        }

        $mapEspacioPiso = [];
        foreach ($espacios as $espacio) {
            $idEspacio = $espacio['id_espacio'] ?? null;
            $idPiso    = $espacio['id_piso'] ?? null;
            if (is_array($idPiso)) $idPiso = $idPiso['id_piso'] ?? null;
            if ($idEspacio && $idPiso) $mapEspacioPiso[$idEspacio] = $idPiso;
        }

        $totalReservaciones = count($reservaciones);
        $espaciosActivos    = count(array_filter($espacios, fn($e) => ($e['id_estado'] ?? $e['id_estado_espacio'] ?? 0) == 1));
        $aprobadas          = count(array_filter($reservaciones, fn($r) => ($r['id_estado_reservacion'] ?? 0) == 2));
        $tasaAprobacion     = $totalReservaciones > 0 ? round(($aprobadas / $totalReservaciones) * 100) : 0;

        $dias     = ['Lun' => 0, 'Mar' => 0, 'Mié' => 0, 'Jue' => 0, 'Vie' => 0, 'Sáb' => 0, 'Dom' => 0];
        $mapaDias = ['Monday' => 'Lun', 'Tuesday' => 'Mar', 'Wednesday' => 'Mié', 'Thursday' => 'Jue', 'Friday' => 'Vie', 'Saturday' => 'Sáb', 'Sunday' => 'Dom'];

        foreach ($reservaciones as $r) {
            $fecha = $r['fecha_solicitud'] ?? null;
            if ($fecha) {
                $corto = $mapaDias[date('l', strtotime($fecha))] ?? null;
                if ($corto) $dias[$corto]++;
            }
        }

        $graficaEdificios = [];
        $fuente = $totalReservaciones > 0 ? $reservaciones : $espacios;
        foreach ($fuente as $item) {
            $idEspacio      = $item['id_espacio'] ?? null;
            $idPiso         = $totalReservaciones > 0 ? ($mapEspacioPiso[$idEspacio] ?? null) : ($item['id_piso'] ?? null);
            if (is_array($idPiso)) $idPiso = $idPiso['id_piso'] ?? null;
            $idEdificio     = $mapPisos[$idPiso] ?? null;
            $nombreEdificio = $mapEdificios[$idEdificio] ?? null;
            if ($nombreEdificio) $graficaEdificios[$nombreEdificio] = ($graficaEdificios[$nombreEdificio] ?? 0) + 1;
        }

        return view('admin.dashboard', [
            'userData'           => Session::get('user_data', []),
            'totalSolicitudes'   => $totalReservaciones,
            'espaciosActivos'    => $espaciosActivos,
            'tasaAprobacion'     => $tasaAprobacion,
            'diasSemana'         => $dias,
            'ocupacionEdificios' => $graficaEdificios,
        ]);
    }

    // ─── SOLICITUDES ──────────────────────────────────────────────────────────
    public function solicitudes()
    {
        $headers = $this->headers();
        
        // 1. Obtener datos
        $resReservas = Http::withHeaders($headers)->timeout(60)->get($this->apiUrl() . '/reservaciones/');
        $resUsuarios = Http::withHeaders($headers)->timeout(60)->get($this->apiUrl() . '/usuarios/');
        $resEspacios = Http::withHeaders($headers)->timeout(60)->get($this->apiUrl() . '/espacios/');

        // Inicializar siempre con las llaves vacías para evitar el error "Undefined array key"
        $solicitudesProcesadas = [
            'pendientes'  => [],
            'aprobadas'   => [],
            'rechazadas'  => [],
            'finalizadas' => [],
            'canceladas'  => [],
        ];

        if ($resReservas->successful()) {
            $usuariosMap = collect($resUsuarios->json() ?? [])->pluck('nombre', 'id_usuario');
            $matriculasMap = collect($resUsuarios->json() ?? [])->pluck('matricula', 'id_usuario');
            $espaciosMap = collect($resEspacios->json() ?? [])->pluck('nombre_espacio', 'id_espacio');

            $solicitudesRaw = $resReservas->json();

            // Mapeo de IDs de estado de tu base de datos a las llaves del array
            $estadoMap = [
                1 => 'pendientes',
                2 => 'aprobadas',
                3 => 'rechazadas',
                4 => 'finalizadas',
                5 => 'canceladas'
            ];

            // IMPORTANTE: Si la API devuelve una lista plana, iteramos y agrupamos manualmente
            // Si la API ya viene agrupada, ajustamos la lógica:
            $dataAIterar = isset($solicitudesRaw[0]) ? $solicitudesRaw : []; // Asumimos lista plana si el primer elemento es numérico

            foreach ($dataAIterar as $s) {
                $item = (array) $s;
                $idEstado = $item['id_estado_reservacion'] ?? 0;
                $key = $estadoMap[$idEstado] ?? null;

                if ($key) {
                    $idU = $item['id_usuario'] ?? null;
                    $idE = $item['id_espacio'] ?? null;

                    $item['nombre_usuario'] = $usuariosMap[$idU] ?? 'Usuario Desconocido';
                    $item['matricula']      = $matriculasMap[$idU] ?? '—';
                    $item['nombre_espacio'] = $espaciosMap[$idE] ?? 'Espacio Desconocido';

                    $solicitudesProcesadas[$key][] = $item;
                }
            }
        }

        return view('admin.solicitudes', [
            'userData'    => \Illuminate\Support\Facades\Session::get('user_data', []),
            'solicitudes' => $solicitudesProcesadas,
        ]);
    }

    public function actualizarSolicitud(Request $request, $id)
    {
        $idEstado = $request->input('estatus') === 'Aprobado' ? 2 : 3;

        $userData = Session::get('user_data', []);
        $idGestor = $userData['id_usuario'] ?? $userData['id'] ?? null;

        $payload = [
            'id_reservacion'        => (int) $id,
            'id_usuario_gestor'     => (int) $idGestor,
            'id_estado_reservacion' => $idEstado,
            'observaciones'         => $request->input('observaciones', 'Gestionado desde panel de administración'),
        ];

        // LOG TEMPORAL — ver qué se envía y qué responde la API
        \Log::info('=== GESTIONAR SOLICITUD ===');
        \Log::info('Payload enviado:', $payload);

        $res = Http::withHeaders($this->headers())
                ->timeout(60)
                ->post($this->apiUrl() . '/reservaciones/gestionar', $payload);

        \Log::info('Status HTTP:', ['status' => $res->status()]);
        \Log::info('Respuesta API:', ['body' => $res->body()]);

        if ($res->successful()) {
            return response()->json(['success' => true]);
        }

        return response()->json([
            'success' => false,
            'message' => $res->json()['detail'] ?? 'Error al procesar la solicitudd',
            // TEMPORAL: exponer detalles completos para debug
            'debug'   => [
                'status'  => $res->status(),
                'payload' => $payload,
                'raw'     => $res->body(),
            ]
        ], 422);
    }

    // ─── CATÁLOGOS DE ESPACIOS (privado) ──────────────────────────────────────
    private function catalogosEspacios(): array
    {
        $headers  = $this->headers();
        $edificios = Http::withHeaders($headers)->timeout(60)->get($this->apiUrl() . '/espacios/catalogos/edificios')->json() ?? [];

        $pisos             = [];
        $mapaPisosEdificio = [];

        foreach ($edificios as $edificio) {
            $idEdificio     = $edificio['id_edificio'];
            $nombreEdificio = $edificio['nombre_edificio'];

            $resPisos = Http::withHeaders($headers)->timeout(60)->get($this->apiUrl() . "/espacios/catalogos/pisos/{$idEdificio}");
            if ($resPisos->successful()) {
                foreach ($resPisos->json() as $piso) {
                    $idPiso     = $piso['id_piso'];
                    $nombrePiso = $piso['numero_piso'] ?? "Piso {$idPiso}";
                    $pisos[]    = ['id_piso' => $idPiso, 'nombre_piso' => "{$nombreEdificio} - {$nombrePiso}"];
                    $mapaPisosEdificio[$idPiso] = $nombreEdificio;
                }
            }
        }

        $resTipos = Http::withHeaders($headers)->timeout(60)->get($this->apiUrl() . '/espacios/catalogos/tipos');
        $tipos    = [];
        if ($resTipos->successful()) {
            foreach ($resTipos->json() as $t) {
                $tipos[] = [
                    'id_tipo_espacio'     => $t['id_tipo_espacio'] ?? $t['id'] ?? null,
                    'nombre_tipo_espacio' => $t['nombre_tipo_espacio'] ?? $t['nombre'] ?? '—',
                ];
            }
        }

        $resEquipos        = Http::withHeaders($headers)->timeout(60)->get($this->apiUrl() . '/espacios/catalogos/equipamiento');
        $tiposEquipamiento = [];
        if ($resEquipos->successful()) {
            foreach ($resEquipos->json() as $eq) {
                $tiposEquipamiento[] = [
                    'id_tipo_equipamiento'     => $eq['id_tipo_equipamiento'],
                    'nombre_tipo_equipamiento' => $eq['nombre_tipo_equipamiento'],
                ];
            }
        }

        return compact('tipos', 'pisos', 'tiposEquipamiento', 'mapaPisosEdificio');
    }

    // ─── ESPACIOS ─────────────────────────────────────────────────────────────
    public function espacios()
    {
        $catalogos         = $this->catalogosEspacios();
        $mapaTipos         = collect($catalogos['tipos'])->pluck('nombre_tipo_espacio', 'id_tipo_espacio')->toArray();
        $mapaPisosEdificio = $catalogos['mapaPisosEdificio'];

        $espaciosRaw = Http::withHeaders($this->headers())->timeout(60)->get($this->apiUrl() . '/espacios')->json() ?? [];

        $espacios = collect($espaciosRaw)->map(function ($e) use ($mapaPisosEdificio, $mapaTipos) {
            $idPiso = $e['id_piso'] ?? null;
            if (is_array($idPiso)) $idPiso = $idPiso['id_piso'] ?? null;

            return [
                'id_espacio' => $e['id_espacio'],
                'nombre'     => $e['nombre_espacio'],
                'capacidad'  => $e['capacidad'],
                'tipo'       => $mapaTipos[$e['id_tipo_espacio'] ?? null] ?? '—',
                'estatus'    => $this->mapEstadoEspacio($e['id_estado_espacio'] ?? null),
                'edificio'   => $mapaPisosEdificio[$idPiso] ?? '—',
            ];
        });

        return view('admin.espacios', [
            'userData' => Session::get('user_data', []),
            'espacios' => $espacios,
        ]);
    }

    public function espaciosCreate()
    {
        $catalogos = $this->catalogosEspacios();
        return view('admin.espacios-form', [
            'userData'             => Session::get('user_data', []),
            'espacio'              => null,
            'equipamientoAsignado' => [],
            'tiposEquipamiento'    => $catalogos['tiposEquipamiento'],
            'tipos'                => $catalogos['tipos'],
            'pisos'                => $catalogos['pisos'],
        ]);
    }

    public function espaciosEdit($id)
    {
        $headers = $this->headers();

        $res = Http::withHeaders($headers)->timeout(60)->get($this->apiUrl() . "/espacios/{$id}");
        if (!$res->successful()) {
            return redirect()->route('admin.espacios')->with('error', 'Espacio no encontrado');
        }

        $data = $res->json();

        // La API puede devolver estos campos como objetos anidados o integers simples
        $idTipo   = $data['id_tipo_espacio'] ?? 1;
        $idEstado = $data['id_estado_espacio'] ?? 1;
        $idPiso   = $data['id_piso'] ?? 1;

        if (is_array($idTipo))   $idTipo   = $idTipo['id_tipo_espacio'] ?? 1;
        if (is_array($idEstado)) $idEstado = $idEstado['id_estado_espacio'] ?? 1;
        if (is_array($idPiso))   $idPiso   = $idPiso['id_piso'] ?? 1;

        $espacio = [
            'id_espacio'        => $data['id_espacio'],
            'nombre'            => $data['nombre_espacio'] ?? '',
            'codigo_espacio'    => $data['codigo_espacio'] ?? '',
            'capacidad'         => $data['capacidad'] ?? 0,
            'id_tipo_espacio'   => (int) $idTipo,
            'id_estado_espacio' => (int) $idEstado,
            'id_piso'           => (int) $idPiso,
        ];

        $resEquip             = Http::withHeaders($headers)->timeout(60)->get($this->apiUrl() . "/espacios/{$id}/equipamiento");
        $equipamientoAsignado = $resEquip->successful() ? $resEquip->json() : [];

        $catalogos = $this->catalogosEspacios();

        return view('admin.espacios-form', [
            'userData'             => Session::get('user_data', []),
            'espacio'              => $espacio,
            'equipamientoAsignado' => $equipamientoAsignado,
            'tiposEquipamiento'    => $catalogos['tiposEquipamiento'],
            'tipos'                => $catalogos['tipos'],
            'pisos'                => $catalogos['pisos'],
        ]);
    }

    public function espaciosStore(Request $request)
{
    $payload = [
        'codigo_espacio'    => $request->input('codigo_espacio'),
        'nombre_espacio'    => $request->input('nombre'),
        'capacidad'         => (int) $request->input('capacidad'),
        'id_tipo_espacio'   => (int) $request->input('id_tipo_espacio'),
        'id_estado_espacio' => (int) $request->input('id_estado_espacio'),
        'id_piso'           => (int) $request->input('id_piso'),
    ];

    try {
        $res = Http::withHeaders($this->headers())
                   ->asJson()
                   ->timeout(60)
                   ->post($this->apiUrl() . '/espacios', $payload);

        if ($res->successful()) {
            return response()->json(['success' => true, 'id_espacio' => $res->json()['id_espacio'] ?? null]);
        }

        return response()->json([
            'success'  => false,
            'message'  => $res->json()['detail'] ?? 'Error al crear',
            'status'   => $res->status(),
            'body_raw' => $res->body(),  // 👈 temporal
        ], 422);

    } catch (\Exception $e) {
        return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
    }
}
    public function espaciosUpdate(Request $request, $id)
{
    $payload = [
        'nombre_espacio'    => $request->input('nombre'),
        'capacidad'         => (int) $request->input('capacidad'),
        'id_tipo_espacio'   => (int) $request->input('id_tipo_espacio'),
        'id_estado_espacio' => (int) $request->input('id_estado_espacio'),
        'id_piso'           => (int) $request->input('id_piso'),
    ];

    try {
        $res = Http::withHeaders($this->headers())->asJson()->timeout(60)->put($this->apiUrl() . "/espacios/{$id}", $payload);
        if ($res->successful()) return response()->json(['success' => true]);
        return response()->json([
            'success'   => false,
            'message'   => $res->json()['detail'] ?? 'Error al actualizar',
            'payload'   => $payload,   // 👈 temporal
            'status'    => $res->status(),
            'body_raw'  => $res->body(),
        ], 422);
    } catch (\Exception $e) {
        return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
    }
}

    public function espaciosDestroy($id)
    {
        try {
            $res = Http::withHeaders($this->headers())->timeout(60)->delete($this->apiUrl() . "/espacios/{$id}");
            if ($res->successful()) return response()->json(['success' => true]);
            return response()->json(['success' => false, 'message' => $res->json()['detail'] ?? 'Error al eliminar'], 422);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ─── EQUIPAMIENTO DE ESPACIOS ─────────────────────────────────────────────
    public function equipamientoStore(Request $request, $id)
    {
        try {
            $res = Http::withHeaders($this->headers())
                       ->asJson()
                       ->timeout(60)
                       ->post($this->apiUrl() . "/espacios/{$id}/equipamiento", [
                           'id_tipo_equipamiento' => (int) $request->input('id_tipo_equipamiento'),
                       ]);

            if ($res->successful()) {
                return response()->json(['success' => true]);
            }

            $detalle = $res->json()['detail'] ?? null;
            $mensaje = is_array($detalle)
                ? collect($detalle)->map(fn($e) => $e['msg'] ?? '')->implode(', ')
                : ($detalle ?? 'Error al agregar equipamiento');

            return response()->json(['success' => false, 'message' => $mensaje], 200);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 200);
        }
    }

    public function equipamientoDestroy($id, $idEquip)
    {
        try {
            $res = Http::withHeaders($this->headers())->timeout(60)->delete($this->apiUrl() . "/espacios/{$id}/equipamiento/{$idEquip}");
            if ($res->successful()) return response()->json(['success' => true]);
            return response()->json(['success' => false, 'message' => $res->json()['detail'] ?? 'Error al eliminar'], 422);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ─── USUARIOS ─────────────────────────────────────────────────────────────
    public function usuarios()
    {
        $resUsuarios = Http::withHeaders($this->headers())->timeout(60)->get($this->apiUrl() . '/usuarios/');
        $usuarios    = $resUsuarios->successful() ? $resUsuarios->json() : [];

        $resCarreras = Http::timeout(60)->get($this->apiUrl() . '/usuarios/catalogos/carreras');
        $carreras    = collect($resCarreras->json())->pluck('nombre_carrera', 'id_carrera');

        $usuariosConCarrera = collect($usuarios)->map(function ($u) use ($carreras) {
            $u['nombre_carrera'] = $carreras[$u['id_carrera']] ?? 'Sin carrera';
            return $u;
        });

        return view('admin.usuarios', [
            'userData' => Session::get('user_data', []),
            'usuarios' => $usuariosConCarrera,
        ]);
    }

    public function usuariosCreate()
    {
        $carreras = Http::timeout(60)->get($this->apiUrl() . '/usuarios/catalogos/carreras')->json() ?? [];
        $roles    = Http::timeout(60)->get($this->apiUrl() . '/usuarios/catalogos/roles')->json() ?? [];
        return view('admin.usuarios-form', [
            'userData' => Session::get('user_data', []),
            'usuario'  => null,
            'carreras' => $carreras,
            'roles'    => $roles,
        ]);
    }

    public function usuariosEdit($id)
    {
        $res      = Http::withHeaders($this->headers())->timeout(60)->get($this->apiUrl() . "/usuarios/{$id}");
        $usuario  = $res->successful() ? $res->json() : null;
        $carreras = Http::timeout(60)->get($this->apiUrl() . '/usuarios/catalogos/carreras')->json() ?? [];
        $roles    = Http::timeout(60)->get($this->apiUrl() . '/usuarios/catalogos/roles')->json() ?? [];
        return view('admin.usuarios-form', [
            'userData' => Session::get('user_data', []),
            'usuario'  => $usuario,
            'carreras' => $carreras,
            'roles'    => $roles,
        ]);
    }

    public function usuariosStore(Request $request)
    {
        $res = Http::asJson()->timeout(60)->post($this->apiUrl() . '/auth/registro', $request->all());
        if ($res->successful()) return response()->json(['success' => true]);
        return response()->json(['success' => false, 'message' => $res->json()['detail'] ?? 'Error al registrar'], 422);
    }

    public function usuariosUpdate(Request $request, $id)
    {
        $datos = [
            'matricula'    => $request->input('matricula'),
            'correo'       => $request->input('correo'),
            'nombre'       => $request->input('nombre'),
            'apellido_p'   => $request->input('apellido_p'),
            'apellido_m'   => $request->input('apellido_m') ?? '',
            'cuatrimestre' => (int) $request->input('cuatrimestre'),
            'id_carrera'   => (int) $request->input('id_carrera'),
            'id_rol'       => (int) $request->input('id_rol'),
        ];

        try {
            $res = Http::withHeaders($this->headers())->asJson()->timeout(60)->put($this->apiUrl() . "/usuarios/{$id}", $datos);
            if ($res->successful()) return response()->json(['success' => true]);
            return response()->json(['success' => false, 'message' => $res->json()['detail'] ?? 'Error al actualizar'], 422);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function usuariosDestroy($id)
    {
        try {
            $res = Http::withHeaders($this->headers())->timeout(60)->delete($this->apiUrl() . "/usuarios/{$id}");
            if ($res->successful()) return response()->json(['success' => true]);
            return response()->json(['success' => false, 'message' => $res->json()['detail'] ?? 'Error al eliminar'], 422);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ─── REPORTES ─────────────────────────────────────────────────────────────
    public function reportes()
    {
        return view('admin.reportes', ['userData' => Session::get('user_data', [])]);
    }

    // ─── PERFIL ───────────────────────────────────────────────────────────────
   public function perfil()
{
    $userData = Session::get('user_data', []);

    // Intentar obtener el id sin importar el nombre del campo
    $id = $userData['id_usuario'] 
       ?? $userData['id'] 
       ?? null;

    if ($id) {
        $res = Http::withHeaders($this->headers())
                   ->timeout(60)
                   ->get($this->apiUrl() . "/usuarios/{$id}");

        if ($res->successful()) {
            $userData = $res->json();
            Session::put('user_data', $userData);
        }
    }

    return view('admin.perfil', ['userData' => $userData]);
}

    public function perfilEdit()
{
    $userData = Session::get('user_data', []);

    $id = $userData['id_usuario'] 
       ?? $userData['id'] 
       ?? null;

    if ($id) {
        $res = Http::withHeaders($this->headers())
                   ->timeout(60)
                   ->get($this->apiUrl() . "/usuarios/{$id}");

        if ($res->successful()) {
            $userData = $res->json();
            Session::put('user_data', $userData);
        }
    }

    return view('admin.perfil-form', [
        'userData' => $userData
    ]);
}

    public function perfilUpdate(Request $request)
{
    $userData = Session::get('user_data', []);

    $id = $userData['id_usuario'] 
       ?? $userData['id'] 
       ?? null;

    if (!$id) {
        return response()->json([
            'success' => false,
            'message' => 'Usuario no identificado'
        ], 400);
    }

    // ── Cambio de contraseña (viene solo el campo 'contrasenia') ──
    if ($request->has('contrasenia')) {
        $contrasenia = $request->input('contrasenia');

        if (!$contrasenia || strlen($contrasenia) < 8) {
            return response()->json(['success' => false, 'message' => 'La contraseña debe tener al menos 8 caracteres.'], 422);
        }

        // Para actualizar contraseña mandamos el payload completo del usuario + nueva contraseña
        $payload = [
            'matricula'    => $userData['matricula']    ?? '',
            'correo'       => $userData['correo']       ?? '',
            'nombre'       => $userData['nombre']       ?? '',
            'apellido_p'   => $userData['apellido_p']   ?? '',
            'apellido_m'   => $userData['apellido_m']   ?? '',
            'cuatrimestre' => (int) ($userData['cuatrimestre'] ?? 1),
            'id_carrera'   => (int) ($userData['id_carrera']   ?? 1),
            'id_rol'       => (int) ($userData['id_rol']       ?? 1),
            'contrasenia'  => $contrasenia,
        ];

        $res = Http::withHeaders($this->headers())->asJson()->timeout(60)
                   ->put($this->apiUrl() . "/usuarios/{$id}", $payload);

                   \Log::info('Cambio contraseña - payload:', $payload);
\Log::info('Cambio contraseña - respuesta:', ['status' => $res->status(), 'body' => $res->body()]);

        if ($res->successful()) {
            return response()->json(['success' => true]);
        }

        $detail = $res->json()['detail'] ?? 'Error al actualizar la contraseña';
        $message = is_array($detail)
            ? collect($detail)->map(fn($e) => $e['msg'] ?? '')->implode(', ')
            : $detail;

        return response()->json(['success' => false, 'message' => $message], 422);
    }

    // ── Actualización de datos personales ──
    $payload = [
        'matricula'    => $request->input('matricula',    $userData['matricula']    ?? ''),
        'correo'       => $request->input('correo',       $userData['correo']       ?? ''),
        'nombre'       => $request->input('nombre',       $userData['nombre']       ?? ''),
        'apellido_p'   => $request->input('apellido_p',   $userData['apellido_p']   ?? ''),
        'apellido_m'   => $request->input('apellido_m',   $userData['apellido_m']   ?? ''),
        'cuatrimestre' => (int) $request->input('cuatrimestre', $userData['cuatrimestre'] ?? 1),
        'id_carrera'   => (int) $request->input('id_carrera',   $userData['id_carrera']   ?? 1),
        'id_rol'       => (int) $request->input('id_rol',       $userData['id_rol']       ?? 1),
    ];

    $res = Http::withHeaders($this->headers())->asJson()->timeout(60)
               ->put($this->apiUrl() . "/usuarios/{$id}", $payload);

    if ($res->successful()) {
        Session::put('user_data', $res->json());
        return response()->json(['success' => true]);
    }

    $detail  = $res->json()['detail'] ?? 'Error al guardar los cambios';
    $message = is_array($detail)
        ? collect($detail)->map(fn($e) => $e['msg'] ?? '')->implode(', ')
        : $detail;

    return response()->json(['success' => false, 'message' => $message], 422);
}

    // ─── PING ─────────────────────────────────────────────────────────────────
    public function pingApi()
    {
        try {
            $res = Http::timeout(60)->get($this->apiUrl() . '/');
            return response()->json(['ok' => $res->successful()]);
        } catch (\Exception $e) {
            return response()->json(['ok' => false]);
        }
    }

    // ─── HELPERS ──────────────────────────────────────────────────────────────
    private function mapEstadoEspacio($id): string
    {
        return [1 => 'Disponible', 2 => 'Mantenimiento', 3 => 'Ocupado'][$id] ?? 'Desconocido';
    }
}