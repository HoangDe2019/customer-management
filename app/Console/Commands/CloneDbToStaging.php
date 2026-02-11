<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CloneDbToStaging extends Command
{
    protected $signature = 'db:clone-to-staging';

    protected $description = 'Clone data from default database to staging connection (run every 30 mins via scheduler)';

    public function handle(): int
    {
        $stagingDriver = config('database.connections.staging.driver');
        $defaultDriver = config('database.default');
        $connection = config('database.default');
        $staging = 'staging';

        if (!config('database.connections.staging.database')) {
            $this->warn('Staging connection not configured. Set DB_STAGING_* in .env');
            return self::FAILURE;
        }

        $this->info('Cloning from ' . $connection . ' to ' . $staging . '...');

        $tables = $this->getTables($connection);

        if ($stagingDriver === 'mysql') {
            DB::connection($staging)->statement('SET FOREIGN_KEY_CHECKS=0');
        }

        foreach ($tables as $table) {
            if (in_array($table, ['migrations', 'jobs', 'job_batches', 'failed_jobs', 'cache', 'cache_locks'], true)) {
                continue;
            }
            $this->copyTable($connection, $staging, $table);
        }

        if ($stagingDriver === 'mysql') {
            DB::connection($staging)->statement('SET FOREIGN_KEY_CHECKS=1');
        }

        $this->info('Clone completed.');
        return self::SUCCESS;
    }

    protected function getTables(string $connection): array
    {
        $driver = config("database.connections.{$connection}.driver");
        if ($driver === 'mysql' || $driver === 'mariadb') {
            $result = DB::connection($connection)->select('SHOW TABLES');
            $key = 'Tables_in_' . config("database.connections.{$connection}.database");
            return array_map(fn ($r) => $r->{$key}, $result);
        }
        if ($driver === 'pgsql') {
            $result = DB::connection($connection)
                ->select("SELECT tablename FROM pg_tables WHERE schemaname = 'public'");
            return array_map(fn ($r) => $r->tablename, $result);
        }
        if ($driver === 'sqlite') {
            $result = DB::connection($connection)
                ->select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
            return array_map(fn ($r) => $r->name, $result);
        }
        return [];
    }

    protected function copyTable(string $from, string $to, string $tableName): void
    {
        if (!Schema::connection($to)->hasTable($tableName)) {
            $this->warn("Staging missing table: {$tableName}, skipping");
            return;
        }

        DB::connection($to)->table($tableName)->truncate();
        $count = 0;
        $query = DB::connection($from)->table($tableName);
        $query->chunk(500, function ($rows) use ($to, $tableName, &$count) {
            $values = $rows->map(fn ($row) => (array) $row)->toArray();
            if (!empty($values)) {
                DB::connection($to)->table($tableName)->insert($values);
                $count += count($values);
            }
        });

        $this->line("  {$tableName}: {$count} rows");
    }
}
