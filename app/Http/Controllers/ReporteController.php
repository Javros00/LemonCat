<?php

namespace App\Http\Controllers;

use App\Models\Venta;
use App\Services\Hoja;
use App\Services\ReporteVentas;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReporteController extends Controller
{
    /** Panel del administrador: hoy y mes en curso. */
    public function panel()
    {
        $hoy = ReporteVentas::dia(today());
        $mes = ReporteVentas::mes(today());

        return view('reportes.panel', [
            'hoy' => $hoy->resumen(),
            'mes' => $mes->resumen(),
            'vendedoresHoy' => $hoy->porVendedor(),
            'ultimas' => Venta::with('vendedor')->orderByDesc('fecha')->limit(8)->get(),
        ]);
    }

    public function corteDiario(Request $request)
    {
        $dia = $this->dia($request->input('fecha'));
        $r = ReporteVentas::dia($dia);

        return view('reportes.corte-diario', [
            'dia' => $dia,
            'resumen' => $r->resumen(),
            'vendedores' => $r->porVendedor(),
            'formas' => $r->porFormaPago(),
            'articulos' => $r->porArticulo(),
            'tipos' => $r->porTipo(),
            'canceladas' => Venta::with('vendedor', 'canceladaPor')
                ->where('cancelada', true)
                ->whereBetween('fecha', [$r->desde, $r->hasta])->get(),
        ]);
    }

    public function corteMensual(Request $request)
    {
        $mes = $this->mes($request->input('mes'));
        $r = ReporteVentas::mes($mes);

        return view('reportes.corte-mensual', [
            'mes' => $mes,
            'resumen' => $r->resumen(),
            'dias' => $r->porDia(),
            'vendedores' => $r->porVendedor(),
            'formas' => $r->porFormaPago(),
            'tipos' => $r->porTipo(),
            'articulos' => $r->porArticulo(),
        ]);
    }

    public function poliza(Request $request)
    {
        $mes = $this->mes($request->input('mes'));

        return view('reportes.poliza', [
            'mes' => $mes,
            'poliza' => ReporteVentas::mes($mes)->poliza(),
        ]);
    }

    /**
     * Exporta ventas para el sistema local.
     * periodo=dia|mes, detalle=agrupado|renglones, formato=xlsx|csv
     */
    public function exportarVentas(Request $request)
    {
        $formato = $request->input('formato') === 'csv' ? 'csv' : 'xlsx';

        if ($request->input('periodo') === 'mes') {
            $mes = $this->mes($request->input('mes'));
            $r = ReporteVentas::mes($mes);
            $sufijo = $mes->format('Y_m');
        } else {
            $dia = $this->dia($request->input('fecha'));
            $r = ReporteVentas::dia($dia);
            $sufijo = $dia->format('Y_m_d');
        }

        if ($request->input('detalle') === 'renglones') {
            $filas = $r->renglones()->map(fn ($x) => [
                str_pad((string) $x->venta_id, 6, '0', STR_PAD_LEFT),
                Carbon::parse($x->fecha)->format('Y-m-d H:i'),
                $x->vendedor,
                Venta::FORMAS_PAGO[$x->forma_pago] ?? $x->forma_pago,
                $x->clave,
                $x->descripcion,
                $this->cantidad($x->cantidad),
                round((float) $x->precio, 2),
                round((float) $x->importe, 2),
            ]);

            return Hoja::descargar('ventas_detalle_'.$sufijo, $formato,
                ['folio', 'fecha', 'vendedor', 'forma_pago', 'clave', 'descripcion', 'cantidad', 'precio', 'monto'],
                $filas, [7, 8], 'Ventas');
        }

        // Agrupado por artículo: clave, cantidad, monto (+ descripción como referencia)
        $filas = $r->porArticulo()->map(fn ($x) => [
            $x->clave,
            $this->cantidad($x->cantidad),
            round((float) $x->monto, 2),
            $x->descripcion,
        ]);

        return Hoja::descargar('ventas_'.$sufijo, $formato,
            ['clave', 'cantidad', 'monto', 'descripcion'], $filas, [2], 'Ventas');
    }

    public function exportarPoliza(Request $request)
    {
        $mes = $this->mes($request->input('mes'));
        $p = ReporteVentas::mes($mes)->poliza();

        $filas = collect($p['lineas'])->map(fn ($l) => [
            $p['fecha']->format('Y-m-d'), $p['tipo'], $l['cuenta'], $l['nombre'], $l['concepto'], $l['cargo'], $l['abono'],
        ])->push(['', '', '', '', 'TOTALES', $p['cargos'], $p['abonos']]);

        return Hoja::descargar('poliza_'.$mes->format('Y_m'),
            $request->input('formato') === 'csv' ? 'csv' : 'xlsx',
            ['fecha', 'tipo', 'cuenta', 'nombre_cuenta', 'concepto', 'cargo', 'abono'],
            $filas, [5, 6], 'Poliza');
    }

    private function dia(?string $valor): Carbon
    {
        try {
            return $valor ? Carbon::createFromFormat('Y-m-d', $valor)->startOfDay() : today();
        } catch (\Throwable) {
            return today();
        }
    }

    private function mes(?string $valor): Carbon
    {
        try {
            return $valor ? Carbon::createFromFormat('Y-m-d', $valor.'-01')->startOfMonth() : today()->startOfMonth();
        } catch (\Throwable) {
            return today()->startOfMonth();
        }
    }

    /** 3 piezas → 3 ; 1.5 kg → 1.5 */
    private function cantidad($v): float|int
    {
        $f = round((float) $v, 3);

        return floor($f) == $f ? (int) $f : $f;
    }
}
