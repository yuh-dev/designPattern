<?php
declare(strict_types=1);

namespace App\Export\Model;

/**
 * Value Object: dữ liệu cần xuất (giống Point trong bài toán khoảng cách).
 * Bất biến, tự kiểm tra tính hợp lệ khi khởi tạo.
 */
final class ExportData
{
    /**
     * @param string[]                         $headers
     * @param array<int, array<int, scalar|null>> $rows
     */
    public function __construct(
        private readonly string $title,
        private readonly array $headers,
        private readonly array $rows,
    ) {
        if ($headers === []) {
            throw new \InvalidArgumentException('Headers không được rỗng');
        }
        foreach ($rows as $i => $row) {
            if (count($row) !== count($headers)) {
                throw new \InvalidArgumentException("Dòng #$i có số cột khác số header");
            }
        }
    }

    public function getTitle(): string { return $this->title; }
    /** @return string[] */
    public function getHeaders(): array { return $this->headers; }
    /** @return array<int, array<int, scalar|null>> */
    public function getRows(): array { return $this->rows; }
    public function countRows(): int { return count($this->rows); }
}
