<?php

return [
    'backup' => [
        /*
         * The name of this application. You can use this name to monitor
         * the backups.
         */
        'name' => 'VASIR',

        'source' => [
            'files' => [
                /*
                 * The list of directories and files that will be included in the backup.
                 */
                'include' => [
                    base_path(),
                ],

                /*
                 * These directories and files will be excluded from the backup.
                 */
                'exclude' => [
                    base_path('vendor'),
                    base_path('node_modules'),
                    base_path('storage/framework/cache'),
                    base_path('storage/framework/sessions'),
                    base_path('storage/framework/views'),
                    base_path('storage/logs'),
                ],

                /*
                 * Determines if symlinks should be followed.
                 */
                'follow_links' => false,

                /*
                 * Determines if it should avoid unreadable folders.
                 */
                'ignore_unreadable_directories' => false,

                /*
                 * The path relative to which the backup should be stored.
                 */
                'relative_path' => null,

                /*
                 * The path where the temporary files will be stored.
                 */
                'temporary_directory' => env('BACKUP_TEMP_PATH', storage_path('app/private/VASIR/temp')),
            ],

            /*
             * The names of the connections to the databases that should be backed up
             * MySQL, PostgreSQL, SQLite and Mongo databases are supported.
             */
            'databases' => [
                'mysql',
            ],
        ],

        /*
         * The database dump can be compressed to decrease diskspace usage.
         */
        'database_dump_compressor' => null,

        /*
         * The file extension used for the database dump.
         */
        'database_dump_file_extension' => '',

        /*
         * Here you can specify the path to the binary of mysqldump and other db dump commands.
         * For Windows, you might need to specify the full path to the executable.
         */
        'database_dump_command_path' => [
            'mysql' => env('DB_DUMP_PATH_MYSQL', 'mysqldump'),
            'pgsql' => env('DB_DUMP_PATH_PGSQL', 'pg_dump'),
            'mongodb' => env('DB_DUMP_PATH_MONGODB', 'mongodump'),
        ],

        'destination' => [
            /*
             * The filename prefix used for the backup zip file.
             */
            'filename_prefix' => env('BACKUP_FILENAME_PREFIX', 'vasir-'),

            /*
             * The disk names on which the backups will be stored.
             * You can configure multiple disks for redundancy.
             * Examples:
             * - Development: ['backup']
             * - Production S3: ['backup-s3'] 
             * - Production Hybrid: ['backup-local-prod', 'backup-s3']
             */
            'disks' => array_filter(explode(',', env('BACKUP_DISKS', 'backup'))),
        ],

        /*
         * The directory where the temporary files will be stored.
         */
        'temporary_directory' => env('BACKUP_TEMP_PATH', storage_path('app/private/VASIR/temp')),
    ],

    /*
     * Notifications are completely disabled for manual backup system.
     * This prevents any notification-related errors.
     */
    'notifications' => [
        'notifications' => [],

        'notifiable' => \Spatie\Backup\Notifications\Notifiable::class,

        'mail' => [
            'to' => env('BACKUP_NOTIFICATION_EMAIL', 'admin@vasir.com'),
            'from' => [
                'address' => env('MAIL_FROM_ADDRESS', 'hello@example.com'),
                'name' => env('MAIL_FROM_NAME', 'VASIR Backup System'),
            ],
        ],

        'slack' => [
            'webhook_url' => '',
            'channel' => null,
            'username' => null,
            'icon' => null,
        ],

        'discord' => [
            'webhook_url' => '',
            'username' => null,
            'avatar_url' => null,
        ],
    ],

    /*
     * Monitoring is disabled for manual backup system.
     * Enable only if you plan to use automatic health checks.
     */
    'monitor_backups' => [
        // Disabled for manual backup system
    ],

    'cleanup' => [
        /*
         * The strategy that will be used to cleanup old backups.
         * For manual backups, you can manually delete old backups
         * or enable automatic cleanup by uncommenting the strategy.
         *
         * Uncomment the line below if you want automatic cleanup.
         */
        'strategy' => \Spatie\Backup\Tasks\Cleanup\Strategies\DefaultStrategy::class,

        'default_strategy' => [
            /*
             * The number of days for which backups must be kept.
             */
            'keep_all_backups_for_days' => env('BACKUP_KEEP_ALL_DAYS', 7),

            /*
             * The number of days for which daily backups must be kept.
             */
            'keep_daily_backups_for_days' => env('BACKUP_KEEP_DAILY_DAYS', 16),

            /*
             * The number of weeks for which one weekly backup must be kept.
             */
            'keep_weekly_backups_for_weeks' => env('BACKUP_KEEP_WEEKLY_WEEKS', 8),

            /*
             * The number of months for which one monthly backup must be kept.
             */
            'keep_monthly_backups_for_months' => env('BACKUP_KEEP_MONTHLY_MONTHS', 4),

            /*
             * The number of years for which one yearly backup must be kept.
             */
            'keep_yearly_backups_for_years' => env('BACKUP_KEEP_YEARLY_YEARS', 2),

            /*
             * After cleaning up the backups remove the oldest backup until
             * this amount of megabytes has been reached.
             */
            'delete_oldest_backups_when_using_more_megabytes_than' => env('BACKUP_MAX_SIZE_MB', 5000),
        ],
    ],

];
