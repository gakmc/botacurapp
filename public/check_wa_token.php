<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$token = env('META_WHATSAPP_TOKEN');
$url = "https://graph.facebook.com/debug_token?input_token={$token}&access_token={$token}";
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$res = curl_exec($ch);
curl_close($ch);
$data = json_decode($res, true);

$valid = $data['data']['is_valid'] ?? false;
$exp   = $data['data']['expires_at'] ?? 0;
$app_  = $data['data']['application'] ?? '?';
$type  = $data['data']['type'] ?? '?';

echo "✅ Válido: " . ($valid ? 'SÍ' : '❌ NO') . "\n";
echo "📅 Expira: " . ($exp ? date('Y-m-d H:i:s', $exp) : 'No expira') . "\n";
echo "🔑 App: $app_\n";
echo "🔑 Tipo: $type\n";
if (!$valid) {
    echo "❌ Error: " . ($data['data']['error']['message'] ?? json_encode($data)) . "\n";
}
