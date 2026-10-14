<?php

declare(strict_types=1);

function certificate_pdf(array $sheet): string
{
    $pageW = 841.89;
    $pageH = 595.28;
    $gold = '0.886 0.694 0.000';
    $red = '0.478 0.200 0.173';
    $ink = '0.227 0.078 0.071';
    $soft = '0.416 0.325 0.282';
    $flagRed = '0.871 0.161 0.063';
    $star = '1 0.871 0';
    $ops = [];
    $ops[] = "1 0.984 0.953 rg 0 0 $pageW $pageH re f";
    $ops[] = "$gold rg 0 0 18 $pageH re f";
    $ops[] = "$red rg " . ($pageW - 18) . " 0 18 $pageH re f";
    $frameX = 36.0;
    $frameY = 32.0;
    $frameW = $pageW - 72;
    $frameH = $pageH - 64;
    $ops[] = pdf_stroke($gold, 1.7, $frameX, $frameY, $frameW, $frameH);
    $ops[] = pdf_stroke($red, 0.6, $frameX + 8, $frameY + 8, $frameW - 16, $frameH - 16);
    $ops[] = pdf_corners($gold, 1.8, $frameX - 1, $frameY - 1, $frameW + 2, $frameH + 2, 16);
    $ops[] = pdf_corners($red, 0.9, $frameX + 8, $frameY + 8, $frameW - 16, $frameH - 16, 11);

    $yFlag = 492.0;
    $yKicker = 466.0;
    $yOrnament = 444.0;
    $yTitle = 396.0;
    $ySubtitle = 356.0;
    $yLead = 312.0;
    $yName = 268.0;
    $yRule = 250.0;
    $yBody = 218.0;
    $yFootRule = 176.0;
    $yLabel = 152.0;
    $yMeta = 132.0;
    $yNote = 104.0;
    $innerBottom = $frameY + 8;
    $innerTop = $innerBottom + ($frameH - 16);
    $shift = (($innerBottom + $innerTop) / 2) - ((($yFlag + 16) + ($yNote - 2)) / 2);
    $yFlag += $shift;
    $yKicker += $shift;
    $yOrnament += $shift;
    $yTitle += $shift;
    $ySubtitle += $shift;
    $yLead += $shift;
    $yName += $shift;
    $yRule += $shift;
    $yBody += $shift;
    $yFootRule += $shift;
    $yLabel += $shift;
    $yMeta += $shift;
    $yNote += $shift;

    $flagW = 54.0;
    $flagH = 36.0;
    $flagX = ($pageW - $flagW) / 2;
    $ops[] = pdf_fill($flagRed, $flagX, $yFlag - 8, $flagW, $flagH);
    $ops[] = pdf_star($star, $flagX + 16, $yFlag + 14, 7.2);
    $ops[] = pdf_star($star, $flagX + 32, $yFlag + 22, 2.4);
    $ops[] = pdf_star($star, $flagX + 36, $yFlag + 16, 2.4);
    $ops[] = pdf_star($star, $flagX + 36, $yFlag + 9, 2.4);
    $ops[] = pdf_star($star, $flagX + 32, $yFlag + 3, 2.4);
    $ops[] = pdf_stroke($ink, 0.6, $flagX, $yFlag - 8, $flagW, $flagH);
    $ops[] = pdf_centered($sheet['kicker'], 9.5, true, 2.1, $ink, $pageW, $yKicker);
    $ops[] = pdf_ornament($pageW / 2, $yOrnament, 92, $gold, $red);
    $ops[] = pdf_centered($sheet['title'], 34, true, 0.15, $ink, $pageW, $yTitle);
    $ops[] = pdf_flanked($sheet['subtitle'], 13, true, 0.8, $red, $soft, $pageW, $ySubtitle, 36);
    $ops[] = pdf_centered($sheet['lead'], 12, false, 0.15, $soft, $pageW, $yLead);
    $ops[] = pdf_centered($sheet['name'], 26, true, 0.2, $ink, $pageW, $yName);
    $ops[] = pdf_name_rule($sheet['name'], 26, 0.2, $gold, $pageW, $yRule);
    $ops[] = pdf_centered($sheet['body'], 12, false, 0.05, $ink, $pageW, $yBody);
    $ops[] = pdf_meta_block($sheet['code'], $sheet['date'], $red, $ink, $soft, $gold, $pageW, $yFootRule, $yLabel, $yMeta);
    $ops[] = pdf_centered('Sin validez oficial', 8, false, 0.6, $soft, $pageW, $yNote);

    $stream = implode("\n", $ops);
    $objects = [];
    $objects[] = '<< /Type /Catalog /Pages 2 0 R >>';
    $objects[] = '<< /Type /Pages /Kids [3 0 R] /Count 1 >>';
    $objects[] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 841.89 595.28] /Contents 4 0 R /Resources << /Font << /F1 5 0 R /F2 6 0 R >> >> >>';
    $objects[] = "<< /Length " . strlen($stream) . " >>\nstream\n" . $stream . "\nendstream";
    $objects[] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>';
    $objects[] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>';

    $pdf = "%PDF-1.4\n";
    $offsets = [0];
    foreach ($objects as $i => $body) {
        $offsets[] = strlen($pdf);
        $pdf .= ($i + 1) . " 0 obj\n" . $body . "\nendobj\n";
    }
    $xref = strlen($pdf);
    $count = count($objects) + 1;
    $pdf .= "xref\n0 $count\n0000000000 65535 f \n";
    for ($i = 1; $i < $count; $i++) {
        $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
    }
    $pdf .= "trailer\n<< /Size $count /Root 1 0 R >>\nstartxref\n$xref\n%%EOF";
    return $pdf;
}

function pdf_star(string $color, float $cx, float $cy, float $r): string
{
    $parts = [];
    for ($i = 0; $i < 5; $i++) {
        $outer = deg2rad(90 - ($i * 72));
        $inner = deg2rad(90 - 36 - ($i * 72));
        $parts[] = [$cx + cos($outer) * $r, $cy + sin($outer) * $r];
        $parts[] = [$cx + cos($inner) * $r * 0.38, $cy + sin($inner) * $r * 0.38];
    }
    $path = $color . ' rg';
    foreach ($parts as $index => $point) {
        $path .= ' ' . pdf_num($point[0]) . ' ' . pdf_num($point[1]) . ($index === 0 ? ' m' : ' l');
    }
    return $path . ' h f';
}

function pdf_num(float $value): string
{
    return number_format($value, 2, '.', '');
}

function pdf_fill(string $color, float $x, float $y, float $w, float $h): string
{
    return $color . ' rg ' . pdf_num($x) . ' ' . pdf_num($y) . ' ' . pdf_num($w) . ' ' . pdf_num($h) . ' re f';
}

function pdf_stroke(string $color, float $width, float $x, float $y, float $w, float $h): string
{
    return $color . ' RG ' . pdf_num($width) . ' w ' . pdf_num($x) . ' ' . pdf_num($y) . ' ' . pdf_num($w) . ' ' . pdf_num($h) . ' re S';
}

function pdf_centered(string $text, float $size, bool $bold, float $tracking, string $color, float $pageW, float $y): string
{
    $text = trim($text);
    if ($text === '') {
        return '';
    }
    $drawSize = $size;
    $width = pdf_text_width($text, $drawSize, $bold, $tracking);
    $max = 640.0;
    if ($width > $max && $width > 0) {
        $drawSize = $size * ($max / $width);
        $width = $max;
    }
    $x = ($pageW - $width) / 2;
    $font = $bold ? 'F2' : 'F1';
    return 'BT /' . $font . ' ' . pdf_num($drawSize) . ' Tf ' . $color . ' rg ' . pdf_num($tracking) . ' Tc 1 0 0 1 ' . pdf_num($x) . ' ' . pdf_num($y) . ' Tm ' . pdf_literal($text) . ' Tj ET';
}

function pdf_hline(string $color, float $width, float $x1, float $x2, float $y): string
{
    return $color . ' RG ' . pdf_num($width) . ' w ' . pdf_num($x1) . ' ' . pdf_num($y) . ' m ' . pdf_num($x2) . ' ' . pdf_num($y) . ' l S';
}

function pdf_diamond(string $color, float $cx, float $cy, float $r): string
{
    return $color . ' rg ' . pdf_num($cx) . ' ' . pdf_num($cy + $r) . ' m '
        . pdf_num($cx + $r) . ' ' . pdf_num($cy) . ' l '
        . pdf_num($cx) . ' ' . pdf_num($cy - $r) . ' l '
        . pdf_num($cx - $r) . ' ' . pdf_num($cy) . ' l h f';
}

function pdf_corners(string $color, float $stroke, float $x, float $y, float $w, float $h, float $arm): string
{
    $paths = [
        pdf_num($x) . ' ' . pdf_num($y + $arm) . ' m ' . pdf_num($x) . ' ' . pdf_num($y) . ' l ' . pdf_num($x + $arm) . ' ' . pdf_num($y) . ' l',
        pdf_num($x + $w - $arm) . ' ' . pdf_num($y) . ' m ' . pdf_num($x + $w) . ' ' . pdf_num($y) . ' l ' . pdf_num($x + $w) . ' ' . pdf_num($y + $arm) . ' l',
        pdf_num($x) . ' ' . pdf_num($y + $h - $arm) . ' m ' . pdf_num($x) . ' ' . pdf_num($y + $h) . ' l ' . pdf_num($x + $arm) . ' ' . pdf_num($y + $h) . ' l',
        pdf_num($x + $w - $arm) . ' ' . pdf_num($y + $h) . ' m ' . pdf_num($x + $w) . ' ' . pdf_num($y + $h) . ' l ' . pdf_num($x + $w) . ' ' . pdf_num($y + $h - $arm) . ' l',
    ];
    return $color . ' RG ' . pdf_num($stroke) . " w 1 J 1 j\n" . implode("\n", $paths) . "\nS";
}

function pdf_ornament(float $cx, float $y, float $half, string $lineColor, string $diamondColor): string
{
    $gap = 9.0;
    return pdf_hline($lineColor, 0.7, $cx - $half, $cx - $gap, $y)
        . "\n" . pdf_diamond($diamondColor, $cx, $y, 3.2)
        . "\n" . pdf_hline($lineColor, 0.7, $cx + $gap, $cx + $half, $y);
}

function pdf_flanked(string $text, float $size, bool $bold, float $tracking, string $color, string $ruleColor, float $pageW, float $y, float $ruleLen): string
{
    $text = trim($text);
    $width = pdf_text_width($text, $size, $bold, $tracking);
    $x = ($pageW - $width) / 2;
    $mid = $y + ($size * 0.32);
    $gap = 11.0;
    return pdf_hline($ruleColor, 0.6, $x - $gap - $ruleLen, $x - $gap, $mid)
        . "\n" . pdf_centered($text, $size, $bold, $tracking, $color, $pageW, $y)
        . "\n" . pdf_hline($ruleColor, 0.6, $x + $width + $gap, $x + $width + $gap + $ruleLen, $mid);
}

function pdf_name_rule(string $name, float $size, float $tracking, string $color, float $pageW, float $y): string
{
    $width = pdf_text_width(trim($name), $size, true, $tracking);
    $rule = min(320.0, max(168.0, $width + 28));
    $x1 = ($pageW - $rule) / 2;
    $x2 = $x1 + $rule;
    return pdf_hline($color, 0.8, $x1, $x2, $y)
        . "\n" . pdf_diamond($color, $x1, $y, 2.4)
        . "\n" . pdf_diamond($color, $x2, $y, 2.4);
}

function pdf_meta_block(string $code, string $date, string $codeColor, string $dateColor, string $labelColor, string $ruleColor, float $pageW, float $ruleY, float $labelY, float $valueY): string
{
    $valueSize = 12.0;
    $labelSize = 7.5;
    $tracking = 0.45;
    $codeWidth = pdf_text_width($code, $valueSize, true, $tracking);
    $dateWidth = pdf_text_width($date, $valueSize, false, 0);
    $serie = 'Serie';
    $fecha = 'Fecha';
    $serieWidth = pdf_text_width($serie, $labelSize, true, 1.3);
    $fechaWidth = pdf_text_width($fecha, $labelSize, true, 1.3);
    $col = max($codeWidth, $dateWidth, $serieWidth, $fechaWidth);
    $gutter = 44.0;
    $diamond = 3.0;
    $total = ($col * 2) + $gutter;
    $x = ($pageW - $total) / 2;
    $codeX = $x + (($col - $codeWidth) / 2);
    $dateX = $x + $col + $gutter + (($col - $dateWidth) / 2);
    $diamondX = $x + $col + ($gutter / 2);
    $rulePad = 28.0;
    $ops = [
        pdf_hline($ruleColor, 0.6, $x - $rulePad, $x + $total + $rulePad, $ruleY),
        pdf_diamond($ruleColor, $diamondX, $ruleY, 2.6),
        pdf_at($serie, $labelSize, true, 1.3, $labelColor, $codeX + (($codeWidth - $serieWidth) / 2), $labelY),
        pdf_at($fecha, $labelSize, true, 1.3, $labelColor, $dateX + (($dateWidth - $fechaWidth) / 2), $labelY),
        pdf_at($code, $valueSize, true, $tracking, $codeColor, $codeX, $valueY),
        pdf_diamond($codeColor, $diamondX, $valueY + 4.2, $diamond),
        pdf_at($date, $valueSize, false, 0, $dateColor, $dateX, $valueY),
    ];
    return implode("\n", $ops);
}

function pdf_at(string $text, float $size, bool $bold, float $tracking, string $color, float $x, float $y): string
{
    $text = trim($text);
    if ($text === '') {
        return '';
    }
    $font = $bold ? 'F2' : 'F1';
    return 'BT /' . $font . ' ' . pdf_num($size) . ' Tf ' . $color . ' rg ' . pdf_num($tracking) . ' Tc 1 0 0 1 ' . pdf_num($x) . ' ' . pdf_num($y) . ' Tm ' . pdf_literal($text) . ' Tj ET';
}

function send_certificate_pdf(string $filename, string $pdf): void
{
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="' . $filename . '"');
    header('Cache-Control: no-store');
    header('Content-Length: ' . (string) strlen($pdf));
    echo $pdf;
    exit;
}

function pdf_literal(string $text): string
{
    $encoded = iconv('UTF-8', 'Windows-1252//TRANSLIT', $text);
    if ($encoded === false) {
        $encoded = $text;
    }
    return '(' . str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $encoded) . ')';
}

function pdf_text_width(string $text, float $size, bool $bold, float $tracking): float
{
    $encoded = iconv('UTF-8', 'Windows-1252//TRANSLIT', $text);
    if ($encoded === false) {
        $encoded = $text;
    }
    $units = 0;
    $chars = 0;
    $length = strlen($encoded);
    for ($i = 0; $i < $length; $i++) {
        $units += pdf_glyph_width(ord($encoded[$i]), $bold);
        $chars++;
    }
    $glyph = $units * $size / 1000;
    $extra = $chars > 1 ? ($chars - 1) * $tracking : 0;
    return $glyph + $extra;
}

function pdf_glyph_width(int $code, bool $bold): int
{
    static $regular = [
        32 => 278, 45 => 333, 46 => 278, 47 => 278,
        48 => 556, 49 => 556, 50 => 556, 51 => 556, 52 => 556, 53 => 556, 54 => 556, 55 => 556, 56 => 556, 57 => 556,
        65 => 667, 66 => 667, 67 => 722, 68 => 722, 69 => 611, 70 => 556, 71 => 778, 72 => 722, 73 => 278, 74 => 500,
        75 => 667, 76 => 556, 77 => 833, 78 => 722, 79 => 778, 80 => 667, 81 => 778, 82 => 722, 83 => 667, 84 => 611,
        85 => 722, 86 => 667, 87 => 944, 88 => 667, 89 => 667, 90 => 611,
        97 => 556, 98 => 556, 99 => 500, 100 => 556, 101 => 556, 102 => 278, 103 => 556, 104 => 556, 105 => 222,
        106 => 222, 107 => 500, 108 => 222, 109 => 833, 110 => 556, 111 => 556, 112 => 556, 113 => 556, 114 => 333,
        115 => 500, 116 => 278, 117 => 556, 118 => 500, 119 => 722, 120 => 500, 121 => 500, 122 => 500,
        193 => 667, 201 => 611, 205 => 278, 211 => 778, 218 => 722, 220 => 722, 209 => 722,
        225 => 556, 233 => 556, 237 => 222, 243 => 556, 250 => 556, 252 => 556, 241 => 556,
    ];
    static $heavy = [
        32 => 278, 45 => 333, 46 => 278,
        48 => 556, 49 => 556, 50 => 556, 51 => 556, 52 => 556, 53 => 556, 54 => 556, 55 => 556, 56 => 556, 57 => 556,
        65 => 722, 66 => 722, 67 => 722, 68 => 722, 69 => 667, 70 => 611, 71 => 778, 72 => 722, 73 => 278, 74 => 556,
        75 => 722, 76 => 611, 77 => 833, 78 => 722, 79 => 778, 80 => 667, 81 => 778, 82 => 722, 83 => 667, 84 => 611,
        85 => 722, 86 => 667, 87 => 944, 88 => 667, 89 => 667, 90 => 611,
        97 => 556, 98 => 611, 99 => 556, 100 => 611, 101 => 556, 102 => 333, 103 => 611, 104 => 611, 105 => 278,
        106 => 278, 107 => 556, 108 => 278, 109 => 889, 110 => 611, 111 => 611, 112 => 611, 113 => 611, 114 => 389,
        115 => 556, 116 => 333, 117 => 611, 118 => 556, 119 => 778, 120 => 556, 121 => 556, 122 => 500,
        193 => 722, 201 => 667, 205 => 278, 211 => 778, 218 => 722, 220 => 722, 209 => 722,
        225 => 556, 233 => 556, 237 => 278, 243 => 611, 250 => 611, 252 => 611, 241 => 611,
    ];
    $table = $bold ? $heavy : $regular;
    return $table[$code] ?? ($bold ? 600 : 500);
}
