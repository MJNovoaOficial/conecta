<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Categoria;
use App\Models\LoginAttempt;
use App\Models\SystemSetting;
use App\Models\User;
use App\Models\Department;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class AdminController extends Controller
{
    public function dashboard(Request $request)
    {
        // Filtro de tiempo
        $period = $request->get('period', '30d');
        $periodMap = [
            '7d'  => ['days' => 7,   'label' => 'Últimos 7 días',   'months' => null, 'dbformat' => '%Y-%m-%d'],
            '30d' => ['days' => 30,  'label' => 'Últimos 30 días',  'months' => null, 'dbformat' => '%Y-%m-%d'],
            '3m'  => ['days' => 90,  'label' => 'Últimos 3 meses',  'months' => 3,    'dbformat' => '%Y-%m'],
            '6m'  => ['days' => 180, 'label' => 'Últimos 6 meses',  'months' => 6,    'dbformat' => '%Y-%m'],
            '12m' => ['days' => 365, 'label' => 'Últimos 12 meses', 'months' => 12,   'dbformat' => '%Y-%m'],
        ];
        $pCfg = $periodMap[$period] ?? $periodMap['30d'];
        $since = now()->subDays($pCfg['days']);
        $dbformat = $pCfg['dbformat'];
        $periodLabel = $pCfg['label'];

        $totalUsers        = User::count();
        $activeUsers       = User::where('is_active', true)->count();
        $totalTickets      = Ticket::count();
        $openTickets       = Ticket::where('status', 'open')->count();
        $inProgressTickets = Ticket::where('status', 'in_progress')->count();
        $pendingTickets    = Ticket::where('status', 'pending_user')->count();
        $resolvedTickets   = Ticket::where('status', 'resolved')->count();
        $closedTickets     = Ticket::where('status', 'closed')->count();
        $totalDepts        = Department::count();

        // Tickets por prioridad (filtrado por período)
        $byPriority = Ticket::select('priority', DB::raw('count(*) as total'))
            ->where('created_at', '>=', $since)
            ->groupBy('priority')
            ->pluck('total', 'priority');

        // Tickets por categoría (top 5, filtrado por período)
        $byCategory = \App\Models\Categoria::withCount([
                'subcategorias as ticket_count' => fn($q) => $q
                    ->join('tickets', 'tickets.subcategoria_id', '=', 'subcategorias.id')
                    ->where('tickets.created_at', '>=', $since)
                    ->select(DB::raw('COUNT(tickets.id)'))
            ])
            ->orderByDesc('ticket_count')
            ->take(5)
            ->get()
            ->pluck('ticket_count', 'name');

        // Tickets por técnico (activos — sin filtro de tiempo)
        $byAgent = User::where(function($q) {
                $q->where('role', 'support')->orWhere('role', 'admin');
            })
            ->withCount(['assignedTickets as active_count' => fn($q) =>
                $q->whereIn('status', ['open','in_progress','pending_user'])
            ])
            ->orderByDesc('active_count')
            ->get();

        // Tendencia (agrupado según período)
        $monthly = Ticket::select(
                DB::raw("DATE_FORMAT(created_at, \"{$dbformat}\") as month"),
                DB::raw('count(*) as total')
            )
            ->where('created_at', '>=', $since)
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // Tickets recientes
        $recentTickets = Ticket::with(['user','assignedTo'])
            ->orderByRaw("FIELD(priority, 'critical','high','medium','low')")
            ->orderByDesc('created_at')
            ->take(8)
            ->get();

        // Usuarios con mayor cantidad de solicitudes (RN-24)
        $topRequesters = User::where('role', 'user')
            ->withCount('tickets as ticket_count')
            ->orderByDesc('ticket_count')
            ->take(5)
            ->get();

        // Tiempo promedio de resolución (horas) (RN-24)
        $avgResolutionHours = Ticket::whereNotNull('resolved_at')
            ->where('created_at', '>=', $since)
            ->select(DB::raw('AVG(TIMESTAMPDIFF(HOUR, created_at, resolved_at)) as avg_h'))
            ->value('avg_h');

        // % Cumplimiento de SLA (RN-24 / RN-17)
        $totalResolved  = Ticket::whereNotNull('resolved_at')->count();
        $resolvedInSla  = Ticket::whereNotNull('resolved_at')
            ->whereNotNull('sla_resolution_deadline_at')
            ->where('resolved_at', '<=', DB::raw('sla_resolution_deadline_at'))
            ->count();
        $slaCompliance = $totalResolved > 0 ? round(($resolvedInSla / $totalResolved) * 100, 1) : null;

        return view('admin.dashboard', compact(
            'totalUsers', 'activeUsers', 'totalTickets',
            'openTickets', 'inProgressTickets', 'pendingTickets',
            'resolvedTickets', 'closedTickets', 'totalDepts',
            'byPriority', 'byCategory', 'byAgent', 'monthly', 'recentTickets',
            'topRequesters', 'avgResolutionHours', 'slaCompliance',
            'period', 'periodLabel'
        ));
    }

    public function users()
    {
        $users = User::with('department')->paginate(20);
        $departments = Department::where('is_active', true)->get();
        return view('admin.users.index', compact('users', 'departments'));
    }

    public function editUser(User $user)
    {
        $departments = Department::where('is_active', true)->get();
        return view('admin.users.edit', compact('user', 'departments'));
    }

    public function createUser()
    {
        $departments = Department::where('is_active', true)->get();
        return view('admin.users.create', compact('departments'));
    }

    public function storeUser(Request $request)
    {
        $request->validate([
            'name'                  => 'required|string|max:255|regex:/^[\p{L}\s]+$/u',
            'email'                 => 'required|email|max:255|unique:usuarios,email',
            'department_id'         => 'required|integer|exists:departamentos,id',
            'role'                  => 'required|in:user,support,admin',
            'password'              => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name'          => trim($request->name),
            'email'         => strtolower($request->email),
            'password'      => Hash::make($request->password),
            'department_id' => $request->department_id,
            'role'          => $request->role,
            'is_active'     => true,
        ]);

        Log::info('Usuario creado por admin', [
            'admin_id' => Auth::id(),
            'user_id'  => $user->id,
            'role'     => $request->role,
        ]);

        return redirect()->route('admin.users')->with('success', 'Usuario "' . $user->name . '" creado correctamente.');
    }

    public function updateUser(Request $request, User $user)
    {
        $request->validate([
            'name'          => 'required|string|max:255|regex:/^[\p{L}\s]+$/u',
            'email'         => 'required|email|max:255|unique:usuarios,email,' . $user->id,
            'role'          => 'required|in:user,support,admin',
            'department_id' => 'required|integer|exists:departamentos,id',
            'is_active'     => 'nullable|boolean',
        ]);

        // El checkbox no envía nada cuando está desmarcado → boolean() retorna false por defecto
        $isActive = $request->boolean('is_active');

        // No permitir que un admin se desactive a sí mismo
        if ($user->id === Auth::id() && !$isActive) {
            return back()->withErrors(['is_active' => 'No puedes desactivar tu propia cuenta.']);
        }

        // Log de cambios
        $changes = [];
        if ($user->role !== $request->role) {
            $changes['role'] = ['from' => $user->role, 'to' => $request->role];
        }
        if ((bool) $user->is_active !== $isActive) {
            $changes['is_active'] = ['from' => $user->is_active, 'to' => $isActive];
        }

        $user->update([
            'name'          => trim($request->name),
            'email'         => strtolower($request->email),
            'role'          => $request->role,
            'department_id' => $request->department_id,
            'is_active'     => $isActive,
        ]);

        Log::warning('Usuario modificado por admin', [
            'admin_id' => Auth::id(),
            'user_id'  => $user->id,
            'changes'  => $changes,
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
            'name' => 'required|string|max:255|unique:departamentos,name|regex:/^[\p{L}\s\-]+$/u',
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

        return redirect()->route('admin.departments.index')->with('success', 'Departamento creado exitosamente.');

    }

    // Mostrar formulario de edición de un departamento (opcional, si se necesita una página separada)
    public function editDepartment(Department $department)
    {
        return view('admin.departments.edit', compact('department'));
    }

    // Actualizar departamento existente
    public function updateDepartment(Request $request, Department $department)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:departamentos,name,' . $department->id . '|regex:/^[\p{L}\s\-]+$/u',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'nullable|boolean',
        ]);

        $isActive = $request->boolean('is_active');
        $department->update([
            'name' => htmlspecialchars($request->name, ENT_QUOTES, 'UTF-8'),
            'description' => htmlspecialchars($request->description, ENT_QUOTES, 'UTF-8'),
            'is_active' => $isActive,
        ]);

        Log::info('Departamento actualizado por admin', [
            'admin_id' => Auth::id(),
            'department_id' => $department->id,
        ]);

        return redirect()->route('admin.departments.index')->with('success', 'Departamento actualizado exitosamente.');
    }

    public function destroyDepartment(Department $department)
    {
        // Safety: prevent deleting if users are assigned
        if ($department->users()->count() > 0) {
            return redirect()->route('admin.departments.index')
                ->with('error', 'No se puede eliminar el departamento "' . $department->name . '" porque tiene usuarios asignados.');
        }

        $name = $department->name;
        $department->delete();

        Log::warning('Departamento eliminado por admin', [
            'admin_id' => Auth::id(),
            'department_name' => $name,
        ]);

        return redirect()->route('admin.departments.index')
            ->with('success', 'Departamento "' . $name . '" eliminado correctamente.');
    }

    public function audit()
    {
        $logs = AuditLog::with('user')
            ->orderByDesc('created_at')
            ->paginate(50);

        return view('admin.audit.index', compact('logs'));
    }

    public function settings()
    {
        // Obtener configuraciones agrupadas
        $groups = ['general', 'notifications', 'sla', 'security'];
        $settings = [];
        foreach ($groups as $g) {
            $settings[$g] = SystemSetting::byGroup($g);
        }

        // Últimos 10 intentos fallidos de login
        $recentFailedLogins = LoginAttempt::where('successful', false)
            ->orderByDesc('attempted_at')
            ->take(10)
            ->get();

        return view('admin.settings.index', compact('settings', 'recentFailedLogins'));
    }

    public function updateSettings(Request $request)
    {
        $data = $request->input('settings', []);

        // Obtener todos los settings de DB para manejar los booleanos no enviados
        $allSettings = SystemSetting::all()->keyBy('key');

        foreach ($allSettings as $key => $setting) {
            if ($setting->type === 'boolean') {
                // Checkbox no enviado = false
                $value = isset($data[$key]) ? '1' : '0';
            } elseif (isset($data[$key])) {
                $value = $data[$key];
            } else {
                continue;
            }

            SystemSetting::set($key, $value);
        }

        AuditLog::record('settings.updated', 'SystemSetting', null, ['keys' => array_keys($data)]);

        return redirect()->route('admin.settings.index')->with('success', 'Configuración guardada correctamente.');
    }
}
