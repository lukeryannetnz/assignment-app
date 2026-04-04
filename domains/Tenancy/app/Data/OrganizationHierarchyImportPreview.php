<?php

declare(strict_types=1);

namespace App\Domains\Tenancy\Data;

use JsonSerializable;

class OrganizationHierarchyImportPreview implements JsonSerializable
{
    /**
     * @param  list<OrganizationHierarchyImportRow>  $rows
     * @param  list<OrganizationHierarchyImportError>  $errors
     */
    public function __construct(
        public readonly array $rows,
        public readonly array $errors,
    ) {
    }

    public function canCommit(): bool
    {
        return $this->errors === [];
    }

    /**
     * @return array{
     *     can_commit: bool,
     *     rows: list<array{
     *         row_number: int,
     *         row_key: string,
     *         parent_row_key: string|null,
     *         node_type: string|null,
     *         name: string,
     *         resolved_depth: int|null
     *     }>,
     *     errors: list<array{
     *         row_number: int,
     *         field: string,
     *         message: string
     *     }>
     * }
     */
    public function toArray(): array
    {
        return [
            'can_commit' => $this->canCommit(),
            'rows' => array_map(
                static fn (OrganizationHierarchyImportRow $row): array => $row->toArray(),
                $this->rows,
            ),
            'errors' => array_map(
                static fn (OrganizationHierarchyImportError $error): array => $error->toArray(),
                $this->errors,
            ),
        ];
    }

    /**
     * @return array{
     *     can_commit: bool,
     *     rows: list<array{
     *         row_number: int,
     *         row_key: string,
     *         parent_row_key: string|null,
     *         node_type: string|null,
     *         name: string,
     *         resolved_depth: int|null
     *     }>,
     *     errors: list<array{
     *         row_number: int,
     *         field: string,
     *         message: string
     *     }>
     * }
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
