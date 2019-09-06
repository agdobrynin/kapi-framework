<?php

namespace kaspi\Migration;

interface MigrationInterface
{
    /**
     * скрипт внесения миграции.
     */
    public function up(): void;

    /**
     * скрипт удаления миграции.
     */
    public function down(): void;
}
