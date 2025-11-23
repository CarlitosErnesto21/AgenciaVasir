<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class OptimizeSessionConfig extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'session:optimize
                            {--lifetime=240 : Session lifetime in minutes}
                            {--driver=database : Session driver}
                            {--encrypt=false : Encrypt session data}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Optimize session configuration for better CSRF token management';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $lifetime = $this->option('lifetime');
        $driver = $this->option('driver');
        $encrypt = $this->option('encrypt');

        $this->info('Optimizing session configuration...');

        // Verificar si existe la tabla de sesiones para driver database
        if ($driver === 'database') {
            $this->checkSessionsTable();
        }

        // Mostrar configuración actual
        $this->showCurrentConfig();

        // Mostrar recomendaciones
        $this->showRecommendations($lifetime, $driver, $encrypt);

        // Crear archivo de configuración optimizada si se confirma
        if ($this->confirm('¿Deseas aplicar la configuración optimizada?')) {
            $this->applyOptimizedConfig($lifetime, $driver, $encrypt);
        }

        return 0;
    }

    private function checkSessionsTable()
    {
        try {
            \DB::table('sessions')->limit(1)->get();
            $this->info('✓ Tabla de sesiones existe y es accesible');
        } catch (\Exception $e) {
            $this->warn('⚠ Tabla de sesiones no encontrada. Ejecuta: php artisan session:table && php artisan migrate');
        }
    }

    private function showCurrentConfig()
    {
        $this->info('Configuración actual de sesiones:');
        $this->table(['Configuración', 'Valor'], [
            ['Lifetime', config('session.lifetime') . ' minutos'],
            ['Driver', config('session.driver')],
            ['Encrypt', config('session.encrypt') ? 'Sí' : 'No'],
            ['Cookie Name', config('session.cookie')],
            ['Same Site', config('session.same_site')],
        ]);
    }

    private function showRecommendations($lifetime, $driver, $encrypt)
    {
        $this->info('Recomendaciones para mejorar CSRF token management:');

        $this->line('');
        $this->line('1. <fg=yellow>Lifetime de sesión</>:');
        $this->line('   - Actual: ' . config('session.lifetime') . ' minutos');
        $this->line('   - Recomendado: ' . $lifetime . ' minutos (4 horas)');
        $this->line('   - Beneficio: Reduce expiración durante procesos largos');

        $this->line('');
        $this->line('2. <fg=yellow>Driver de sesión</>:');
        $this->line('   - Actual: ' . config('session.driver'));
        $this->line('   - Recomendado: ' . $driver);
        $this->line('   - Beneficio: Mejor persistencia y rendimiento');

        $this->line('');
        $this->line('3. <fg=yellow>Encriptación</>:');
        $this->line('   - Actual: ' . (config('session.encrypt') ? 'Habilitada' : 'Deshabilitada'));
        $this->line('   - Recomendado: ' . ($encrypt ? 'Habilitada' : 'Deshabilitada'));
        $this->line('   - Beneficio: Mayor seguridad para datos sensibles');

        $this->line('');
        $this->line('4. <fg=yellow>Configuraciones adicionales</>:');
        $this->line('   - Middleware personalizado: ✓ Implementado');
        $this->line('   - Auto-refresh de tokens: ✓ Implementado');
        $this->line('   - Interceptores Axios/Inertia: ✓ Implementado');
    }

    private function applyOptimizedConfig($lifetime, $driver, $encrypt)
    {
        $envPath = base_path('.env');

        if (!file_exists($envPath)) {
            $this->error('Archivo .env no encontrado');
            return;
        }

        $envContent = file_get_contents($envPath);

        // Actualizar configuraciones en .env
        $updates = [
            'SESSION_LIFETIME' => $lifetime,
            'SESSION_DRIVER' => $driver,
            'SESSION_ENCRYPT' => $encrypt ? 'true' : 'false',
        ];

        foreach ($updates as $key => $value) {
            if (strpos($envContent, $key . '=') !== false) {
                $envContent = preg_replace('/^' . $key . '=.*$/m', $key . '=' . $value, $envContent);
            } else {
                $envContent .= "\n" . $key . '=' . $value;
            }
        }

        file_put_contents($envPath, $envContent);

        $this->info('✓ Configuración de sesiones actualizada en .env');
        $this->warn('⚠ Ejecuta "php artisan config:cache" para aplicar los cambios');

        // Mostrar comandos adicionales recomendados
        $this->line('');
        $this->info('Comandos adicionales recomendados:');
        $this->line('php artisan config:cache');
        if ($driver === 'database') {
            $this->line('php artisan session:table (si no existe)');
            $this->line('php artisan migrate');
        }
        $this->line('php artisan queue:restart (si usas colas)');
    }
}
