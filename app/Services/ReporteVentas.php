<?php

namespace App\Services;

use App\Models\Venta;
use App\Models\VentaDetalle;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Cálculos de cortes (diario/mensual) y póliza.
 * Las ventas canceladas nunca se suman.
 */
class ReporteVentas
{
    public function __construct(
        public readonly Carbon $desde,
        public readonly Carbon $hasta,
    ) {}

    public static function dia(Carbon $dia): self
    {
        return new self($dia->copy()->startOfDay(), $dia->copy()->endOfDay());
    }

    public static function mes(Carbon $mes): self
    {
        return new self($mes->copy()->startOfMonth()->startOfDay(), $mes->copy()->endOfMonth()->endOfDay());
    }

    private function ventas()
    {
        return Venta::query()->whereBetween('fecha', [$this->desde, $this->hasta]);
    }

    private function detalles()
    {
        return VentaDetalle::query()
            ->join('ventas', 'ventas.id', '=', 'venta_detalles.venta_id')
            ->where('ventas.cancelada', false)
            ->whereBetween('ventas.fecha', [$this->desde, $this->hasta]);
    }

    /** Totales generales del periodo. */
    public function resumen(): array
    {
        $v = $this->ventas()->where('cancelada', false)
            ->selectRaw('COUNT(*) as n, COALESCE(SUM(total),0) as total, COALESCE(SUM(costo_total),0) as costo')
            ->first();

        $c = $this->ventas()->where('cancelada', true)
            ->selectRaw('COUNT(*) as n, COALESCE(SUM(total),0) as total')
            ->first();

        $total = round((float) $v->total, 2);
        $costo = round((float) $v->costo, 2);
        $n = (int) $v->n;

        return [
            'ventas' => $n,
            'total' => $total,
            'costo' => $costo,
            'utilidad' => round($total - $costo, 2),
            'margen' => $total > 0 ? round(($total - $costo) / $total * 100, 1) : 0,
            'ticket_promedio' => $n > 0 ? round($total / $n, 2) : 0,
            'canceladas' => (int) $c->n,
            'total_canceladas' => round((float) $c->total, 2),
        ];
    }

    public function porVendedor(): Collection
    {
        return $this->ventas()->where('cancelada', false)
            ->join('users', 'users.id', '=', 'ventas.user_id')
            ->groupBy('users.id', 'users.name')
            ->orderBy('users.name')
            ->selectRaw('users.name as vendedor, COUNT(*) as ventas, SUM(ventas.total) as total')
            ->get();
    }

    public function porFormaPago(): Collection
    {
        return $this->ventas()->where('cancelada', false)
            ->groupBy('forma_pago')
            ->orderBy('forma_pago')
            ->selectRaw('forma_pago, COUNT(*) as ventas, SUM(total) as total')
            ->get()
            ->map(function ($r) {
                $r->nombre = Venta::FORMAS_PAGO[$r->forma_pago] ?? $r->forma_pago;

                return $r;
            });
    }

    /** Agrupado por artículo: clave, cantidad y monto (lo que pide el sistema local). */
    public function porArticulo(): Collection
    {
        return $this->detalles()
            ->groupBy('venta_detalles.clave', 'venta_detalles.descripcion', 'venta_detalles.tipo')
            ->orderBy('venta_detalles.clave')
            ->selectRaw('venta_detalles.clave, venta_detalles.descripcion, venta_detalles.tipo,
                SUM(venta_detalles.cantidad) as cantidad,
                SUM(venta_detalles.importe) as monto,
                SUM(venta_detalles.costo_importe) as costo')
            ->get();
    }

    public function porTipo(): Collection
    {
        return $this->detalles()
            ->groupBy('venta_detalles.tipo')
            ->orderByRaw('SUM(venta_detalles.importe) DESC')
            ->selectRaw('venta_detalles.tipo, SUM(venta_detalles.cantidad) as cantidad,
                SUM(venta_detalles.importe) as monto, SUM(venta_detalles.costo_importe) as costo')
            ->get();
    }

    /** Totales por día (para el corte mensual). */
    public function porDia(): Collection
    {
        $filas = $this->ventas()->where('cancelada', false)
            ->get(['fecha', 'total', 'costo_total']);

        return $filas->groupBy(fn ($v) => $v->fecha->format('Y-m-d'))
            ->map(fn ($g, $dia) => (object) [
                'dia' => Carbon::parse($dia),
                'ventas' => $g->count(),
                'total' => round($g->sum(fn ($v) => (float) $v->total), 2),
                'costo' => round($g->sum(fn ($v) => (float) $v->costo_total), 2),
            ])
            ->sortKeys()
            ->values();
    }

    /** Cada renglón vendido (detalle completo), para exportar sin agrupar. */
    public function renglones(): Collection
    {
        return $this->detalles()
            ->join('users', 'users.id', '=', 'ventas.user_id')
            ->orderBy('ventas.fecha')->orderBy('ventas.id')
            ->get([
                'ventas.id as venta_id', 'ventas.fecha', 'users.name as vendedor', 'ventas.forma_pago',
                'venta_detalles.clave', 'venta_detalles.descripcion', 'venta_detalles.cantidad',
                'venta_detalles.precio', 'venta_detalles.importe',
            ]);
    }

    /**
     * Póliza del periodo. Los precios son netos (IVA incluido), así que el IVA
     * se desglosa por renglón según la tasa de cada artículo.
     */
    public function poliza(): array
    {
        $cfg = config('ventas.poliza');
        $cuentas = $cfg['cuentas'];

        // Cobros: caja (efectivo) vs bancos (tarjeta, transferencia)
        $caja = 0.0;
        $bancos = 0.0;
        foreach ($this->porFormaPago() as $fp) {
            if (in_array($fp->forma_pago, $cfg['pagos_a_caja'], true)) {
                $caja += (float) $fp->total;
            } else {
                $bancos += (float) $fp->total;
            }
        }
        $caja = round($caja, 2);
        $bancos = round($bancos, 2);
        $total = round($caja + $bancos, 2);

        // IVA por tasa
        $porTasa = $this->detalles()
            ->groupBy('venta_detalles.tasa_iva')
            ->selectRaw('venta_detalles.tasa_iva as tasa, SUM(venta_detalles.importe) as importe')
            ->get();

        $iva = 0.0;
        foreach ($porTasa as $t) {
            $tasa = (float) $t->tasa;
            if ($tasa > 0) {
                $importe = (float) $t->importe;
                $iva += $importe - $importe / (1 + $tasa / 100);
            }
        }
        $iva = round($iva, 2);
        $subtotal = round($total - $iva, 2); // así siempre cuadra

        $costo = round((float) $this->detalles()->sum('venta_detalles.costo_importe'), 2);

        $concepto = 'Ventas del '.$this->desde->format('d/m/Y').' al '.$this->hasta->format('d/m/Y');
        $lineas = [];
        $agregar = function (string $cta, float $cargo, float $abono) use (&$lineas, $cuentas, $concepto) {
            if ($cargo == 0 && $abono == 0) {
                return;
            }
            $lineas[] = [
                'cuenta' => $cuentas[$cta]['numero'],
                'nombre' => $cuentas[$cta]['nombre'],
                'concepto' => $concepto,
                'cargo' => $cargo,
                'abono' => $abono,
            ];
        };

        $agregar('caja', $caja, 0);
        $agregar('bancos', $bancos, 0);
        $agregar('ventas', 0, $subtotal);
        $agregar('iva_trasladado', 0, $iva);
        if ($cfg['incluir_costo']) {
            $agregar('costo_ventas', $costo, 0);
            $agregar('almacen', 0, $costo);
        }

        $cargos = round(array_sum(array_column($lineas, 'cargo')), 2);
        $abonos = round(array_sum(array_column($lineas, 'abono')), 2);

        return [
            'tipo' => $cfg['tipo'],
            'fecha' => $this->hasta->copy()->startOfDay(),
            'concepto' => $concepto,
            'lineas' => $lineas,
            'cargos' => $cargos,
            'abonos' => $abonos,
            'cuadra' => abs($cargos - $abonos) < 0.01,
            'total' => $total,
            'subtotal' => $subtotal,
            'iva' => $iva,
            'costo' => $costo,
        ];
    }
}
