<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreHolidayRequest;
use App\Http\Requests\UpdateHolidayRequest;
use App\Models\Holiday;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HolidayWebController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Holiday::class);

        $query = Holiday::query()->orderByDesc('fecha');

        if ($request->filled('year')) {
            $query->whereYear('fecha', $request->integer('year'));
        }

        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->where('nombre', 'like', "%{$search}%");
        }

        return Inertia::render('Holidays/Index', [
            'holidays' => $query->paginate(12)->withQueryString(),
            'filters' => $request->only(['year', 'search']),
        ]);
    }

    public function store(StoreHolidayRequest $request): RedirectResponse
    {
        $this->authorize('create', Holiday::class);

        Holiday::create($request->validated());

        return redirect()
            ->route('holidays.index', $request->only(['year', 'search']))
            ->with('success', 'Festivo creado correctamente.');
    }

    public function update(UpdateHolidayRequest $request, Holiday $holiday): RedirectResponse
    {
        $this->authorize('update', $holiday);

        $holiday->update($request->validated());

        return redirect()
            ->route('holidays.index', $request->only(['year', 'search']))
            ->with('success', 'Festivo actualizado correctamente.');
    }

    public function destroy(Holiday $holiday): RedirectResponse
    {
        $this->authorize('delete', $holiday);

        $holiday->delete();

        return redirect()
            ->route('holidays.index')
            ->with('success', 'Festivo eliminado correctamente.');
    }
}
