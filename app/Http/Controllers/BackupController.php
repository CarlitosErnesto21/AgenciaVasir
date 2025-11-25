<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Inertia\Inertia;

class BackupController extends Controller
{
    public function showBackupPage()
    {
        return Inertia::render('Configuracion/Backup');
    }

    public function index()
    {
        try {
            $backupDisk = Storage::disk('backup');

            // Buscar archivos en la carpeta VASIR específicamente
            $vasirPath = 'VASIR';

            if (!$backupDisk->exists($vasirPath)) {
                return response()->json([
                    'success' => true,
                    'backups' => [],
                    'total_backups' => 0,
                    'total_size' => '0 B'
                ]);
            }

            $files = $backupDisk->files($vasirPath);

            $backups = collect($files)
                ->filter(function ($file) {
                    return str_ends_with($file, '.zip');
                })
                ->map(function ($file) use ($backupDisk) {
                    $size = $backupDisk->size($file);
                    $lastModified = $backupDisk->lastModified($file);
                    $filename = basename($file);
                    $extension = pathinfo($filename, PATHINFO_EXTENSION);
                    $fileId = basename($filename, '.' . $extension);

                    $createdAt = Carbon::createFromTimestamp($lastModified);

                    return [
                        'id' => $fileId,
                        'name' => $filename,
                        'full_path' => $file,
                        'type' => $extension,
                        'size' => $this->formatBytes($size),
                        'size_bytes' => $size,
                        'created_at' => $createdAt->format('Y-m-d H:i:s'),
                        'formatted_date' => $createdAt->format('d/m/Y H:i:s'),
                        'date' => $createdAt->format('Y-m-d H:i:s'),
                        'timestamp' => $lastModified
                    ];
                })
                ->sortByDesc('created_at')
                ->values();

            return response()->json([
                'success' => true,
                'backups' => $backups,
                'total_backups' => $backups->count(),
                'total_size' => $this->formatBytes($backups->sum('size_bytes'))
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener la lista de backups: ' . $e->getMessage()
            ], 500);
        }
    }

    public function generate(Request $request)
    {
        try {
            $timestamp = now()->format('Y-m-d-H-i-s');
            $filename = "vasir-backup-{$timestamp}.zip";

            // Asegurar que el directorio VASIR existe
            $backupDisk = Storage::disk('backup');
            $backupPath = 'VASIR';
            if (!$backupDisk->exists($backupPath)) {
                $backupDisk->makeDirectory($backupPath);
            }

            $zipPath = storage_path("app/private/VASIR/{$filename}");

            // Crear el directorio padre si no existe
            $zipDir = dirname($zipPath);
            if (!file_exists($zipDir)) {
                mkdir($zipDir, 0755, true);
            }

            $zip = new \ZipArchive();

            // Crear el archivo ZIP
            $zipResult = $zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
            if ($zipResult !== TRUE) {
                return response()->json([
                    'success' => false,
                    'message' => "No se pudo crear el archivo ZIP. Error código: {$zipResult}"
                ], 500);
            }

            $addedFiles = 0;

            // 1. Crear dump de base de datos directamente en el ZIP
            try {
                $databaseContent = $this->createDatabaseDump();
                if (!empty($databaseContent)) {
                    $zip->addFromString('database_' . $timestamp . '.sql', $databaseContent);
                    $addedFiles++;
                }
            } catch (\Exception $e) {
                error_log("Database backup failed: " . $e->getMessage());
            }

            // 2. Incluir archivos importantes del proyecto
            $importantFiles = [
                'composer.json' => base_path('composer.json'),
                'composer.lock' => base_path('composer.lock'),
                'package.json' => base_path('package.json'),
            ];

            foreach ($importantFiles as $zipName => $filePath) {
                if (file_exists($filePath) && is_readable($filePath)) {
                    $zip->addFile($filePath, $zipName);
                    $addedFiles++;
                }
            }

            // 3. Incluir información del sistema
            $systemInfo = [
                'backup_date' => now()->format('Y-m-d H:i:s'),
                'laravel_version' => app()->version(),
                'php_version' => PHP_VERSION,
                'database_name' => config('database.connections.mysql.database'),
                'backup_type' => 'full'
            ];

            $zip->addFromString('backup_info.json', json_encode($systemInfo, JSON_PRETTY_PRINT));
            $addedFiles++;

            if ($addedFiles === 0) {
                $zip->close();
                if (file_exists($zipPath)) {
                    unlink($zipPath);
                }

                return response()->json([
                    'success' => false,
                    'message' => 'No se pudieron agregar archivos al backup'
                ], 500);
            }

            // Cerrar el ZIP correctamente
            $closeResult = $zip->close();
            if (!$closeResult) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al finalizar el archivo ZIP'
                ], 500);
            }

            // Verificar que el archivo se creó correctamente
            if (!file_exists($zipPath) || filesize($zipPath) === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'El archivo ZIP no se creó correctamente'
                ], 500);
            }

            return response()->json([
                'success' => true,
                'message' => 'Backup generado exitosamente',
                'filename' => $filename,
                'size' => $this->formatBytes(filesize($zipPath)),
                'files_included' => $addedFiles
            ]);

        } catch (\Exception $e) {
            // Limpiar en caso de error
            if (isset($zipPath) && file_exists($zipPath)) {
                unlink($zipPath);
            }

            return response()->json([
                'success' => false,
                'message' => 'Error al generar el backup: ' . $e->getMessage(),
                'exception' => get_class($e),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ], 500);
        }
    }

    /**
     * Crear dump de base de datos directamente
     */
    private function createDatabaseDump()
    {
        try {
            $sql = "-- ================================================" . PHP_EOL;
            $sql .= "-- Backup de base de datos VASIR" . PHP_EOL;
            $sql .= "-- Generado el: " . Carbon::now()->format('Y-m-d H:i:s') . PHP_EOL;
            $sql .= "-- Base de datos: " . DB::getDatabaseName() . PHP_EOL;
            $sql .= "-- ================================================" . PHP_EOL . PHP_EOL;

            $sql .= "SET FOREIGN_KEY_CHECKS = 0;" . PHP_EOL;
            $sql .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";" . PHP_EOL;
            $sql .= "SET AUTOCOMMIT = 0;" . PHP_EOL;
            $sql .= "START TRANSACTION;" . PHP_EOL . PHP_EOL;

            $tables = $this->getDatabaseTables();

            foreach ($tables as $table) {
                try {
                    // Estructura de la tabla
                    $createTable = DB::select("SHOW CREATE TABLE `{$table}`");
                    $sql .= "-- ================================================" . PHP_EOL;
                    $sql .= "-- Estructura de tabla `{$table}`" . PHP_EOL;
                    $sql .= "-- ================================================" . PHP_EOL;
                    $sql .= "DROP TABLE IF EXISTS `{$table}`;" . PHP_EOL;
                    $sql .= $createTable[0]->{'Create Table'} . ";" . PHP_EOL . PHP_EOL;

                    // Datos de la tabla
                    $rowCount = DB::table($table)->count();
                    if ($rowCount > 0) {
                        $sql .= "-- Datos de tabla `{$table}` ({$rowCount} registros)" . PHP_EOL;

                        // Obtener la primera columna como clave para orderBy
                        $columns = DB::getSchemaBuilder()->getColumnListing($table);
                        $firstColumn = $columns[0] ?? 'id';

                        $chunkSize = 100; // Procesar de a 100 registros para evitar problemas de memoria

                        DB::table($table)->orderBy($firstColumn)->chunk($chunkSize, function ($chunk) use (&$sql, $table) {
                            if ($chunk->isNotEmpty()) {
                                $sql .= "INSERT INTO `{$table}` VALUES" . PHP_EOL;
                                $values = [];

                                foreach ($chunk as $row) {
                                    $rowData = array_map([$this, 'escapeValue'], (array) $row);
                                    $values[] = '(' . implode(',', $rowData) . ')';
                                }

                                $sql .= implode(',' . PHP_EOL, $values) . ';' . PHP_EOL . PHP_EOL;
                            }
                        });
                    }
                } catch (\Exception $e) {
                    $sql .= "-- Error al respaldar tabla {$table}: " . $e->getMessage() . PHP_EOL . PHP_EOL;
                }
            }

            $sql .= "COMMIT;" . PHP_EOL;
            $sql .= "SET FOREIGN_KEY_CHECKS = 1;" . PHP_EOL;
            $sql .= PHP_EOL . "-- ================================================" . PHP_EOL;
            $sql .= "-- Fin del backup" . PHP_EOL;
            $sql .= "-- ================================================" . PHP_EOL;

            return $sql;
        } catch (\Exception $e) {
            error_log("Error creating database dump: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Escapar valores para SQL
     */
    private function escapeValue($value)
    {
        if (is_null($value)) {
            return 'NULL';
        } elseif (is_bool($value)) {
            return $value ? '1' : '0';
        } elseif (is_numeric($value)) {
            return $value;
        } else {
            // Escapar comillas y caracteres especiales
            $escaped = str_replace(['\\', "'", "\r", "\n", "\0", "\x1a"], ['\\\\', "\\'", '\\r', '\\n', '\\0', '\\Z'], $value);
            return "'" . $escaped . "'";
        }
    }

    /**
     * Obtener lista de tablas de la base de datos
     */
    private function getDatabaseTables()
    {
        try {
            $tables = DB::select('SHOW TABLES');
            $databaseName = DB::getDatabaseName();
            $tableKey = "Tables_in_{$databaseName}";

            return collect($tables)->pluck($tableKey)->filter(function($table) {
                // Excluir tablas del sistema y temporales
                return !in_array($table, [
                    'migrations',
                    'password_reset_tokens',
                    'sessions',
                    'failed_jobs',
                    'telescope_entries',
                    'telescope_entries_tags',
                    'telescope_monitoring'
                ]);
            })->toArray();
        } catch (\Exception $e) {
            error_log("Error getting database tables: " . $e->getMessage());
            return [];
        }
    }

    private function findBackupFile($backupDisk, $id)
    {
        $files = $backupDisk->files('VASIR');

        foreach ($files as $file) {
            $filename = basename($file);
            $fileId = pathinfo($filename, PATHINFO_FILENAME);

            if ($fileId === $id && str_ends_with($file, '.zip')) {
                return $file;
            }
        }

        return null;
    }

    public function download($id)
    {
        try {
            $backupDisk = Storage::disk('backup');
            $targetFile = $this->findBackupFile($backupDisk, $id);

            if (!$targetFile || !$backupDisk->exists($targetFile)) {
                return response()->json([
                    'success' => false,
                    'message' => 'El archivo de backup no existe'
                ], 404);
            }

            $filePath = $backupDisk->path($targetFile);
            $filename = basename($targetFile);

            return response()->download($filePath, $filename);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al descargar el backup: ' . $e->getMessage()
            ], 500);
        }
    }

    public function delete($id)
    {
        try {
            $backupDisk = Storage::disk('backup');
            $targetFile = $this->findBackupFile($backupDisk, $id);

            if (!$targetFile || !$backupDisk->exists($targetFile)) {
                return response()->json([
                    'success' => false,
                    'message' => 'El archivo de backup no existe'
                ], 404);
            }

            $backupDisk->delete($targetFile);

            return response()->json([
                'success' => true,
                'message' => 'Backup eliminado exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el backup: ' . $e->getMessage()
            ], 500);
        }
    }

    public function cleanup()
    {
        try {
            $backupDisk = Storage::disk('backup');
            $vasirPath = 'VASIR';

            if (!$backupDisk->exists($vasirPath)) {
                return response()->json([
                    'success' => true,
                    'message' => 'No hay backups para limpiar',
                    'deleted_count' => 0
                ]);
            }

            $files = $backupDisk->files($vasirPath);
            $backupFiles = collect($files)
                ->filter(function ($file) {
                    return str_ends_with($file, '.zip');
                })
                ->map(function ($file) use ($backupDisk) {
                    return [
                        'path' => $file,
                        'name' => basename($file),
                        'modified' => $backupDisk->lastModified($file)
                    ];
                })
                ->sortByDesc('modified') // Más recientes primero
                ->values();

            // Mantener solo los últimos 3 backups
            $keepLatest = 3;
            $toDelete = $backupFiles->skip($keepLatest);

            if ($toDelete->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'message' => "Ya tienes solo los últimos {$keepLatest} backups. No hay nada que limpiar.",
                    'deleted_count' => 0
                ]);
            }

            $deletedCount = 0;
            $deletedFiles = [];

            foreach ($toDelete as $file) {
                try {
                    $backupDisk->delete($file['path']);
                    $deletedCount++;
                    $deletedFiles[] = $file['name'];
                } catch (\Exception $e) {
                    // Continuar con el siguiente archivo si uno falla
                    continue;
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Limpieza completada. Se eliminaron {$deletedCount} backup(s) antiguos.",
                'deleted_count' => $deletedCount,
                'deleted_files' => $deletedFiles,
                'remaining_backups' => $backupFiles->take($keepLatest)->count()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al limpiar los backups: ' . $e->getMessage(),
                'exception' => get_class($e)
            ], 500);
        }
    }

    private function formatBytes($size, $precision = 2)
    {
        if ($size <= 0) {
            return '0 B';
        }

        $base = log($size, 1024);
        $suffixes = array('B', 'KB', 'MB', 'GB', 'TB');

        return round(pow(1024, $base - floor($base)), $precision) . ' ' . $suffixes[floor($base)];
    }
}
