<?php

namespace App\Http\Controllers;

use App\Models\Articulo;
use App\Models\CambioPrecio;
use App\Services\Hoja;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ArticuloController extends Controller
{
    public function lista(Request $request)
    {
        $buscar = trim((string) $request->input('buscar'));
        $articulos = Articulo::query()
            ->when($buscar !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('clave', 'like', "%{$buscar}%")
                ->orWhere('descripcion', 'like', "%{$buscar}%")))
            ->when($request->filled('tipo'), fn ($q) => $q->where('tipo', $request->input('tipo')))
            ->when($request->input('estado') === 'sin_precio', fn ($q) => $q->where('precio', '<=', 0))
            ->when($request->input('estado') === 'inactivos', fn ($q) => $q->where('activo', false))
            ->orderBy('clave')
            ->get();

        return view('articulos.lista', [
            'articulos' => $articulos,
            'tipos' => $this->tipos(),
            'sinPrecio' => Articulo::where('activo', true)->where('precio', '<=', 0)->count(),
        ]);
    }

    public function nuevo()
    {
        return view('articulos.form', [
            'articulo' => new Articulo(['activo' => true, 'tasa_iva' => 0, 'clave' => $this->siguienteClave()]),
            'tipos' => $this->tipos(),
        ]);
    }

    public function guardar(Request $request)
    {
        $datos = $this->validar($request);
        $articulo = Articulo::create($datos);

        return redirect()->route('articulos.lista')->with('ok', "Artículo {$articulo->clave} agregado.");
    }

    public function editar(Articulo $articulo)
    {
        return view('articulos.form', [
            'articulo' => $articulo,
            'tipos' => $this->tipos(),
            'cambios' => $articulo->cambios()->with('usuario')->limit(10)->get(),
        ]);
    }

    public function actualizar(Request $request, Articulo $articulo)
    {
        $datos = $this->validar($request, $articulo);

        DB::transaction(function () use ($articulo, $datos, $request) {
            $articulo->actualizarPrecioCosto((float) $datos['precio'], (float) $datos['costo'], $request->user()->id);
            $articulo->update(collect($datos)->except(['precio', 'costo'])->all());
        });

        return redirect()->route('articulos.lista')->with('ok', "Artículo {$articulo->clave} actualizado.");
    }

    public function cambiarActivo(Articulo $articulo)
    {
        $articulo->update(['activo' => ! $articulo->activo]);

        return back()->with('ok', $articulo->clave.($articulo->activo ? ' activado.' : ' desactivado (ya no aparece al vender).'));
    }

    /** Tabla para cambiar muchos precios y costos a la vez. */
    public function precios(Request $request)
    {
        $articulos = Articulo::where('activo', true)
            ->when($request->filled('tipo'), fn ($q) => $q->where('tipo', $request->input('tipo')))
            ->orderBy('tipo')->orderBy('clave')
            ->get();

        return view('articulos.precios', [
            'articulos' => $articulos,
            'tipos' => $this->tipos(),
        ]);
    }

    public function guardarPrecios(Request $request)
    {
        $datos = $request->validate([
            'precios' => ['required', 'array'],
            'precios.*.precio' => ['required', 'numeric', 'min:0', 'max:999999'],
            'precios.*.costo' => ['required', 'numeric', 'min:0', 'max:999999'],
        ], [], ['precios.*.precio' => 'precio', 'precios.*.costo' => 'costo']);

        $cambiados = 0;
        DB::transaction(function () use ($datos, $request, &$cambiados) {
            $articulos = Articulo::whereIn('id', array_keys($datos['precios']))->get()->keyBy('id');
            foreach ($datos['precios'] as $id => $p) {
                if (isset($articulos[$id]) && $articulos[$id]->actualizarPrecioCosto((float) $p['precio'], (float) $p['costo'], $request->user()->id, 'masivo')) {
                    $cambiados++;
                }
            }
        });

        return back()->with('ok', $cambiados === 0 ? 'No hubo cambios.' : "Se actualizaron {$cambiados} artículos.");
    }

    public function historial()
    {
        return view('articulos.historial', [
            'cambios' => CambioPrecio::with('articulo', 'usuario')->latest()->paginate(50),
        ]);
    }

    /** Descarga el catálogo con el mismo formato que se importa. */
    public function exportar(Request $request)
    {
        $filas = Articulo::orderBy('clave')->get()->map(fn ($a) => [
            $a->clave, $a->descripcion, $a->tipo, (float) $a->precio, (float) $a->costo,
            (float) $a->tasa_iva, $a->activo ? 'SI' : 'NO',
        ]);

        return Hoja::descargar(
            'catalogo_'.now()->format('Ymd'),
            $request->input('formato') === 'csv' ? 'csv' : 'xlsx',
            ['plu', 'producto', 'tipo', 'precio', 'costo', 'iva', 'activo'],
            $filas,
            [3, 4],
            'Catalogo'
        );
    }

    public function formImportar()
    {
        return view('articulos.importar');
    }

    /**
     * Importa Excel/CSV con columnas: plu (o clave), producto (o descripcion),
     * tipo (o familia), precio, costo. Opcionales: iva, activo.
     * Si la clave existe la actualiza; si no, la crea.
     */
    public function importar(Request $request)
    {
        $request->validate([
            'archivo' => ['required', 'file', 'mimes:xlsx,xls,csv,txt', 'max:5120'],
        ], ['archivo.required' => 'Selecciona un archivo.', 'archivo.mimes' => 'El archivo debe ser Excel (.xlsx, .xls) o CSV.']);

        try {
            $filas = Hoja::leer($request->file('archivo')->getRealPath());
        } catch (\Throwable $e) {
            return back()->with('error', 'No se pudo leer el archivo: '.$e->getMessage());
        }

        $alias = [
            'clave' => ['plu', 'clave', 'codigo', 'sku'],
            'descripcion' => ['producto', 'descripcion', 'articulo', 'nombre'],
            'tipo' => ['tipo', 'familia', 'categoria', 'linea'],
            'precio' => ['precio', 'precio_venta'],
            'costo' => ['costo', 'costo_unitario'],
            'tasa_iva' => ['iva', 'tasa_iva'],
            'activo' => ['activo'],
        ];
        $valor = function (array $fila, string $campo) use ($alias) {
            foreach ($alias[$campo] as $a) {
                if (array_key_exists($a, $fila)) {
                    return $fila[$a];
                }
            }

            return null;
        };

        if (! $filas || ! array_intersect(array_keys($filas[0]), $alias['clave'])) {
            return back()->with('error', 'No encontré la columna de clave (plu o clave) en la primera fila del archivo.');
        }

        $creados = 0;
        $actualizados = 0;
        $errores = [];
        $userId = $request->user()->id;

        DB::transaction(function () use ($filas, $valor, $userId, &$creados, &$actualizados, &$errores) {
            foreach ($filas as $i => $fila) {
                $renglon = $i + 2;
                $clave = trim((string) $valor($fila, 'clave'));
                if ($clave === '') {
                    $errores[] = "Fila {$renglon}: sin clave, se omitió.";
                    continue;
                }

                $precio = $this->numero($valor($fila, 'precio'));
                $costo = $this->numero($valor($fila, 'costo'));
                if (($precio !== null && $precio < 0) || ($costo !== null && $costo < 0)) {
                    $errores[] = "Fila {$renglon} ({$clave}): precio o costo negativo, se omitió.";
                    continue;
                }

                $descripcion = trim((string) $valor($fila, 'descripcion'));
                $tipo = trim((string) $valor($fila, 'tipo'));
                $iva = $this->numero($valor($fila, 'tasa_iva'));
                $activoTxt = $valor($fila, 'activo');

                $articulo = Articulo::where('clave', $clave)->first();
                if (! $articulo) {
                    if ($descripcion === '') {
                        $errores[] = "Fila {$renglon} ({$clave}): artículo nuevo sin descripción, se omitió.";
                        continue;
                    }
                    Articulo::create([
                        'clave' => $clave,
                        'descripcion' => $descripcion,
                        'tipo' => $tipo !== '' ? $tipo : 'Sin tipo',
                        'precio' => $precio ?? 0,
                        'costo' => $costo ?? 0,
                        'tasa_iva' => $iva ?? 0,
                        'activo' => $activoTxt === null ? true : $this->siNo($activoTxt),
                    ]);
                    $creados++;
                    continue;
                }

                $cambio = $articulo->actualizarPrecioCosto($precio, $costo, $userId, 'importacion');
                $otros = array_filter([
                    'descripcion' => $descripcion !== '' ? $descripcion : null,
                    'tipo' => $tipo !== '' ? $tipo : null,
                    'tasa_iva' => $iva,
                    'activo' => $activoTxt === null ? null : $this->siNo($activoTxt),
                ], fn ($v) => $v !== null);
                $articulo->fill($otros);
                if ($articulo->isDirty()) {
                    $articulo->save();
                    $cambio = true;
                }
                if ($cambio) {
                    $actualizados++;
                }
            }
        });

        $mensaje = "Importación lista: {$creados} artículos nuevos, {$actualizados} actualizados.";

        return redirect()->route('catalogo.importar')
            ->with('ok', $mensaje)
            ->with('errores_importacion', $errores);
    }

    private function validar(Request $request, ?Articulo $articulo = null): array
    {
        $datos = $request->validate([
            'clave' => ['required', 'string', 'max:30', Rule::unique('articulos', 'clave')->ignore($articulo?->id)],
            'descripcion' => ['required', 'string', 'max:255'],
            'tipo' => ['required', 'string', 'max:60'],
            'precio' => ['required', 'numeric', 'min:0', 'max:999999'],
            'costo' => ['required', 'numeric', 'min:0', 'max:999999'],
            'tasa_iva' => ['required', Rule::in(['0', '8', '16'])],
        ], [], ['descripcion' => 'descripción', 'tipo' => 'tipo o familia', 'tasa_iva' => 'IVA']);

        $datos['clave'] = mb_strtoupper(trim($datos['clave']));
        $datos['activo'] = $request->boolean('activo');

        return $datos;
    }

    private function tipos()
    {
        return Articulo::query()->distinct()->orderBy('tipo')->pluck('tipo');
    }

    private function siguienteClave(): string
    {
        $max = 0;
        foreach (Articulo::where('clave', 'like', 'PLU%')->pluck('clave') as $c) {
            $n = (int) preg_replace('/\D/', '', $c);
            $max = max($max, $n);
        }

        return 'PLU'.str_pad((string) ($max + 1), 4, '0', STR_PAD_LEFT);
    }

    private function numero($v): ?float
    {
        if ($v === null || $v === '') {
            return null;
        }
        if (is_numeric($v)) {
            return (float) $v;
        }
        $limpio = str_replace(['$', ',', ' '], '', (string) $v);

        return is_numeric($limpio) ? (float) $limpio : null;
    }

    private function siNo($v): bool
    {
        $v = mb_strtolower(trim((string) $v));

        return ! in_array($v, ['no', 'n', '0', 'false', 'inactivo'], true);
    }
}
