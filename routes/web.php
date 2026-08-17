<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ArticuloController;
use App\Http\Controllers\AsistenteController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AyudaController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ForgotPasswordController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PriorityRuleController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ManualController;

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

// Recuperación de contraseña (sin autenticación)
Route::get('/forgot-password',         [ForgotPasswordController::class, 'showForgotForm'])->name('password.forgot');
Route::post('/forgot-password',        [ForgotPasswordController::class, 'sendResetLink'])->name('password.forgot.send');
Route::get('/reset-password/{token}',  [ForgotPasswordController::class, 'showResetForm'])->name('password.reset.form');
Route::post('/reset-password',         [ForgotPasswordController::class, 'resetPassword'])->name('password.reset.update');

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

    // Perfil de usuario
    // Base de conocimiento para el usuario (RN-18).
    // 'sugerencias' devuelve JSON y la consulta el formulario de ticket
    // mientras se escribe el asunto.
    Route::get('/ayuda',                        [AyudaController::class, 'index'])->name('ayuda.index');
    Route::get('/ayuda/sugerencias',            [AyudaController::class, 'sugerencias'])->name('ayuda.sugerencias');
    // Asistente sobre la base de conocimiento. Limitado por minuto porque cada
    // consulta ocupa el servidor de modelos durante varios segundos.
    Route::post('/ayuda/asistente',             [AsistenteController::class, 'preguntar'])
        ->middleware('throttle:10,1')->name('ayuda.asistente');
    Route::get('/ayuda/{articulo}',             [AyudaController::class, 'show'])->name('ayuda.show');
    Route::post('/ayuda/{articulo}/util',       [AyudaController::class, 'util'])->name('ayuda.util');
    Route::post('/ayuda/{articulo}/resuelto',   [AyudaController::class, 'evitado'])->name('ayuda.evitado');

    Route::get('/profile',           [ProfileController::class, 'index'])->name('profile.index');
    Route::post('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
    Route::post('/profile/info',     [ProfileController::class, 'updateProfile'])->name('profile.info');
    Route::post('/profile/avatar',   [ProfileController::class, 'uploadAvatar'])->name('profile.avatar');

    // Manuales descargables (Reunión 4)
    Route::get('/manuales',                       [ManualController::class, 'index'])->name('manuales.index');
    Route::get('/manuales/{manual}/download',     [ManualController::class, 'download'])->name('manuales.download');

    // ── Tickets ──────────────────────────────────────────────────────
    Route::get('/tickets/my-stats',  [TicketController::class, 'myStats'])->name('tickets.my-stats');
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
    Route::get('/notifications',                         [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/recent',                  [NotificationController::class, 'recent'])->name('notifications.recent');
    Route::get('/notifications/count',                   [NotificationController::class, 'count'])->name('notifications.count');
    Route::match(['GET','POST'], '/notifications/{notificacion}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::post('/notifications/read-all',               [NotificationController::class, 'markAllRead'])->name('notifications.readAll');

    // ── Admin ─────────────────────────────────────────────────────────
    Route::middleware('admin')->prefix('admin')->group(function () {
        Route::get('/dashboard',  [AdminController::class, 'dashboard'])->name('admin.dashboard');
        Route::get('/gerencial',  [AdminController::class, 'gerencialDashboard'])->name('admin.gerencial');

        // Usuarios
        Route::get('/users',             [AdminController::class, 'users'])->name('admin.users.index');
        Route::get('/users/create',      [AdminController::class, 'createUser'])->name('admin.users.create');
        Route::post('/users',            [AdminController::class, 'storeUser'])->name('admin.users.store');
        Route::get('/users/{user}/edit', [AdminController::class, 'editUser'])->name('admin.users.edit');
        Route::put('/users/{user}',      [AdminController::class, 'updateUser'])->name('admin.users.update');

        // Departamentos
        Route::get('/departments',        [AdminController::class, 'departments'])->name('admin.departments.index');
        Route::get('/departments/create', [AdminController::class, 'createDepartment'])->name('admin.departments.create');
        Route::post('/departments',       [AdminController::class, 'storeDepartment'])->name('admin.departments.store');
        Route::get('/departments/{department}/edit', [AdminController::class, 'editDepartment'])->name('admin.departments.edit');
        Route::put('/departments/{department}', [AdminController::class, 'updateDepartment'])->name('admin.departments.update');
        Route::delete('/departments/{department}', [AdminController::class, 'destroyDepartment'])->name('admin.departments.destroy');

        // Categorías / Subcategorías / Tipos de Incidente
        // Base de conocimiento (RN-18)
        Route::get('/articulos',                     [ArticuloController::class, 'index'])->name('admin.articulos.index');
        Route::post('/articulos',                    [ArticuloController::class, 'store'])->name('admin.articulos.store');
        Route::get('/articulos/{articulo}/edit',     [ArticuloController::class, 'edit'])->name('admin.articulos.edit');
        Route::put('/articulos/{articulo}',          [ArticuloController::class, 'update'])->name('admin.articulos.update');
        Route::patch('/articulos/{articulo}/toggle', [ArticuloController::class, 'toggle'])->name('admin.articulos.toggle');

        Route::get('/categories',                                    [CategoryController::class, 'index'])->name('admin.categories.index');
        Route::post('/categories',                                   [CategoryController::class, 'store'])->name('admin.categories.store');
        Route::put('/categories/{categoria}',                        [CategoryController::class, 'update'])->name('admin.categories.update');
        Route::delete('/categories/{categoria}',                     [CategoryController::class, 'destroy'])->name('admin.categories.destroy');
        Route::post('/categories/{categoria}/subcategorias',         [CategoryController::class, 'storeSubcategoria'])->name('admin.subcategorias.store');
        Route::put('/subcategorias/{subcategoria}',                  [CategoryController::class, 'updateSubcategoria'])->name('admin.subcategorias.update');
        Route::delete('/subcategorias/{subcategoria}',               [CategoryController::class, 'destroySubcategoria'])->name('admin.subcategorias.destroy');
        Route::post('/subcategorias/{subcategoria}/tipos',           [CategoryController::class, 'storeTipo'])->name('admin.tipos.store');
        Route::delete('/tipos/{tipo}',                               [CategoryController::class, 'destroyTipo'])->name('admin.tipos.destroy');
        // Regla departamento automático por categoría
        Route::post('/categories/{categoria}/dept-rule',             [CategoryController::class, 'storeDeptRule'])->name('admin.categories.deptRule');
        Route::delete('/categories/{categoria}/dept-rule',           [CategoryController::class, 'destroyDeptRule'])->name('admin.categories.deptRule.destroy');

        // SLA
        Route::get('/sla',  [CategoryController::class, 'sla'])->name('admin.sla.index');
        Route::put('/sla',  [CategoryController::class, 'updateSla'])->name('admin.sla.update');

        // Reportes
        Route::get('/reports',                           [ReportController::class, 'index'])->name('admin.reports.index');
        Route::get('/reports/export',                    [ReportController::class, 'export'])->name('admin.reports.export');
        Route::get('/reports/export-pdf',                [ReportController::class, 'exportPdf'])->name('admin.reports.exportPdf');
        Route::get('/reports/export-excel',              [ReportController::class, 'exportExcel'])->name('admin.reports.exportExcel');
        Route::get('/reports/agents',                    [ReportController::class, 'agentReport'])->name('admin.reports.agents');
        // Rutas con filename en el path (para compatibilidad con Edge/Chrome)
        Route::get('/reports/download/{filename}.csv',   [ReportController::class, 'export'])->name('admin.reports.downloadCsv');
        Route::get('/reports/download/{filename}.xlsx',  [ReportController::class, 'exportExcel'])->name('admin.reports.downloadXlsx');
        Route::get('/reports/download/{filename}.pdf',   [ReportController::class, 'exportPdf'])->name('admin.reports.downloadPdf');

        // Reglas de Prioridad
        Route::get('/priority-rules',                    [PriorityRuleController::class, 'index'])->name('admin.priority-rules.index');
        Route::post('/priority-rules',                   [PriorityRuleController::class, 'store'])->name('admin.priority-rules.store');
        Route::delete('/priority-rules/{priorityRule}', [PriorityRuleController::class, 'destroy'])->name('admin.priority-rules.destroy');
        Route::get('/priority-rules/{priorityRule}/edit', [PriorityRuleController::class, 'edit'])->name('admin.priority-rules.edit');
        Route::put('/priority-rules/{priorityRule}',      [PriorityRuleController::class, 'update'])->name('admin.priority-rules.update');

        // Auditoría
        Route::get('/audit',    [AdminController::class, 'audit'])->name('admin.audit.index');

        // Configuración del Sistema
        Route::get('/settings',  [AdminController::class, 'settings'])->name('admin.settings.index');
        Route::post('/settings', [AdminController::class, 'updateSettings'])->name('admin.settings.update');

        // RF-AD-08: Configuración de estados del flujo de tickets
        Route::get('/states',  [AdminController::class, 'statesConfig'])->name('admin.states.index');
        Route::post('/states', [AdminController::class, 'updateStates'])->name('admin.states.update');

        // Manuales descargables — gestión admin (Reunión 4)
        Route::get('/manuales',                          [\App\Http\Controllers\Admin\ManualAdminController::class, 'index'])->name('admin.manuales.index');
        Route::get('/manuales/create',                   [\App\Http\Controllers\Admin\ManualAdminController::class, 'create'])->name('admin.manuales.create');
        Route::post('/manuales',                         [\App\Http\Controllers\Admin\ManualAdminController::class, 'store'])->name('admin.manuales.store');
        Route::get('/manuales/{manual}/edit',            [\App\Http\Controllers\Admin\ManualAdminController::class, 'edit'])->name('admin.manuales.edit');
        Route::put('/manuales/{manual}',                 [\App\Http\Controllers\Admin\ManualAdminController::class, 'update'])->name('admin.manuales.update');
        Route::delete('/manuales/{manual}',              [\App\Http\Controllers\Admin\ManualAdminController::class, 'destroy'])->name('admin.manuales.destroy');
    });
});
