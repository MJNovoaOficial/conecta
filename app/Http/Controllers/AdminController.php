<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Department;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;

class AdminController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('admin');
    }

    public function dashboard()
    {
        $totalUsers = User::count();
        $totalTickets = Ticket::count();
        $openTickets = Ticket::where('status', 'open')->count();
        $resolvedTickets = Ticket::where('status', 'resolved')->count();

        Log::info('Panel de admin accedido', ['user_id' => Auth::id()]);

        return view('admin.dashboard', compact('totalUsers', 'totalTickets', 'openTickets', 'resolvedTickets'));
    }

    public function users()
    {
        $users = User::with('department')->paginate(20);
        return view('admin.users.index', compact('users'));
    }

    public function editUser(User $user)
    {
        $departments = Department::where('is_active', true)->get();
        return view('admin.users.edit', compact('user', 'departments'));
    }

    public function updateUser(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required|string|max:255|regex:/^[a-zA-Z\s]*$/',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'role' => 'required|in:user,support,admin',
            'department_id' => 'required|integer|exists:departments,id',
            'is_active' => 'boolean',
        ]);

        // No permitir que un usuario se elimine a sí mismo
        if ($user->id === Auth::id() && $request->input('is_active') === false) {
            return back()->withErrors(['is_active' => 'No puedes desactivar tu propia cuenta.']);
        }

        // Log de cambios
        $changes = [];
        if ($user->role !== $request->role) {
            $changes['role'] = ['from' => $user->role, 'to' => $request->role];
        }
        if ($user->is_active != $request->boolean('is_active')) {
            $changes['is_active'] = ['from' => $user->is_active, 'to' => $request->boolean('is_active')];
        }

        $user->update([
            'name' => trim($request->name),
            'email' => strtolower($request->email),
            'role' => $request->role,
            'department_id' => $request->department_id,
            'is_active' => $request->boolean('is_active', true),
        ]);

        Log::warning('Usuario modificado por admin', [
            'admin_id' => Auth::id(),
            'user_id' => $user->id,
            'changes' => $changes,
        ]);

        return redirect()->route('admin.users')->with('success', 'Usuario actualizado correctamente.');
    }

    public function departments()
    {
        $departments = Department::withCount('users', 'tickets')->paginate(20);
        return view('admin.departments.index', compact('departments'));
    }

    public function createDepartment()
    {
        return view('admin.departments.create');
    }

    public function storeDepartment(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:departments|regex:/^[a-zA-Z\s]*$/',
            'description' => 'nullable|string|max:1000',
        ]);

        Department::create([
            'name' => htmlspecialchars($request->name, ENT_QUOTES, 'UTF-8'),
            'description' => htmlspecialchars($request->description, ENT_QUOTES, 'UTF-8'),
            'is_active' => true,
        ]);

        Log::info('Departamento creado por admin', [
            'admin_id' => Auth::id(),
            'name' => $request->name,
        ]);

        return redirect()->route('admin.departments')->with('success', 'Departamento creado exitosamente.');
    }
}
