<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class BotUserSeeder extends Seeder
{
    /**
     * Crea el usuario sistema que se asigna como user_id
     * en las reservas creadas por el chatbot de WhatsApp/Instagram.
     *
     * Ejecutar con:
     *   php artisan db:seed --class=BotUserSeeder
     *
     * El ID de este usuario debe coincidir con BOT_SYSTEM_USER_ID en .env
     */
    public function run()
    {
        $email = 'bot@botacura.cl';

        $existe = DB::table('users')->where('email', $email)->exists();

        if ($existe) {
            $this->command->info('[BotUserSeeder] Usuario bot ya existe. Omitiendo.');
            return;
        }

        $id = DB::table('users')->insertGetId([
            'name'              => 'Bot-Acura',
            'email'             => $email,
            'password'          => Hash::make(\Illuminate\Support\Str::random(32)),
            'email_verified_at' => now(),
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);

        $this->command->info("[BotUserSeeder] Usuario bot creado con ID: {$id}");
        $this->command->info("[BotUserSeeder] Agrega a tu .env: BOT_SYSTEM_USER_ID={$id}");
    }
}
