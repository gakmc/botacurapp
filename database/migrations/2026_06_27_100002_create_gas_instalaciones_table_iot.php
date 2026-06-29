<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGasInstalacionesTableIot extends Migration
{
    /**
     * Tabla gas_instalaciones (BD IoT: botacura_iot / conexión mysql_iot).
     *
     * Registra el historial operativo de cambios de cilindros por sector.
     * Calcula automáticamente los días de duración del cilindro anterior.
     *
     * Sectores: tinaja_1 | tinaja_2 | gas_casa | gas_cocina
     *
     * Modelo: App\Models\GasInstalacion
     * Usado por: GasIotController (operación instalacion_cilindro)
     *            HA button "Registrar gas nuevo"
     *
     * Campo contador_anterior_valor:
     *   - Para tinaja_1 / tinaja_2: horas de ENCENDIDO REAL del calefont.
     *     Acumulado por automation HA que detecta variación de temperatura
     *     en sensor.sonoff_10012a25d8_temperature (T1) / sonoff_10012a3d29_temperature (T2).
     *     Unidad: 'horas'. No incluye tiempo de calentamiento pasivo.
     *   - Para gas_casa / gas_cocina: días en uso (por fecha de instalación).
     *     Unidad: 'dias'.
     */
    public function up()
    {
        Schema::connection('mysql_iot')->create('gas_instalaciones', function (Blueprint $table) {
            $table->increments('id');

            // Sector donde se instaló el cilindro
            $table->enum('lugar', ['tinaja_1', 'tinaja_2', 'gas_casa', 'gas_cocina']);

            // Fecha de la nueva instalación
            $table->dateTime('fecha_instalacion');

            // Datos del cilindro anterior (para calcular duración)
            $table->dateTime('fecha_instalacion_anterior')->nullable();
            $table->integer('dias_duracion_anterior')->nullable()->comment('Días que duró el cilindro anterior');

            // Datos del cilindro instalado
            $table->integer('valor_cilindro_clp')->nullable();
            $table->decimal('kg_cilindro', 5, 2)->nullable();

            // Proveedor y documento
            $table->string('proveedor_nombre', 150)->nullable();
            $table->string('documento', 120)->nullable();

            // Referencia cruzada a la compra y egreso en BD principal
            // (no FK porque son BDs distintas)
            $table->unsignedInteger('gas_compra_id')->nullable()->comment('ID en gas_compras (BD principal)');
            $table->unsignedInteger('egreso_id')->nullable()->comment('ID en egresos (BD principal)');

            // Contador de gas (si el sector tiene medidor)
            $table->decimal('contador_anterior_valor', 10, 2)->nullable();
            $table->string('contador_anterior_unidad', 30)->nullable()->comment('m3 | kg | bar');

            // Trazabilidad
            $table->enum('origen', ['ho