<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Catálogo de artículos con su precio y costo vigentes
        Schema::create('articulos', function (Blueprint $table) {
            $table->id();
            $table->string('clave', 30)->unique();          // PLU0001
            $table->string('descripcion');
            $table->string('tipo', 60)->index();            // familia: Pastel, Frappe, ...
            $table->decimal('precio', 12, 2)->default(0);   // precio neto (IVA incluido)
            $table->decimal('costo', 12, 2)->default(0);
            $table->decimal('tasa_iva', 5, 2)->default(0);  // 0 o 16 (para la póliza)
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        // Historial de cambios de precio y costo
        Schema::create('cambios_precio', function (Blueprint $table) {
            $table->id();
            $table->foreignId('articulo_id')->constrained('articulos')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('precio_anterior', 12, 2)->nullable();
            $table->decimal('precio_nuevo', 12, 2)->nullable();
            $table->decimal('costo_anterior', 12, 2)->nullable();
            $table->decimal('costo_nuevo', 12, 2)->nullable();
            $table->string('origen', 30)->default('manual'); // manual | masivo | importacion
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cambios_precio');
        Schema::dropIfExists('articulos');
    }
};
