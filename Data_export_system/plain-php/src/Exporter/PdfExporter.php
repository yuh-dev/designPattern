<?php
declare(strict_types=1);

namespace App\Export\Exporter;

use App\Export\Contract\Exporter;
use App\Export\Model\ExportData;

/**
 * STRATEGY: xuất PDF tối giản (tự sinh cấu trúc PDF 1.4, font Courier).
 * Font chuẩn PDF không có dấu tiếng Việt nên văn bản được bỏ dấu.
 * Giới hạn số dòng (PDF dạng bảng rất nặng) để minh họa fallback.
 */
final class PdfExporter extends AbstractExporter
{
    private const LINES_PER_PAGE = 50;

    public function __construct(
        ?Exporter $fallback = null,
        private readonly int $maxRows = 1_000,
    ) {
        parent::__construct($fallback);
    }

    protected function exec(ExportData $data): string
    {
        if ($data->countRows() > $this->maxRows) {
            throw new \OverflowException("PDF chỉ hỗ trợ tối đa {$this->maxRows} dòng, nhận {$data->countRows()}");
        }

        // 1. Dựng các dòng văn bản dạng bảng có căn cột
        $table  = array_merge([$data->getHeaders()], $data->getRows());
        $table  = array_map(fn ($r) => array_map(fn ($c) => self::ascii($this->stringify($c)), $r), $table);
        $widths = [];
        foreach ($table as $r) {
            foreach ($r as $i => $c) {
                $widths[$i] = max($widths[$i] ?? 0, strlen($c));
            }
        }
        $fmt   = fn (array $r) => implode(' | ', array_map(fn ($c, $i) => str_pad($c, $widths[$i]), $r, array_keys($r)));
        $lines = [self::ascii($data->getTitle()), '', $fmt($table[0]), str_repeat('-', strlen($fmt($table[0])))];
        foreach (array_slice($table, 1) as $r) {
            $lines[] = $fmt($r);
        }

        // 2. Ghi cấu trúc PDF: catalog, pages, font, mỗi trang = page + content stream
        $pages   = array_chunk($lines, self::LINES_PER_PAGE);
        $objects = [];
        $objects[1] = '<< /Type /Catalog /Pages 2 0 R >>';
        $objects[3] = '<< /Type /Font /Subtype /Type1 /BaseFont /Courier >>';
        $kids = [];
        foreach ($pages as $i => $pageLines) {
            $pageId    = 4 + $i * 2;
            $contentId = $pageId + 1;
            $kids[]    = "$pageId 0 R";
            $stream    = "BT /F1 9 Tf 11 TL 36 806 Td\n";
            foreach ($pageLines as $l) {
                $stream .= '(' . strtr($l, ['\\' => '\\\\', '(' => '\\(', ')' => '\\)']) . ") Tj T*\n";
            }
            $stream .= 'ET';
            $objects[$pageId]    = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] "
                                 . "/Resources << /Font << /F1 3 0 R >> >> /Contents $contentId 0 R >>";
            $objects[$contentId] = '<< /Length ' . strlen($stream) . " >>\nstream\n$stream\nendstream";
        }
        $objects[2] = '<< /Type /Pages /Kids [' . implode(' ', $kids) . '] /Count ' . count($kids) . ' >>';
        ksort($objects);

        $pdf     = "%PDF-1.4\n";
        $offsets = [];
        foreach ($objects as $id => $body) {
            $offsets[$id] = strlen($pdf);
            $pdf .= "$id 0 obj\n$body\nendobj\n";
        }
        $xref = strlen($pdf);
        $pdf .= 'xref' . "\n0 " . (count($objects) + 1) . "\n0000000000 65535 f \n";
        foreach ($offsets as $off) {
            $pdf .= sprintf("%010d 00000 n \n", $off);
        }
        $pdf .= 'trailer << /Size ' . (count($objects) + 1) . " /Root 1 0 R >>\nstartxref\n$xref\n%%EOF\n";

        return $pdf;
    }

    /** Bỏ dấu tiếng Việt -> ASCII để font chuẩn PDF hiển thị được. */
    private static function ascii(string $s): string
    {
        $map = [
            'a' => 'àáạảãâầấậẩẫăằắặẳẵ', 'e' => 'èéẹẻẽêềếệểễ', 'i' => 'ìíịỉĩ',
            'o' => 'òóọỏõôồốộổỗơờớợởỡ', 'u' => 'ùúụủũưừứựửữ', 'y' => 'ỳýỵỷỹ', 'd' => 'đ',
        ];
        foreach ($map as $plain => $chars) {
            foreach (mb_str_split($chars) as $ch) {
                $s = str_replace([$ch, mb_strtoupper($ch)], [$plain, strtoupper($plain)], $s);
            }
        }
        return preg_replace('/[^\x20-\x7E]/', '?', $s);
    }

    protected function mimeType(): string { return 'application/pdf'; }
    protected function extension(): string { return 'pdf'; }
}
