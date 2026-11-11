<?php
declare(strict_types=1);

namespace App\Services\Export\Exporter;

use App\Services\Export\Contract\Exporter;
use App\Services\Export\Model\ExportData;

/**
 * STRATEGY: xuất Excel dạng SpreadsheetML 2003 (XML) - Excel/LibreOffice mở được,
 * không cần thư viện ngoài. Giới hạn số dòng để mô phỏng lỗi thực tế.
 */
final class ExcelExporter extends AbstractExporter
{
    public function __construct(
        ?Exporter $fallback = null,
        private readonly int $maxRows = 65_535,
    ) {
        parent::__construct($fallback);
    }

    protected function exec(ExportData $data): string
    {
        if ($data->countRows() > $this->maxRows) {
            throw new \OverflowException("Excel chỉ hỗ trợ tối đa {$this->maxRows} dòng, nhận {$data->countRows()}");
        }

        $xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<?mso-application progid="Excel.Sheet"?>' . "\n";
        $xml .= '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" '
              . 'xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">' . "\n";
        $xml .= '<Styles><Style ss:ID="h"><Font ss:Bold="1"/></Style></Styles>' . "\n";
        $xml .= '<Worksheet ss:Name="' . $this->esc(mb_substr($data->getTitle(), 0, 31)) . '"><Table>' . "\n";
        $xml .= $this->row($data->getHeaders(), 'h');
        foreach ($data->getRows() as $row) {
            $xml .= $this->row($row);
        }
        $xml .= "</Table></Worksheet>\n</Workbook>\n";

        return $xml;
    }

    private function row(array $cells, ?string $style = null): string
    {
        $out = '<Row>';
        foreach ($cells as $cell) {
            $type  = is_int($cell) || is_float($cell) ? 'Number' : 'String';
            $attr  = $style ? ' ss:StyleID="' . $style . '"' : '';
            $out  .= "<Cell$attr><Data ss:Type=\"$type\">" . $this->esc($this->stringify($cell)) . '</Data></Cell>';
        }
        return $out . "</Row>\n";
    }

    private function esc(string $s): string
    {
        return htmlspecialchars($s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    protected function mimeType(): string { return 'application/vnd.ms-excel'; }
    protected function extension(): string { return 'xls'; }
}
