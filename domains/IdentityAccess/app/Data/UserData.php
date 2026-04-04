<?php

declare(strict_types=1);

namespace App\Domains\IdentityAccess\Data;

use Carbon\CarbonImmutable;
use JsonSerializable;

final class UserData implements JsonSerializable
{
    public function __construct(
        public readonly int $id,
        public readonly ?int $tenant_id,
        public readonly string $name,
        public readonly string $email,
        public readonly ?CarbonImmutable $email_verified_at,
        public readonly bool $is_student,
        public readonly bool $is_admin,
        public readonly CarbonImmutable $created_at,
        public readonly CarbonImmutable $updated_at,
    ) {
    }

    /**
     * @param  object{
     *     id: int,
     *     tenant_id: int|null,
     *     name: string,
     *     email: string,
     *     email_verified_at: string|null,
     *     is_student: int|bool,
     *     is_admin: int|bool,
     *     created_at: string,
     *     updated_at: string
     * }  $row
     */
    public static function fromRow(object $row): self
    {
        return new self(
            id: (int) $row->id,
            tenant_id: $row->tenant_id !== null ? (int) $row->tenant_id : null,
            name: (string) $row->name,
            email: (string) $row->email,
            email_verified_at: $row->email_verified_at !== null
                ? CarbonImmutable::parse((string) $row->email_verified_at)
                : null,
            is_student: (bool) $row->is_student,
            is_admin: (bool) $row->is_admin,
            created_at: CarbonImmutable::parse((string) $row->created_at),
            updated_at: CarbonImmutable::parse((string) $row->updated_at),
        );
    }

    /**
     * @return array{
     *     id: int,
     *     tenant_id: int|null,
     *     name: string,
     *     email: string,
     *     email_verified_at: string|null,
     *     is_student: bool,
     *     is_admin: bool,
     *     created_at: string,
     *     updated_at: string
     * }
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'name' => $this->name,
            'email' => $this->email,
            'email_verified_at' => $this->email_verified_at?->toDateTimeString(),
            'is_student' => $this->is_student,
            'is_admin' => $this->is_admin,
            'created_at' => $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at->toDateTimeString(),
        ];
    }

    /**
     * @return array{
     *     id: int,
     *     tenant_id: int|null,
     *     name: string,
     *     email: string,
     *     email_verified_at: string|null,
     *     is_student: bool,
     *     is_admin: bool,
     *     created_at: string,
     *     updated_at: string
     * }
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
