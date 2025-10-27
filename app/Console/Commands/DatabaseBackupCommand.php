<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class DatabaseBackupCommand extends Command
{
    protected $signature = 'vasir:backup-db {--format=sql : Format del backup (sql|json)}';
    protected $description = 'Crear backup de la base de datos sin depender de mysqldump';

    public function handle()
    {
        $format = $this->option('format');
        $timestamp = Carbon::now()->format('Y-m-d-H-i-s');
        $filename = "vasir-{$timestamp}.{$format}";

        $this->info("Iniciando backup de base de datos...");

        try {
            if ($format === 'json') {
                $this->createJsonBackup($filename);
            } else {
                $this->createSqlBackup($filename);
            }

            $this->info("✅ Backup creado exitosamente: {$filename}");
            return 0;
        } catch (\Exception $e) {
            $this->error("❌ Error al crear backup: " . $e->getMessage());
            return 1;
        }
    }

    private function createJsonBackup($filename)
    {
        $backup = [];
        $tables = $this->getTables();

        foreach ($tables as $table) {
            $this->info("Respaldando tabla: {$table}");
            $data = DB::table($table)->get()->toArray();
            $backup[$table] = $data;
        }

        $jsonData = json_encode($backup, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        Storage::disk('backup')->put("VASIR/{$filename}", $jsonData);
    }

    private function createSqlBackup($filename)
    {
        $sql = "-- Backup de base de datos VASIR\n";
        $sql .= "-- Generado el: " . Carbon::now()->format('Y-m-d H:i:s') . "\n\n";
        $sql .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";

        $tables = $this->getTables();

        foreach ($tables as $table) {
            $this->info("Respaldando tabla: {$table}");

            // Estructura de la tabla
            $createTable = DB::select("SHOW CREATE TABLE `{$table}`");
            $sql .= "-- Estructura de tabla `{$table}`\n";
            $sql .= "DROP TABLE IF EXISTS `{$table}`;\n";
            $sql .= $createTable[0]->{'Create Table'} . ";\n\n";

            // Datos de la tabla
            $rows = DB::table($table)->get();
            if ($rows->count() > 0) {
                $sql .= "-- Datos de tabla `{$table}`\n";
                $sql .= "INSERT INTO `{$table}` VALUES\n";

                $values = [];
                foreach ($rows as $row) {
                    $rowData = array_map(function($value) {
                        return is_null($value) ? 'NULL' : "'" . addslashes($value) . "'";
                    }, (array) $row);
                    $values[] = '(' . implode(',', $rowData) . ')';
                }

                $sql .= implode(",\n", $values) . ";\n\n";
            }
        }

        $sql .= "SET FOREIGN_KEY_CHECKS = 1;\n";

        Storage::disk('backup')->put("VASIR/{$filename}", $sql);
    }

    private function getTables()
    {
        $tables = DB::select('SHOW TABLES');
        $databaseName = DB::getDatabaseName();
        $tableKey = "Tables_in_{$databaseName}";

        return collect($tables)->pluck($tableKey)->filter(function($table) {
            // Excluir tablas del sistema
            return !in_array($table, ['migrations', 'password_reset_tokens', 'sessions']);
        })->toArray();
    }
}
