<?php
declare(strict_types=1);

namespace App\Services\Export\Model;

use Illuminate\Contracts\Support\Arrayable;

/**
 * Value Object: dữ liệu cần xuất (giống Point trong bài toán khoảng cách).
 * Bất biến, tự kiểm tra tính hợp lệ khi khởi tạo.
 */
final class ExportData
{
    /**
     * @param string[]                            $headers
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

    /**
     * Tạo từ Collection/Eloquent: chọn cột => nhãn hiển thị.
     *
     * @param iterable<mixed>       $items   Collection model hoặc mảng
     * @param array<string, string> $columns ['email' => 'Email', 'created_at' => 'Ngày tạo']
     */
    public static function fromIterable(string $title, iterable $items, array $columns): self
    {
        $rows = [];
        foreach ($items as $item) {
            $arr = $item instanceof Arrayable ? $item->toArray() : (array) $item;
            $rows[] = array_map(
                fn (string $key) => self::scalar(data_get($item, $key, $arr[$key] ?? null)),
                array_keys($columns),
            );
        }

        return new self($title, array_values($columns), $rows);
    }

    private static function scalar(mixed $v): string|int|float|bool|null
    {
        return match (true) {
            $v === null, is_scalar($v)       => $v,
            $v instanceof \DateTimeInterface => $v->format('Y-m-d H:i:s'),
            $v instanceof \BackedEnum        => $v->value,
            $v instanceof \Stringable        => (string) $v,
            default                          => json_encode($v, JSON_UNESCAPED_UNICODE),
        };
    }

    public function getTitle(): string { return $this->title; }
    /** @return string[] */
    public function getHeaders(): array { return $this->headers; }
    /** @return array<int, array<int, scalar|null>> */
    public function getRows(): array { return $this->rows; }
    public function countRows(): int { return count($this->rows); }
}
