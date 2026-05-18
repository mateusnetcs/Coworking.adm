<?php

namespace App\Support;

use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Encoder\ByteMatrix;
use BaconQrCode\Encoder\Encoder;

/**
 * QR para PDF: PNG via GD quando disponível; tabela HTML como fallback (sem GD/Imagick).
 */
final class QrCodeGenerator
{
    public static function tryPngBase64(string $text, int $size = 220, int $margin = 1, string $ecc = 'M'): ?string
    {
        if (! extension_loaded('gd')) {
            return null;
        }

        return base64_encode(self::toPngBinary($text, $size, $margin, $ecc));
    }

    public static function toHtmlTable(string $text, int $size = 180, int $margin = 1, string $ecc = 'M'): string
    {
        $matrix = self::encodeMatrix($text, $ecc);
        $matrixSize = $matrix->getWidth();
        $totalModules = $matrixSize + ($margin * 2);
        $cellSize = max(1, (int) floor($size / $totalModules));

        $rows = '';

        for ($y = 0; $y < $totalModules; $y++) {
            $cells = '';

            for ($x = 0; $x < $totalModules; $x++) {
                $moduleX = $x - $margin;
                $moduleY = $y - $margin;
                $isDark = $moduleX >= 0
                    && $moduleY >= 0
                    && $moduleX < $matrixSize
                    && $moduleY < $matrixSize
                    && $matrix->get($moduleX, $moduleY) === 1;

                $color = $isDark ? '#000000' : '#ffffff';
                $cells .= '<td style="width:'.$cellSize.'px;height:'.$cellSize.'px;background-color:'.$color.';padding:0;margin:0;line-height:0;font-size:0;"></td>';
            }

            $rows .= '<tr>'.$cells.'</tr>';
        }

        return '<table cellpadding="0" cellspacing="0" style="border-collapse:collapse;margin:0 auto;">'.$rows.'</table>';
    }

    private static function toPngBinary(string $text, int $size, int $margin, string $ecc): string
    {
        $matrix = self::encodeMatrix($text, $ecc);
        $matrixSize = $matrix->getWidth();
        $totalModules = $matrixSize + ($margin * 2);
        $moduleSize = max(1, (int) floor($size / $totalModules));
        $imageSize = $moduleSize * $totalModules;

        $image = imagecreatetruecolor($imageSize, $imageSize);
        if ($image === false) {
            return '';
        }

        $white = imagecolorallocate($image, 255, 255, 255);
        $black = imagecolorallocate($image, 0, 0, 0);
        imagefilledrectangle($image, 0, 0, $imageSize - 1, $imageSize - 1, $white);

        for ($y = 0; $y < $matrixSize; $y++) {
            for ($x = 0; $x < $matrixSize; $x++) {
                if ($matrix->get($x, $y) !== 1) {
                    continue;
                }

                $left = ($x + $margin) * $moduleSize;
                $top = ($y + $margin) * $moduleSize;
                imagefilledrectangle(
                    $image,
                    $left,
                    $top,
                    $left + $moduleSize - 1,
                    $top + $moduleSize - 1,
                    $black
                );
            }
        }

        ob_start();
        imagepng($image);
        imagedestroy($image);

        return (string) ob_get_clean();
    }

    private static function encodeMatrix(string $text, string $ecc): ByteMatrix
    {
        $ecLevel = match (strtoupper($ecc)) {
            'L' => ErrorCorrectionLevel::L(),
            'Q' => ErrorCorrectionLevel::Q(),
            'H' => ErrorCorrectionLevel::H(),
            default => ErrorCorrectionLevel::M(),
        };

        return Encoder::encode($text, $ecLevel)->getMatrix();
    }
}
