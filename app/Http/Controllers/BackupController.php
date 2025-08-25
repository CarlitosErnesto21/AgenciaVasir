<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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
                    
                    return [
                        'id' => basename($filename, '.zip'),
                        'name' => $filename,
                        'full_path' => $file,
                        'size' => $this->formatBytes($size),
                        'size_bytes' => $size,
                        'created_at' => Carbon::createFromTimestamp($lastModified)->format('Y-m-d H:i:s'),
                        'formatted_date' => Carbon::createFromTimestamp($lastModified)->format('d/m/Y H:i:s'),
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
            // Crear backup manualmente sin usar artisan para evitar problemas de notificaciones
            $includeDatabase = !$request->input('only_files', false);
            $includeFiles = !$request->input('only_db', false);
            
            $timestamp = now()->format('Y-m-d-H-i-s');
            $filename = "vasir-{$timestamp}.zip";
            $backupDisk = Storage::disk('backup');
            $tempPath = storage_path('app/private/VASIR/temp');
            
            // Crear directorio temporal si no existe
            if (!file_exists($tempPath)) {
                mkdir($tempPath, 0755, true);
            }
            
            $filesToZip = [];
            
            // Incluir dump de base de datos si se solicita
            if ($includeDatabase) {
                $databaseDumpPath = $tempPath . '/database.sql';
                $databaseName = config('database.connections.mysql.database');
                $username = config('database.connections.mysql.username');
                $password = config('database.connections.mysql.password');
                $host = config('database.connections.mysql.host');
                $port = config('database.connections.mysql.port', 3306);
                
                // Crear archivo de configuración temporal para MySQL (más seguro)
                $configFile = $tempPath . '/.my.cnf';
                $configContent = "[client]\nuser={$username}\npassword={$password}\nhost={$host}\nport={$port}";
                file_put_contents($configFile, $configContent);
                
                // Usar archivo de configuración en lugar de contraseña en línea de comandos
                $command = "mysqldump --defaults-file=\"{$configFile}\" {$databaseName} > \"{$databaseDumpPath}\" 2>&1";
                exec($command, $output, $returnCode);
                
                // Eliminar archivo de configuración temporal
                if (file_exists($configFile)) {
                    unlink($configFile);
                }
                
                if ($returnCode === 0 && file_exists($databaseDumpPath)) {
                    $filesToZip[] = $databaseDumpPath;
                } else {
                    // Si el dump falló, continuar sin él pero reportar
                    error_log("MySQL dump failed: " . implode("\n", $output));
                }
            }
            
            // Incluir archivos importantes si se solicita
            if ($includeFiles) {
                // Solo incluir archivos que sean seguros y útiles
                $importantFiles = [
                    base_path('composer.json'),
                    base_path('composer.lock'),
                    base_path('package.json'),
                    // NO incluimos .env por seguridad
                ];
                
                foreach ($importantFiles as $file) {
                    if (file_exists($file)) {
                        $filesToZip[] = $file;
                    }
                }
            }
            
            if (empty($filesToZip)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay archivos para respaldar'
                ], 400);
            }
            
            // Crear ZIP
            $zipPath = storage_path("app/private/VASIR/{$filename}");
            $zip = new \ZipArchive();
            
            $zipResult = $zip->open($zipPath, \ZipArchive::CREATE);
            if ($zipResult !== TRUE) {
                return response()->json([
                    'success' => false,
                    'message' => "No se pudo crear el archivo ZIP. Error código: {$zipResult}"
                ], 500);
            }
            
            $addedFiles = 0;
            foreach ($filesToZip as $file) {
                if (file_exists($file) && is_readable($file)) {
                    $zip->addFile($file, basename($file));
                    $addedFiles++;
                }
            }
            
            if ($addedFiles === 0) {
                $zip->close();
                unlink($zipPath); // Eliminar ZIP vacío
                return response()->json([
                    'success' => false,
                    'message' => 'No se pudieron agregar archivos al ZIP'
                ], 500);
            }
            
            $zip->close();
            
            // Limpiar archivos temporales
            foreach ($filesToZip as $file) {
                if (str_contains($file, $tempPath)) {
                    unlink($file);
                }
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Backup generado exitosamente',
                'filename' => $filename,
                'size' => $this->formatBytes(filesize($zipPath))
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al generar el backup: ' . $e->getMessage(),
                'exception' => get_class($e),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ], 500);
        }
    }

    private function findBackupFile($backupDisk, $id)
    {
        $files = $backupDisk->files('VASIR');
        
        foreach ($files as $file) {
            if (str_contains($file, $id) && str_ends_with($file, '.zip')) {
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
