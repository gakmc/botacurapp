<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Reduce fotos (normalmente de celular) con GD: corrige la rotación EXIF,
 * limita el lado mayor y las deja en JPEG. Si GD no puede leer la imagen,
 * se devuelve o guarda tal cual.
 */
class ImagenService
{
    /** Devuelve [bytes, mime] de la imagen reducida. $abs = ruta absoluta. */
    public function reducir($abs, $mime, $ladoMax, $calidad = 85)
    {
        $info = @getimagesize($abs);
        if (! $info || ! function_exists('imagecreatefromstring')) return [file_get_contents($abs), $mime];
        list($w, $h) = $info;
        $img = @imagecreatefromstring(file_get_contents($abs));
        if (! $img) return [file_get_contents($abs), $mime];
        if (function_exists('exif_read_data') && $mime === 'image/jpeg') {
            $o = (@exif_read_data($abs)['Orientation'] ?? 1);
            if ($o == 3) $img = imagerotate($img, 180, 0);
            if ($o == 6) { $img = imagerotate($img, -90, 0); list($w, $h) = [$h, $w]; }
            if ($o == 8) { $img = imagerotate($img, 90, 0); list($w, $h) = [$h, $w]; }
        }
        $f = min(1, $ladoMax / max($w, $h));
        if ($f < 1) {
            $nueva = imagecreatetruecolor((int) ($w * $f), (int) ($h * $f));
            imagecopyresampled($nueva, $img, 0, 0, 0, 0, (int) ($w * $f), (int) ($h * $f), $w, $h);
            imagedestroy($img);
            $img = $nueva;
        }
        ob_start();
        imagejpeg($img, null, $calidad);
        imagedestroy($img);
        return [ob_get_clean(), 'image/jpeg'];
    }

    /** Guarda la foto reducida en storage/app/$carpeta (disco privado) y devuelve la ruta relativa. */
    public function guardarReducida(UploadedFile $archivo, $carpeta, $ladoMax = 1600, $calidad = 80)
    {
        list($datos, $mime) = $this->reducir($archivo->getRealPath(), $archivo->getMimeType(), $ladoMax, $calidad);

        $extension = $mime === 'image/jpeg' ? 'jpg' : ($archivo->guessExtension() ?: 'jpg');
        $ruta = trim($carpeta, '/') . '/' . uniqid('', true) . '.' . $extension;
        Storage::put($ruta, $datos);

        return $ruta;
    }
}
