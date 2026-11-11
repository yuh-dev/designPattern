<?php
declare(strict_types=1);

namespace App\Export\Model;

/**
 * Kết quả xuất: nội dung nhị phân + thông tin định dạng + metadata
 * (metadata được các Decorator bổ sung: nén, mã hóa, chữ ký...).
 */
final class ExportResult
{
    /** @param array<string, mixed> $metadata */
    public function __construct(
        public readonly string $content,
        public readonly string $mimeType,
        public readonly string $extension,
        public readonly array $metadata = [],
    ) {}

    /** Trả về bản sao với nội dung/đuôi file mới - giữ bất biến. */
    public function with(
        ?string $content = null,
        ?string $mimeType = null,
        ?string $extension = null,
        array $extraMetadata = [],
    ): self {
        return new self(
            $content ?? $this->content,
            $mimeType ?? $this->mimeType,
            $extension ?? $this->extension,
            array_merge($this->metadata, $extraMetadata),
        );
    }
}
