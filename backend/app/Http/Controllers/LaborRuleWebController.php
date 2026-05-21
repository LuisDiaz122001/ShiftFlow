<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLaborRuleRequest;
use App\Http\Requests\UpdateLaborRuleRequest;
use App\Models\LaborRule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LaborRuleWebController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', LaborRule::class);

        $query = LaborRule::query()->orderByDesc('vigente_desde');

        if ($request->filled('from')) {
            $query->whereDate('vigente_desde', '>=', $request->date('from'));
        }

        return Inertia::render('LaborRules/Index', [
            'laborRules' => $query->paginate(10)->withQueryString(),
            'filters' => $request->only(['from']),
        ]);
    }

    public function store(StoreLaborRuleRequest $request): RedirectResponse
    {
        $this->authorize('create', LaborRule::class);

        LaborRule::create($this->normalizeTimes($request->validated()));

        return redirect()
            ->route('labor-rules.index')
            ->with('success', 'Regla laboral creada correctamente.');
    }

    public function update(UpdateLaborRuleRequest $request, LaborRule $laborRule): RedirectResponse
    {
        $this->authorize('update', $laborRule);

        $laborRule->update($this->normalizeTimes($request->validated()));

        return redirect()
            ->route('labor-rules.index')
            ->with('success', 'Regla laboral actualizada correctamente.');
    }

    public function destroy(LaborRule $laborRule): RedirectResponse
    {
        $this->authorize('delete', $laborRule);

        $laborRule->delete();

        return redirect()
            ->route('labor-rules.index')
            ->with('success', 'Regla laboral eliminada correctamente.');
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function normalizeTimes(array $data): array
    {
        foreach (['hora_diurna_inicio', 'hora_nocturna_inicio'] as $field) {
            if (isset($data[$field]) && strlen($data[$field]) === 5) {
                $data[$field] .= ':00';
            }
        }

        return $data;
    }
}
