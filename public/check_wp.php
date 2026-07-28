<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$existe = \Illuminate\Support\Facades\Schema::hasTable('webpay_pendientes');
echo $existe ? "✅ Tabla webpay_pendientes EXISTS" : "❌ Tabla webpay_pendientes NO existe";

if (!$existe) {
    \Illuminate\Support\Facades\Schema::create('webpay_pendientes', function ($table) {
        $table->increments('id');
        $table->string('webpay_token', 64)->unique();
        $table->string('webpay_orden', 26);
        $table->unsignedInteger('monto');
        $table->text('datos_json');
        $table->timestamps();
    });
    echo " → Creada ahora ✅";
}
