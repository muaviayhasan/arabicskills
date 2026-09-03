<?php

namespace App\Support;

class BackfillConfig
{
    public function __construct(
        public string $table,
        public string $idColumn = 'id',
        public string $levelStringColumn = 'level',
        public string $levelIdColumn = 'level_id',
        public int $chunkSize = 500,
        public bool $supplementFromDistinctLevels = true,
    ) {}

    public static function forStudents(int $chunkSize = 500): self
    {
        return new self(
            table: 'students',
            chunkSize: $chunkSize,
        );
    }
}
