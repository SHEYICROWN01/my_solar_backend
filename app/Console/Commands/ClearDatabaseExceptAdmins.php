<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ClearDatabaseExceptAdmins extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:clear-except-admins {--force : Force the operation without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear all database tables except admins table';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Safety check - confirm the operation unless --force is used
        if (!$this->option('force')) {
            if (!$this->confirm('This will delete ALL data from ALL tables except admins. Are you sure?')) {
                $this->info('Operation cancelled.');
                return 0;
            }
        }

        $this->info('Starting database cleanup...');

        try {
            // Get database connection type
            $connection = DB::connection();
            $driver = $connection->getDriverName();

            // Disable foreign key checks based on database type
            if ($driver === 'mysql') {
                DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            }

            // Get all table names based on database type
            if ($driver === 'mysql') {
                $tables = DB::select('SHOW TABLES');
                $databaseName = DB::getDatabaseName();
                $tableKey = 'Tables_in_' . $databaseName;
            } else {
                // PostgreSQL
                $tables = DB::select("
                    SELECT tablename 
                    FROM pg_tables 
                    WHERE schemaname = 'public'
                ");
                $tableKey = 'tablename';
            }

            $skippedTables = ['admins', 'migrations'];
            $clearedTables = [];

            foreach ($tables as $table) {
                $tableName = $table->{$tableKey};
                
                // Skip protected tables
                if (in_array($tableName, $skippedTables)) {
                    $this->line("Skipping table: {$tableName}");
                    continue;
                }

                // Check if table exists and has data
                if (Schema::hasTable($tableName)) {
                    $count = DB::table($tableName)->count();
                    
                    if ($count > 0) {
                        // For PostgreSQL, we need to handle foreign keys differently
                        if ($driver === 'pgsql') {
                            // Use DELETE instead of TRUNCATE for PostgreSQL with foreign keys
                            DB::table($tableName)->delete();
                            // Reset sequence if it exists
                            try {
                                DB::statement("SELECT setval(pg_get_serial_sequence('{$tableName}', 'id'), 1, false)");
                            } catch (\Exception $e) {
                                // Sequence might not exist, ignore
                            }
                        } else {
                            // MySQL - use truncate
                            DB::table($tableName)->truncate();
                        }
                        
                        $clearedTables[] = $tableName;
                        $this->line("Cleared table: {$tableName} ({$count} records)");
                    } else {
                        $this->line("Table {$tableName} was already empty");
                    }
                }
            }

            // Re-enable foreign key checks for MySQL
            if ($driver === 'mysql') {
                DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            }

            $this->newLine();
            $this->info('Database cleanup completed successfully!');
            $this->info('Tables cleared: ' . count($clearedTables));
            
            if (!empty($clearedTables)) {
                $this->table(['Cleared Tables'], array_map(fn($table) => [$table], $clearedTables));
            }

            $this->warn('Note: Admins table was preserved as requested.');

        } catch (\Exception $e) {
            // Re-enable foreign key checks for MySQL in case of error
            if (isset($driver) && $driver === 'mysql') {
                DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            }
            
            $this->error('An error occurred while clearing the database:');
            $this->error($e->getMessage());
            return 1;
        }

        return 0;
    }
}
