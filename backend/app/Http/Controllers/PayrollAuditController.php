<?php
namespace App\Http\Controllers;

use App\Models\PayrollLog;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PayrollAuditController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', \App\Models\Payroll::class);

        if (!auth()->user()->isAdmin()) {
            abort(403, 'Solo los administradores pueden acceder al panel de auditoría.');
        }

        $query = PayrollLog::query()
            ->with(['user', 'payroll.employee'])
            ->orderByDesc('created_at');

        // Filtros
        if ($request->user_id) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->action) {
            $query->where('action', $request->action);
        }

        if ($request->date_from) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->date_to) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('payroll_id', 'like', "%{$request->search}%")
                  ->orWhereHas('payroll.employee', function ($sq) use ($request) {
                      $sq->where('nombre', 'like', "%{$request->search}%")
                        ->orWhere('documento', 'like', "%{$request->search}%");
                  });
            });
        }

        $logs = $query->paginate(20)->withQueryString();

        return Inertia::render('Payrolls/Audit', [
            'logs' => $logs,
            'filters' => $request->only(['user_id', 'action', 'date_from', 'date_to', 'search']),
            'users' => User::all(['id', 'name']),
            'actions' => [
                'create' => 'Creación',
                'pay' => 'Pago',
                'cancel' => 'Cancelación',
                'blocked_attempt' => 'Intento Bloqueado',
                'close_period' => 'Cierre de Periodo',
            ]
        ]);
    }
}
