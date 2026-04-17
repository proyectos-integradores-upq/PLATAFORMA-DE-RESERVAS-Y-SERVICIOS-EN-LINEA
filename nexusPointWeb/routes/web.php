<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;

// ─── PÚBLICAS ─────────────────────────────────────────────────────────────────
Route::get('/', fn() => view('login'))->name('login');
Route::post('/api-login', [AuthController::class, 'login']);
Route::get('/logout', [AuthController::class, 'logout'])->name('logout');

// ─── PROTEGIDAS ───────────────────────────────────────────────────────────────
Route::prefix('admin')->name('admin.')->middleware(['check.api.token'])->group(function () {

    // Dashboard
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');

    // Solicitudes
    Route::get('/solicitudes',              [AdminController::class, 'solicitudes'])->name('solicitudes');
    Route::patch('/solicitudes/{id}',       [AdminController::class, 'actualizarSolicitud'])->name('solicitudes.update');

    // Espacios
    Route::get('/espacios',                 [AdminController::class, 'espacios'])->name('espacios');
    Route::get('/espacios/crear',           [AdminController::class, 'espaciosCreate'])->name('espacios.create');
    Route::get('/espacios/{id}/editar',     [AdminController::class, 'espaciosEdit'])->name('espacios.edit');
    Route::post('/espacios',                [AdminController::class, 'espaciosStore'])->name('espacios.store');
    Route::put('/espacios/{id}',            [AdminController::class, 'espaciosUpdate'])->name('espacios.update');
    Route::delete('/espacios/{id}',         [AdminController::class, 'espaciosDestroy'])->name('espacios.destroy');
    Route::post('/espacios/{id}/equipamiento', [AdminController::class, 'equipamientoStore'])->name('espacios.equipamiento.store');
    Route::delete('/espacios/{id}/equipamiento/{idEquip}', [AdminController::class, 'equipamientoDestroy'])->name('espacios.equipamiento.destroy');
    Route::get('/ping-api', [AdminController::class, 'pingApi'])->name('ping-api');

    // Usuarios
    Route::get('/usuarios',                 [AdminController::class, 'usuarios'])->name('usuarios');
    Route::get('/usuarios/crear',           [AdminController::class, 'usuariosCreate'])->name('usuarios.create');
    Route::get('/usuarios/{id}/editar',     [AdminController::class, 'usuariosEdit'])->name('usuarios.edit');
    Route::post('/usuarios',                [AdminController::class, 'usuariosStore'])->name('usuarios.store');
    Route::put('/usuarios/{id}',            [AdminController::class, 'usuariosUpdate'])->name('usuarios.update');
    Route::delete('/usuarios/{id}',         [AdminController::class, 'usuariosDestroy'])->name('usuarios.destroy');

    // Reportes
    Route::get('/reportes',                 [AdminController::class, 'reportes'])->name('reportes');

    // Perfil
    Route::get('/perfil',                   [AdminController::class, 'perfil'])->name('perfil');
    Route::get('/perfil/editar',            [AdminController::class, 'perfilEdit'])->name('perfil.edit');
    Route::put('/perfil',                   [AdminController::class, 'perfilUpdate'])->name('perfil.update');
});