<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ventas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');      // vendedor
            $table->dateTime('fecha')->index();
            $table->string('forma_pago', 20)->default('efectivo');   // efectivo | tarjeta | transferencia
            $table->decimal('total', 12, 2)->default(0);             // suma de importes (neto)
            $table->decimal('costo_total', 12, 2)->default(0);
            $table->decimal('pago_con', 12, 2)->nullable();          // efectivo recibido
            $table->boolean('cancelada')->default(false)->index();
            $table->foreignId('cancelada_por')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('cancelada_en')->nullable();
            $table->string('motivo_cancelacion')->nullable();
            $table->timestamps();
        });

        // Se guarda clave, descripción, precio y costo AL MOMENTO de la venta,
        // para que los cambios de precio no alteren las ventas pasadas.
        Schema::create('venta_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venta_id')->constrained('ventas')->cascadeOnDelete();
            $table->foreignId('articulo_id')->nullable()->constrained('articulos')->nullOnDelete();
            $table->string('clave', 30)->index();
            $table->string('descripcion');
            $table->string('tipo', 60);
            $table->decimal('cantidad', 12, 3);
            $table->decimal('precio', 12, 2);
            $table->decimal('costo', 12, 2);
            $table->decimal('tasa_iva', 5, 2)->default(0);
            $table->decimal('importe', 12, 2);        // cantidad * precio
            $table->decimal('costo_importe', 12, 2);  // cantidad * costo
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('venta_detalles');
        Schema::dropIfExists('ventas');
    }
};
