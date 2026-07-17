<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\AdminController;

// Página de inicio
Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('tickets.index');
    }
    return view('welcome');
})->name('home');

// Autenticación
Route::get('/login',    [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login',   [AuthController::class, 'login']);
Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register');
Route::post('/register',[AuthController::class, 'register']);

// Tickets de invitados (sin autenticación)
Route::get('/tickets/guest',         [TicketController::class, 'guestCreate'])->name('tickets.guest.create');
Route::post('/tickets/guest',        [TicketController::class, 'guestStore'])->name('tickets.guest.store');
Route::get('/tickets/guest/{token}', [TicketController::class, 'guestShow'])->name('tickets.guest.show');

// AJAX público — catálogo (para formularios de invitados)
Route::get('/api/categorias/{categoria}/subcategorias', [CategoryController::class, 'getSubcategorias'])->name('api.subcategorias');
Route::get('/api/subcategorias/{subcategoria}/tipos',   [CategoryController::class, 'getTipos'])->name('api.tipos');

// Rutas protegidas
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // ── Tickets ──────────────────────────────────────────────────────
    Route::resource('tickets', TicketController::class);
    Route::post('/tickets/{ticket}/comment',     [TicketController::class, 'addComment'])->name('tickets.addComment');
    Route::put('/tickets/{ticket}/status',       [TicketController::class, 'updateStatus'])->name('tickets.updateStatus');
    Route::put('/tickets/{ticket}/priority',     [TicketController::class, 'updatePriority'])->name('tickets.updatePriority');
    Route::put('/tickets/{ticket}/classify',     [TicketController::class, 'updateClassification'])->name('tickets.updateClassification');
    Route::post('/tickets/{ticket}/assign',      [TicketController::class, 'assignTo'])->name('tickets.assignTo');
    Route::post('/tickets/{ticket}/self-assign', [TicketController::class, 'selfAssign'])->name('tickets.selfAssign');
    Route::post('/tickets/{ticket}/forward',     [TicketController::class, 'forward'])->name('tickets.forward');
    Route::post('/tickets/{ticket}/close',       [TicketController::class, 'close'])->name('tickets.close');
    Route::get('/tickets/{ticket}/panel',        [TicketController::class, 'panel'])->name('tickets.panel');

    // ── Notificaciones ────────────────────────────────────────────────
    Route::get('/notifications',                     [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/recent',              [NotificationController::class, 'recent'])->name('notifications.recent');
    Route::get('/notifications/count',               [NotificationController::class, 'count'])->name('notifications.count');
    Route::post('/notifications/{notificacion}/read',[NotificationController::class, 'markRead'])->name('notifications.read');
    Route::post('/notifications/read-all',           [NotificationController::class, 'markAllRead'])->name('notifications.readAll');

    // ── Admin ─────────────────────────────────────────────────────────
    Route::middleware('admin')->prefix('admin')->group(function () {
        Route::get('/dashboard',            [AdminController::class, 'dashboard'])->name('admin.dashboard');

        // Usuarios
        Route::get('/users',                [AdminController::class, 'users'])->name('admin.users');
        Route::get('/users/create',         [AdminController::class, 'createUser'])->name('admin.users.create');
        Route::post('/users',               [AdminController::class, 'storeUser'])->name('admin.users.store');
        Route::get('/users/{user}/edit',    [AdminController::class, 'editUser'])->name('admin.users.edit');
        Route::put('/users/{user}',         [AdminController::class, 'updateUser'])->name('admin.users.update');

        // Departamentos
        Route::get('/departments',          [AdminController::class, 'departments'])->name('admin.departments');
        Route::get('/departments/create',   [AdminController::class, 'createDepartment'])->name('admin.departments.create');
        Route::post('/departments',         [AdminController::class, 'storeDepartment'])->name('admin.departments.store');

        // Categorías / Subcategorías / Tipos de Incidente
        Route::get('/categories',                                             [CategoryController::class, 'index'])->name('admin.categories');
        Route::post('/categories',                                            [CategoryController::class, 'store'])->name('admin.categories.store');
        Route::put('/categories/{categoria}',                                 [CategoryController::class, 'update'])->name('admin.categories.update');
        Route::delete('/categories/{categoria}',                              [CategoryController::class, 'destroy'])->name('admin.categories.destroy');
        Route::post('/categories/{categoria}/subcategorias',                  [CategoryController::class, 'storeSubcategoria'])->name('admin.subcategorias.store');
        Route::put('/subcategorias/{subcategoria}',                           [CategoryController::class, 'updateSubcategoria'])->name('admin.subcategorias.update');
        Route::delete('/subcategorias/{subcategoria}',                        [CategoryController::class, 'destroySubcategoria'])->name('admin.subcategorias.destroy');
        Route::post('/subcategorias/{subcategoria}/tipos',                    [CategoryController::class, 'storeTipo'])->name('admin.tipos.store');
        Route::delete('/tipos/{tipo}',                                        [CategoryController::class, 'destroyTipo'])->name('admin.tipos.destroy');

        // SLA
        Route::get('/sla',                  [CategoryController::class, 'sla'])->name('admin.sla');
        Route::put('/sla',                  [CategoryController::class, 'updateSla'])->name('admin.sla.update');

        // Reportes
        Route::get('/reports',              [ReportController::class, 'index'])->name('admin.reports');
        Route::get('/reports/export',       [ReportController::class, 'export'])->name('admin.reports.export');

        // Auditoría
        Route::get('/audit',                [AdminController::class, 'audit'])->name('admin.audit');
    });
});
