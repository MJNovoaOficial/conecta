<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\AdminController;

// Página de inicio: login directo o redirige a tickets si ya está autenticado
Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('tickets.index');
    }
    return view('welcome');
})->name('home');

// Rutas de autenticación
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register');
Route::post('/register', [AuthController::class, 'register']);

// Rutas para invitados (sin autenticación)
Route::get('/tickets/guest', [TicketController::class, 'guestCreate'])->name('tickets.guest.create');
Route::post('/tickets/guest', [TicketController::class, 'guestStore'])->name('tickets.guest.store');
Route::get('/tickets/guest/{token}', [TicketController::class, 'guestShow'])->name('tickets.guest.show');

// Rutas protegidas por autenticación
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Tickets
    Route::resource('tickets', TicketController::class);
    Route::post('/tickets/{ticket}/comment', [TicketController::class, 'addComment'])->name('tickets.addComment');
    Route::put('/tickets/{ticket}/status', [TicketController::class, 'updateStatus'])->name('tickets.updateStatus');
    Route::post('/tickets/{ticket}/assign', [TicketController::class, 'assignTo'])->name('tickets.assignTo');
    Route::post('/tickets/{ticket}/self-assign', [TicketController::class, 'selfAssign'])->name('tickets.selfAssign');
    Route::post('/tickets/{ticket}/forward', [TicketController::class, 'forward'])->name('tickets.forward');
    Route::get('/tickets/{ticket}/panel', [TicketController::class, 'panel'])->name('tickets.panel');

    // Admin
    Route::middleware('admin')->prefix('admin')->group(function () {
        Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard');
        Route::get('/users', [AdminController::class, 'users'])->name('admin.users');
        Route::get('/users/create', [AdminController::class, 'createUser'])->name('admin.users.create');
        Route::post('/users', [AdminController::class, 'storeUser'])->name('admin.users.store');
        Route::get('/users/{user}/edit', [AdminController::class, 'editUser'])->name('admin.users.edit');
        Route::put('/users/{user}', [AdminController::class, 'updateUser'])->name('admin.users.update');
        Route::get('/departments', [AdminController::class, 'departments'])->name('admin.departments');
        Route::get('/departments/create', [AdminController::class, 'createDepartment'])->name('admin.departments.create');
        Route::post('/departments', [AdminController::class, 'storeDepartment'])->name('admin.departments.store');
    });
});
