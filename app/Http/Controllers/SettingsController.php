<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SiteSetting;
use App\Models\CompanyValue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Inertia\Inertia;

class SettingsController extends Controller
{
    public function index()
    {
        $mission = SiteSetting::get('company_mission');
        $vision = SiteSetting::get('company_vision');
        $description = SiteSetting::get('company_description');
        $companyValues = CompanyValue::getAllValues();

        // Log para debug
        Log::info('Settings loaded:', [
            'mission' => $mission,
            'vision' => $vision,
            'description' => $description,
            'values_count' => $companyValues->count()
        ]);

        return Inertia::render('Configuracion/Settings', [
            'siteSettings' => [
                'mission' => $mission,
                'vision' => $vision,
                'description' => $description,
            ],
            'companyValues' => $companyValues,
            'databaseInfo' => $this->getDatabaseInfo()
        ]);
    }

    /**
     * Obtener información dinámica de la base de datos
     */
    public function getDatabaseInfo()
    {
        try {
            // Obtener el último backup
            $lastBackup = $this->getLastBackupInfo();

            // Obtener el tamaño de la base de datos
            $dbSize = $this->getDatabaseSize();

            // Determinar el estado de la base de datos
            $status = $this->getDatabaseStatus();

            return [
                'last_backup_date' => $lastBackup['date'],
                'last_backup_formatted' => $lastBackup['formatted'],
                'database_size' => $dbSize,
                'status' => $status,
                'status_text' => $this->getStatusText($status)
            ];

        } catch (\Exception $e) {
            return [
                'last_backup_date' => null,
                'last_backup_formatted' => 'No disponible',
                'database_size' => 'No disponible',
                'status' => 'unknown',
                'status_text' => 'Desconocido'
            ];
        }
    }

    /**
     * Obtener información del último backup
     */
    private function getLastBackupInfo()
    {
        try {
            // Usar el mismo disco que usa nuestro BackupController
            $backupDisk = Storage::disk('backup');
            $backupPath = 'VASIR';

            // Verificar si el directorio principal existe
            if (!$backupDisk->exists('.')) {
                Log::warning('Backup disk root directory does not exist');
                return [
                    'date' => null,
                    'formatted' => 'Directorio no configurado'
                ];
            }

            // Verificar si el subdirectorio VASIR existe
            if (!$backupDisk->exists($backupPath)) {
                Log::info('VASIR backup directory does not exist yet');
                return [
                    'date' => null,
                    'formatted' => 'Sin respaldos disponibles'
                ];
            }

            // Obtener todos los archivos en el directorio de backup
            $files = $backupDisk->allFiles($backupPath);
            Log::info('Checking backup files:', [
                'path' => $backupPath,
                'total_files' => count($files)
            ]);

            $latestFile = null;
            $latestTime = 0;
            $backupCount = 0;

            foreach ($files as $file) {
                // Solo considerar archivos ZIP (nuestros backups)
                if (str_ends_with($file, '.zip') && str_contains($file, 'vasir-backup-')) {
                    $backupCount++;
                    try {
                        $lastModified = $backupDisk->lastModified($file);
                        if ($lastModified > $latestTime) {
                            $latestTime = $lastModified;
                            $latestFile = $file;
                        }
                    } catch (\Exception $e) {
                        Log::warning('Could not get file modification time:', [
                            'file' => $file,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            }

            Log::info('Backup analysis complete:', [
                'total_backups_found' => $backupCount,
                'latest_file' => $latestFile,
                'latest_timestamp' => $latestTime
            ]);

            if ($latestFile && $latestTime > 0) {
                $date = Carbon::createFromTimestamp($latestTime)
                            ->setTimezone('America/El_Salvador');

                $timeDiff = $date->diffForHumans();
                $formattedDate = $date->format('d/m/Y H:i');

                Log::info('Latest backup found:', [
                    'file' => $latestFile,
                    'timestamp' => $latestTime,
                    'formatted_date' => $formattedDate,
                    'time_diff' => $timeDiff
                ]);

                return [
                    'date' => $date,
                    'formatted' => $timeDiff . ' (' . $formattedDate . ')'
                ];
            }

            // Si encontramos archivos pero no pudimos obtener información
            if ($backupCount > 0) {
                return [
                    'date' => null,
                    'formatted' => "Error en {$backupCount} respaldos"
                ];
            }

            return [
                'date' => null,
                'formatted' => 'Sin respaldos disponibles'
            ];

        } catch (\Exception $e) {
            Log::error('Error getting backup info:', [
                'error' => $e->getMessage(),
                'backup_config' => config('filesystems.disks.backup')
            ]);
            return [
                'date' => null,
                'formatted' => 'Error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Obtener el tamaño de la base de datos
     */
    private function getDatabaseSize()
    {
        try {
            $databaseName = config('database.connections.mysql.database');

            // Primero intentar obtener el tamaño total de la base de datos
            $result = DB::select("
                SELECT
                    ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb,
                    COUNT(*) as table_count
                FROM information_schema.tables
                WHERE table_schema = ?
                AND table_type = 'BASE TABLE'
            ", [$databaseName]);

            if (empty($result)) {
                // Método alternativo si no funciona el anterior
                $altResult = DB::select("
                    SELECT
                        ROUND(SUM(IFNULL(data_length, 0) + IFNULL(index_length, 0)) / 1024 / 1024, 2) AS size_mb,
                        COUNT(*) as table_count
                    FROM information_schema.tables
                    WHERE table_schema = DATABASE()
                ");
                $result = $altResult;
            }

            if (!empty($result) && isset($result[0])) {
                $sizeMB = floatval($result[0]->size_mb ?? 0);
                $tableCount = intval($result[0]->table_count ?? 0);

                // Log para debug
                Log::info('Database size calculation:', [
                    'database' => $databaseName,
                    'size_mb' => $sizeMB,
                    'table_count' => $tableCount
                ]);

                if ($sizeMB >= 1024) {
                    return round($sizeMB / 1024, 2) . ' GB (' . $tableCount . ' tablas)';
                } elseif ($sizeMB >= 1) {
                    return round($sizeMB, 2) . ' MB (' . $tableCount . ' tablas)';
                } else {
                    return round($sizeMB * 1024, 2) . ' KB (' . $tableCount . ' tablas)';
                }
            }

            // Si no se pudo calcular, intentar método básico
            $basicResult = DB::select("SELECT COUNT(*) as table_count FROM information_schema.tables WHERE table_schema = ?", [$databaseName]);
            $tableCount = $basicResult[0]->table_count ?? 0;

            return $tableCount > 0 ? "Calculando... ({$tableCount} tablas)" : 'Base de datos vacía';

        } catch (\Exception $e) {
            Log::error('Error calculating database size:', [
                'error' => $e->getMessage(),
                'database' => config('database.connections.mysql.database')
            ]);
            return 'Error al calcular';
        }
    }

    /**
     * Determinar el estado de la base de datos
     */
    private function getDatabaseStatus()
    {
        try {
            // Intentar hacer una consulta simple para verificar conectividad
            DB::select('SELECT 1');

            // Verificar el estado de las tablas principales
            $databaseName = config('database.connections.mysql.database');
            $tableResult = DB::select("
                SELECT COUNT(*) as table_count
                FROM information_schema.tables
                WHERE table_schema = ? AND table_type = 'BASE TABLE'
            ", [$databaseName]);

            $tableCount = $tableResult[0]->table_count ?? 0;

            // Verificar si podemos acceder a las tablas principales de la aplicación
            $coreTablesExist = 0;
            $coreTables = ['users', 'empleados', 'clientes', 'productos', 'tours', 'hoteles'];

            foreach ($coreTables as $table) {
                try {
                    $exists = DB::select("
                        SELECT 1
                        FROM information_schema.tables
                        WHERE table_schema = ? AND table_name = ?
                    ", [$databaseName, $table]);

                    if (!empty($exists)) {
                        // Verificar que la tabla tenga al menos estructura básica
                        $columns = DB::select("SHOW COLUMNS FROM `{$table}`");
                        if (count($columns) > 0) {
                            $coreTablesExist++;
                        }
                    }
                } catch (\Exception $e) {
                    // Tabla no existe o hay problema de acceso
                    continue;
                }
            }

            // Verificar conexiones activas (solo si SHOW PROCESSLIST está disponible)
            $activeConnections = 0;
            try {
                $processlist = DB::select('SHOW PROCESSLIST');
                $activeConnections = count($processlist);
            } catch (\Exception $e) {
                // No se pudo obtener processlist, continuar sin este check
                Log::info('Could not get process list', ['error' => $e->getMessage()]);
            }

            // Log para debug
            Log::info('Database status check:', [
                'database' => $databaseName,
                'total_tables' => $tableCount,
                'core_tables_found' => $coreTablesExist,
                'active_connections' => $activeConnections
            ]);

            // Determinar estado basado en los checks
            if ($coreTablesExist >= 4 && $tableCount > 10) {
                if ($activeConnections > 0 && $activeConnections < 50) {
                    return 'operational';
                } elseif ($activeConnections >= 50) {
                    return 'high_load';
                } else {
                    return 'operational'; // Si no podemos ver connections, asumir OK
                }
            } elseif ($coreTablesExist >= 2) {
                return 'warning'; // Algunas tablas principales faltan
            } else {
                return 'error'; // Sistema no funcional
            }

        } catch (\Exception $e) {
            Log::error('Database status check failed:', [
                'error' => $e->getMessage(),
                'database' => config('database.connections.mysql.database')
            ]);
            return 'error';
        }
    }    /**
     * Obtener texto descriptivo del estado
     */
    private function getStatusText($status)
    {
        return match($status) {
            'operational' => 'Operativo ✓',
            'high_load' => 'Carga Alta ⚠️',
            'warning' => 'Advertencia ⚠️',
            'error' => 'Error ❌',
            default => 'Desconocido ❓'
        };
    }

    /**
     * Formatear bytes a unidades legibles
     */
    private function formatBytes($bytes)
    {
        if ($bytes >= 1073741824) {
            return round($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return round($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return round($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' bytes';
        }
    }

    public function update(Request $request)
    {
        $request->validate([
            'mission' => 'required|string|max:1000',
            'vision' => 'required|string|max:1000',
            'description' => 'required|string|max:1000',
            'companyValues' => 'sometimes|array',
            'companyValues.new' => 'sometimes|array',
            'companyValues.updated' => 'sometimes|array',
            'companyValues.deleted' => 'sometimes|array',
        ]);

        // Log para debug
        Log::info('Updating settings:', [
            'mission' => $request->mission,
            'vision' => $request->vision,
            'description' => $request->description,
            'companyValues' => $request->companyValues
        ]);

        try {
            // Actualizar o crear la misión
            $missionSetting = SiteSetting::updateOrCreate(
                ['key' => 'company_mission'],
                [
                    'value' => $request->mission,
                    'type' => 'textarea',
                    'label' => 'Misión de la Empresa',
                    'description' => 'Misión corporativa que aparece en la página Sobre Nosotros',
                    'updated_by' => Auth::id()
                ]
            );

            // Actualizar o crear la visión
            $visionSetting = SiteSetting::updateOrCreate(
                ['key' => 'company_vision'],
                [
                    'value' => $request->vision,
                    'type' => 'textarea',
                    'label' => 'Visión de la Empresa',
                    'description' => 'Visión corporativa que aparece en la página Sobre Nosotros',
                    'updated_by' => Auth::id()
                ]
            );

            // Actualizar o crear la descripción
            $descriptionSetting = SiteSetting::updateOrCreate(
                ['key' => 'company_description'],
                [
                    'value' => $request->description,
                    'type' => 'textarea',
                    'label' => 'Descripción de la Empresa',
                    'description' => 'Descripción principal que aparece en el encabezado de la página Sobre Nosotros',
                    'updated_by' => Auth::id()
                ]
            );

            // Manejar valores corporativos si se enviaron
            if ($request->has('companyValues')) {
                $companyValues = $request->companyValues;

                // Crear nuevos valores
                if (isset($companyValues['new']) && is_array($companyValues['new'])) {
                    foreach ($companyValues['new'] as $newValue) {
                        CompanyValue::create([
                            'titulo' => $newValue['titulo'],
                            'descripcion' => $newValue['descripcion'],
                        ]);
                    }
                }

                // Actualizar valores existentes
                if (isset($companyValues['updated']) && is_array($companyValues['updated'])) {
                    foreach ($companyValues['updated'] as $updatedValue) {
                        $value = CompanyValue::find($updatedValue['id']);
                        if ($value) {
                            $value->update([
                                'titulo' => $updatedValue['titulo'],
                                'descripcion' => $updatedValue['descripcion'],
                            ]);
                        }
                    }
                }

                // Eliminar valores
                if (isset($companyValues['deleted']) && is_array($companyValues['deleted'])) {
                    CompanyValue::whereIn('id', $companyValues['deleted'])->delete();
                }
            }

            // Log para confirmar la actualización
            Log::info('Settings updated successfully:', [
                'mission_id' => $missionSetting->id,
                'vision_id' => $visionSetting->id,
                'description_id' => $descriptionSetting->id,
                'mission_value' => $missionSetting->value,
                'vision_value' => $visionSetting->value,
                'description_value' => $descriptionSetting->value
            ]);

            // Limpiar cache si está habilitado
            if (config('cache.default') !== 'array') {
                cache()->forget('site_settings_company_mission');
                cache()->forget('site_settings_company_vision');
                cache()->forget('site_settings_company_description');
            }

            return back()->with('success', 'Configuración de la empresa actualizada correctamente');

        } catch (\Exception $e) {
            Log::error('Error updating settings:', [
                'error' => $e->getMessage(),
                'mission' => $request->mission,
                'vision' => $request->vision,
                'description' => $request->description
            ]);

            return back()->with('error', 'Error al actualizar la configuración: ' . $e->getMessage());
        }
    }

    /**
     * Crear un nuevo valor corporativo
     */
    public function storeValue(Request $request)
    {
        $request->validate([
            'titulo' => 'required|string|max:100',
            'descripcion' => 'required|string|max:500',
        ]);

        try {
            $value = CompanyValue::create([
                'titulo' => $request->titulo,
                'descripcion' => $request->descripcion,
            ]);

            Log::info('Company value created:', [
                'id' => $value->id,
                'titulo' => $value->titulo
            ]);

            return back()->with('success', 'Valor corporativo agregado correctamente');

        } catch (\Exception $e) {
            Log::error('Error creating company value:', [
                'error' => $e->getMessage(),
                'titulo' => $request->titulo
            ]);

            return back()->with('error', 'Error al agregar el valor: ' . $e->getMessage());
        }
    }

    /**
     * Actualizar un valor corporativo
     */
    public function updateValue(Request $request, $id)
    {
        $request->validate([
            'titulo' => 'required|string|max:100',
            'descripcion' => 'required|string|max:500',
        ]);

        try {
            $value = CompanyValue::findOrFail($id);

            $value->update([
                'titulo' => $request->titulo,
                'descripcion' => $request->descripcion,
            ]);

            Log::info('Company value updated:', [
                'id' => $value->id,
                'titulo' => $value->titulo
            ]);

            return back()->with('success', 'Valor corporativo actualizado correctamente');

        } catch (\Exception $e) {
            Log::error('Error updating company value:', [
                'error' => $e->getMessage(),
                'id' => $id
            ]);

            return back()->with('error', 'Error al actualizar el valor: ' . $e->getMessage());
        }
    }

    /**
     * Eliminar un valor corporativo
     */
    public function destroyValue($id)
    {
        try {
            $value = CompanyValue::findOrFail($id);
            $titulo = $value->titulo;

            $value->delete();

            Log::info('Company value deleted:', [
                'id' => $id,
                'titulo' => $titulo
            ]);

            return back()->with('success', 'Valor corporativo eliminado correctamente');

        } catch (\Exception $e) {
            Log::error('Error deleting company value:', [
                'error' => $e->getMessage(),
                'id' => $id
            ]);

            return back()->with('error', 'Error al eliminar el valor: ' . $e->getMessage());
        }
    }
}
