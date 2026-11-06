<?php

namespace App\Http\Controllers;

use App\Models\Articulo;
use App\Models\User;
use App\Models\Venta;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class VentaController extends Controller
{
    /** Pantalla de captura de venta (punto de venta). */
    public function nueva()
    {
        // Se mandan todos los activos; los que no tienen precio se muestran bloqueados
        $articulos = Articulo::where('activo', true)
            ->orderBy('tipo')->orderBy('descripcion')
            ->get(['id', 'clave', 'descripcion', 'tipo', 'precio']);

        $catalogo = $articulos->map(fn ($a) => [
            'id' => $a->id,
            'clave' => $a->clave,
            'descripcion' => $a->descripcion,
            'tipo' => $a->tipo,
            'precio' => (float) $a->precio,
        ])->values();

        $tipos = $articulos->pluck('tipo')->unique()->sort()->values();

        return view('ventas.nueva', [
            'catalogo' => $catalogo,
            'tipos' => $tipos,
            'formasPago' => Venta::FORMAS_PAGO,
        ]);
    }

    /** Guarda la venta. Los precios se toman SIEMPRE de la base, nunca del navegador. */
    public function guardar(Request $request)
    {
        $datos = $request->validate([
            'forma_pago' => ['required', Rule::in(array_keys(Venta::FORMAS_PAGO))],
            'pago_con' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
            'items' => ['required', 'array', 'min:1', 'max:200'],
            'items.*.articulo_id' => ['required', 'integer'],
            'items.*.cantidad' => ['required', 'numeric', 'gt:0', 'max:9999'],
        ], [
            'items.required' => 'Agrega al menos un artículo a la venta.',
        ]);

        // Juntar renglones repetidos del mismo artículo
        $cantidades = [];
        foreach ($datos['items'] as $item) {
            $id = (int) $item['articulo_id'];
            $cantidades[$id] = ($cantidades[$id] ?? 0) + (float) $item['cantidad'];
        }

        $articulos = Articulo::vendibles()->whereIn('id', array_keys($cantidades))->get()->keyBy('id');

        $faltantes = array_diff(array_keys($cantidades), $articulos->keys()->all());
        if ($faltantes) {
            throw ValidationException::withMessages([
                'items' => 'Uno o más artículos ya no están disponibles o no tienen precio. Actualiza la pantalla.',
            ]);
        }

        $venta = DB::transaction(function () use ($cantidades, $articulos, $datos, $request) {
            $venta = Venta::create([
                'user_id' => $request->user()->id,
                'fecha' => now(),
                'forma_pago' => $datos['forma_pago'],
                'total' => 0,
                'costo_total' => 0,
            ]);

            $total = 0;
            $costoTotal = 0;
            foreach ($cantidades as $id => $cantidad) {
                $a = $articulos[$id];
                $importe = round($cantidad * (float) $a->precio, 2);
                $costoImporte = round($cantidad * (float) $a->costo, 2);
                $total += $importe;
                $costoTotal += $costoImporte;

                $venta->detalles()->create([
                    'articulo_id' => $a->id,
                    'clave' => $a->clave,
                    'descripcion' => $a->descripcion,
                    'tipo' => $a->tipo,
                    'cantidad' => $cantidad,
                    'precio' => $a->precio,
                    'costo' => $a->costo,
                    'tasa_iva' => $a->tasa_iva,
                    'importe' => $importe,
                    'costo_importe' => $costoImporte,
                ]);
            }

            $total = round($total, 2);
            $pagoCon = null;
            if ($datos['forma_pago'] === 'efectivo' && isset($datos['pago_con']) && $datos['pago_con'] !== '') {
                $pagoCon = (float) $datos['pago_con'];
                if ($pagoCon + 0.001 < $total) {
                    throw ValidationException::withMessages([
                        'pago_con' => 'El efectivo recibido es menor que el total ($'.number_format($total, 2).').',
                    ]);
                }
            }

            $venta->update([
                'total' => $total,
                'costo_total' => round($costoTotal, 2),
                'pago_con' => $pagoCon,
            ]);

            return $venta;
        });

        $mensaje = 'Venta '.$venta->folio.' registrada por $'.number_format((float) $venta->total, 2);
        if ($venta->cambio !== null) {
            $mensaje .= ' · Cambio: $'.number_format($venta->cambio, 2);
        }

        return redirect()->route('ventas.nueva')
            ->with('ok', $mensaje)
            ->with('ultima_venta', $venta->id);
    }

    /** El vendedor ve solo sus ventas del día (sin costos). */
    public function misVentas(Request $request)
    {
        $ventas = Venta::with('detalles')
            ->where('user_id', $request->user()->id)
            ->whereDate('fecha', today())
            ->orderByDesc('fecha')
            ->get();

        return view('ventas.mias', [
            'ventas' => $ventas,
            'total' => $ventas->where('cancelada', false)->sum('total'),
        ]);
    }

    public function ticket(Request $request, Venta $venta)
    {
        $usuario = $request->user();
        if (! $usuario->esAdmin() && (int) $venta->user_id !== (int) $usuario->id) {
            abort(403);
        }

        $venta->load('detalles', 'vendedor');

        return view('ventas.ticket', ['venta' => $venta]);
    }

    /** Lista de ventas para el administrador. */
    public function lista(Request $request)
    {
        $desde = $this->fecha($request->input('desde'), today());
        $hasta = $this->fecha($request->input('hasta'), $desde->copy());
        if ($hasta->lt($desde)) {
            [$desde, $hasta] = [$hasta, $desde];
        }

        $consulta = Venta::with('vendedor')
            ->whereBetween('fecha', [$desde->copy()->startOfDay(), $hasta->copy()->endOfDay()])
            ->when($request->filled('vendedor'), fn ($q) => $q->where('user_id', $request->integer('vendedor')))
            ->when($request->input('estado') === 'vigentes', fn ($q) => $q->where('cancelada', false))
            ->when($request->input('estado') === 'canceladas', fn ($q) => $q->where('cancelada', true));

        $totales = (clone $consulta)->where('cancelada', false)
            ->selectRaw('COUNT(*) as ventas, COALESCE(SUM(total),0) as total')->first();

        return view('ventas.lista', [
            'ventas' => $consulta->orderByDesc('fecha')->paginate(50)->withQueryString(),
            'totales' => $totales,
            'vendedores' => User::orderBy('name')->get(['id', 'name']),
            'desde' => $desde,
            'hasta' => $hasta,
        ]);
    }

    public function cancelar(Request $request, Venta $venta)
    {
        $datos = $request->validate([
            'motivo' => ['required', 'string', 'max:200'],
        ], ['motivo.required' => 'Escribe el motivo de la cancelación.']);

        if ($venta->cancelada) {
            return back()->with('error', 'La venta '.$venta->folio.' ya estaba cancelada.');
        }

        $venta->update([
            'cancelada' => true,
            'cancelada_por' => $request->user()->id,
            'cancelada_en' => now(),
            'motivo_cancelacion' => $datos['motivo'],
        ]);

        return back()->with('ok', 'Venta '.$venta->folio.' cancelada.');
    }

    private function fecha(?string $valor, Carbon $defecto): Carbon
    {
        try {
            return $valor ? Carbon::createFromFormat('Y-m-d', $valor)->startOfDay() : $defecto->copy()->startOfDay();
        } catch (\Throwable) {
            return $defecto->copy()->startOfDay();
        }
    }
}
