<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Bitácora de la tienda: quién hizo qué y cuándo (para saber qué pasó si algo no cuadra).
 */
class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        /** @var \App\Models\Company $company */
        $company = auth()->guard('company')->user();

        $from = rescue(fn () => Carbon::parse($request->input('desde', today()->subDays(6))), today()->subDays(6), false)->startOfDay();
        $to   = rescue(fn () => Carbon::parse($request->input('hasta', today())), today(), false)->endOfDay();

        $logs = AuditLog::forCompany($company->id)
            ->whereBetween('created_at', [$from, $to])
            ->when($request->filled('persona'), function ($q) use ($request) {
                [$type, $id] = array_pad(explode(':', $request->persona, 2), 2, null);
                $q->where('actor_type', $type)->when($id, fn ($q) => $q->where('actor_id', $id));
            })
            ->when($request->filled('tipo') && isset(AuditLog::GROUPS[$request->tipo]), fn ($q) => $q->whereIn('action', AuditLog::GROUPS[$request->tipo]))
            ->when($request->filled('buscar'), fn ($q) => $q->whereRaw('LOWER(description) LIKE ?', ['%' . mb_strtolower($request->buscar) . '%']))
            ->orderByDesc('id')
            ->paginate(40)
            ->withQueryString();

        // Personas para el filtro: la empresa y sus empleados (también los eliminados, porque su historial sigue)
        $people = collect([['value' => 'company:' . $company->id, 'label' => $company->name . ' (empresa)']])
            ->concat($company->employees()->withTrashed()->orderBy('name')->get()
                ->map(fn ($e) => ['value' => 'employee:' . $e->id, 'label' => $e->name . ($e->trashed() ? ' (eliminado)' : '')]))
            ->push(['value' => 'system', 'label' => 'Intentos desconocidos']);

        return view('company.pages.audit', compact('logs', 'people', 'from', 'to'));
    }
}
