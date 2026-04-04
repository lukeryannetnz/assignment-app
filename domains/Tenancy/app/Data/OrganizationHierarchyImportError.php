<?php

declare(strict_types=1);

namespace App\Domains\Tenancy\Data;

use JsonSerializable;

class OrganizationHierarchyImportError implements JsonSerializable
{
    public function __construct(
        public readonly int $rowNumber,
        public readonly string $field,
        public readonly string $message,
    ) {
    }

    /**
     * @return array{
     *     row_number: int,
     *     field: string,
     *     message: string
     * }
     */
    public function toArray(): array
    {
        return [
            'row_number' => $this->rowNumber,
            'field' => $this->field,
            'message' => $this->message,
        ];
    }

    /**
     * @return array{
     *     row_number: int,
     *     field: string,
     *     message: string
     * }
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
