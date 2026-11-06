<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Exporta tablas a Excel (.xlsx) o CSV y lee archivos de Excel/CSV.
 */
class Hoja
{
    /**
     * @param  array<string>  $encabezados
     * @param  iterable<array>  $filas
     * @param  array<int>  $columnasMoneda  índices (base 0) con formato de dinero
     */
    public static function descargar(string $nombre, string $formato, array $encabezados, iterable $filas, array $columnasMoneda = [], string $titulo = 'Datos'): StreamedResponse
    {
        if ($formato === 'csv') {
            return response()->streamDownload(function () use ($encabezados, $filas) {
                $out = fopen('php://output', 'w');
                fwrite($out, "\xEF\xBB\xBF"); // BOM para que Excel respete acentos
                fputcsv($out, $encabezados);
                foreach ($filas as $f) {
                    fputcsv($out, array_values($f));
                }
                fclose($out);
            }, $nombre.'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
        }

        $libro = new Spreadsheet;
        $hoja = $libro->getActiveSheet();
        $hoja->setTitle(mb_substr($titulo, 0, 31));
        $hoja->fromArray($encabezados, null, 'A1');

        $r = 2;
        foreach ($filas as $f) {
            $hoja->fromArray(array_values($f), null, 'A'.$r, true);
            $r++;
        }

        $ultimaCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($encabezados));
        $hoja->getStyle('A1:'.$ultimaCol.'1')->getFont()->setBold(true);
        $hoja->getStyle('A1:'.$ultimaCol.'1')->getFill()
            ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E8E2D6');

        foreach ($columnasMoneda as $c) {
            $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c + 1);
            $hoja->getStyle($col.'2:'.$col.max(2, $r - 1))->getNumberFormat()->setFormatCode('#,##0.00');
        }
        for ($i = 1; $i <= count($encabezados); $i++) {
            $hoja->getColumnDimensionByColumn($i)->setAutoSize(true);
        }
        $hoja->freezePane('A2');

        return response()->streamDownload(function () use ($libro) {
            IOFactory::createWriter($libro, 'Xlsx')->save('php://output');
        }, $nombre.'.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Lee la primera hoja y regresa filas como arreglos asociativos
     * con encabezados en minúsculas y sin acentos.
     */
    public static function leer(string $ruta): array
    {
        $libro = IOFactory::load($ruta);
        $filas = $libro->getActiveSheet()->toArray(null, true, false, false);
        if (! $filas) {
            return [];
        }

        $encabezados = array_map(fn ($h) => self::normalizar((string) $h), array_shift($filas));
        $resultado = [];
        foreach ($filas as $fila) {
            if (count(array_filter($fila, fn ($v) => $v !== null && $v !== '')) === 0) {
                continue; // fila vacía
            }
            $asoc = [];
            foreach ($encabezados as $i => $h) {
                if ($h !== '') {
                    $asoc[$h] = $fila[$i] ?? null;
                }
            }
            $resultado[] = $asoc;
        }

        return $resultado;
    }

    public static function normalizar(string $texto): string
    {
        $texto = mb_strtolower(trim($texto));
        $texto = strtr($texto, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ñ' => 'n', 'ü' => 'u']);

        return preg_replace('/[^a-z0-9]+/', '_', $texto) ?? $texto;
    }
}
