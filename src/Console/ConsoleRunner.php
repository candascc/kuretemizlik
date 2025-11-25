<?php

class ConsoleRunner
{
    private const COMMANDS = [
        'help',
        'init-db',
        'run-migrations',
        'migrations-status',
        'schema-dump',
        'build-install',
        'mark-migrations',
        'list-tables',
        'smoke-tests',
    ];

    private static string $appRoot;
    private static bool $configLoaded = false;

    public static function run(array $argv): void
    {
        self::$appRoot = dirname(__DIR__, 2); // app/src/Console -> app
        $script = array_shift($argv);
        $command = $argv[0] ?? 'help';

        if (!in_array($command, self::COMMANDS, true)) {
            self::printError("Unknown command: {$command}");
            self::printHelp();
            exit(1);
        }

        if ($command === 'help') {
            self::printHelp();
            return;
        }

        array_shift($argv); // remove command
        try {
            self::runCommand($command, $argv);
        } catch (Throwable $e) {
            self::printError($e->getMessage());
            exit(1);
        }
    }

    public static function runCommand(string $command, array $args = []): void
    {
        if (!isset(self::$appRoot)) {
            self::$appRoot = dirname(__DIR__, 2);
        }

        switch ($command) {
            case 'init-db':
                self::commandInitDb($args);
                break;
            case 'run-migrations':
                self::commandRunMigrations($args);
                break;
            case 'migrations-status':
                self::commandMigrationsStatus($args);
                break;
            case 'schema-dump':
                self::commandSchemaDump($args);
                break;
            case 'build-install':
                self::commandBuildInstall($args);
                break;
            case 'mark-migrations':
                self::commandMarkMigrations($args);
                break;
            case 'list-tables':
                self::commandListTables($args);
                break;
            case 'smoke-tests':
                self::commandSmokeTests($args);
                break;
            case 'help':
            default:
                self::printHelp();
                break;
        }
    }

    private static function commandInitDb(array $args = []): void
    {
        $options = self::parseOptionArgs($args);
        if (isset($options['admin-password'])) {
            $_ENV['DEFAULT_ADMIN_PASSWORD'] = $options['admin-password'];
        }
        if (isset($options['operator-password'])) {
            $_ENV['DEFAULT_OP_PASSWORD'] = $options['operator-password'];
        }

        self::requireCore();
        Database::getInstance();
        echo "Database initialized." . PHP_EOL;

        if (!empty($options['with-tests']) || !empty($options['run-tests'])) {
            $testTarget = is_string($options['with-tests']) ? $options['with-tests'] : ($options['tests'] ?? null);
            self::executeSmokeTests($testTarget);
        }
    }

    private static function commandRunMigrations(array $args = []): void
    {
        self::requireCore();
        require_once self::$appRoot . '/src/Lib/MigrationManager.php';

        $result = MigrationManager::migrate();
        if (!empty($result['errors'])) {
            echo "Migration failed:" . PHP_EOL;
            foreach ($result['errors'] as $error) {
                $migration = $error['migration'] ?? 'unknown';
                $message = $error['error'] ?? 'unknown error';
                echo " - {$migration}: {$message}" . PHP_EOL;
            }
            exit(1);
        }
        echo "Migrations executed: {$result['executed']}" . PHP_EOL;
    }

    private static function commandMigrationsStatus(array $args = []): void
    {
        self::requireCore();
        require_once self::$appRoot . '/src/Lib/MigrationManager.php';

        $status = MigrationManager::status();
        echo "Total migrations: {$status['total']}" . PHP_EOL;
        echo "Executed: {$status['executed']}" . PHP_EOL;
        echo "Pending: {$status['pending']}" . PHP_EOL . PHP_EOL;

        foreach ($status['migrations'] as $migration) {
            $prefix = ($migration['status'] ?? '') === 'executed' ? '[x]' : '[ ]';
            echo $prefix . ' ' . $migration['migration'];
            if (!empty($migration['executed_at'])) {
                echo ' (executed at ' . $migration['executed_at'] . ')';
            }
            echo PHP_EOL;
        }
    }

    private static function commandSchemaDump(array $args = []): void
    {
        self::requireCore();
        self::ensureDirectory(self::$appRoot . '/scripts');

        $pdo = new PDO('sqlite:' . DB_PATH);
        $sql = "SELECT name, sql FROM sqlite_master WHERE type IN ('table','index','trigger') AND name NOT LIKE 'sqlite_%' ORDER BY CASE type WHEN 'table' THEN 1 WHEN 'index' THEN 2 ELSE 3 END, name";
        $objects = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

        $output = '';
        foreach ($objects as $object) {
            if (empty($object['sql'])) {
                continue;
            }
            $output .= '-- ' . strtoupper($object['type']) . ': ' . $object['name'] . PHP_EOL;
            $output .= $object['sql'] . ';' . PHP_EOL . PHP_EOL;
        }

        file_put_contents(self::$appRoot . '/schema-current.sql', $output);
        echo "Schema dumped to schema-current.sql" . PHP_EOL;
    }

    private static function commandBuildInstall(array $args = []): void
    {
        self::requireCore();
        $schemaPath = self::$appRoot . '/schema-current.sql';
        if (!is_readable($schemaPath)) {
            throw new RuntimeException('Schema snapshot not found. Run console schema-dump first.');
        }

        $installPath = self::$appRoot . '/db/install.sql';
        $schema = file_get_contents($schemaPath);
        $schema = str_replace('"users_legacy"', 'users', $schema);
        $schema = str_replace('users_legacy', 'users', $schema);

        $header = "-- Auto-generated install script\n-- Source: schema-current.sql\n-- Generated: " . date('Y-m-d H:i:s') . "\n\nPRAGMA journal_mode=WAL;\nPRAGMA foreign_keys=ON;\n\n";

        $options = self::parseOptionArgs($args);

        $defaultAdminPass = $options['admin-password'] ?? ($_ENV['DEFAULT_ADMIN_PASSWORD'] ?? 'ChangeMe123!');
        $defaultOperatorPass = $options['operator-password'] ?? ($_ENV['DEFAULT_OP_PASSWORD'] ?? 'ChangeMe123!');
        $adminHash = password_hash($defaultAdminPass, PASSWORD_DEFAULT);
        $operatorHash = password_hash($defaultOperatorPass, PASSWORD_DEFAULT);

        $seed = <<<SQL
-- Seed data
INSERT OR IGNORE INTO services (id, name, duration_min, default_fee, is_active, created_at)
VALUES
  (1, 'Ev Temizliği', 120, 150.00, 1, datetime('now')),
  (2, 'Ofis Temizliği', 90, 100.00, 1, datetime('now')),
  (3, 'Cam Temizliği', 60, 80.00, 1, datetime('now')),
  (4, 'Halı Yıkama', 180, 200.00, 1, datetime('now')),
  (5, 'Balkon Temizliği', 45, 60.00, 1, datetime('now'));

INSERT OR IGNORE INTO users (id, username, password_hash, role, is_active, created_at, updated_at)
VALUES
  (1, 'candas', '{$adminHash}', 'ADMIN', 1, datetime('now'), datetime('now')),
  (2, 'necla', '{$operatorHash}', 'OPERATOR', 1, datetime('now'), datetime('now'));

SQL;

        file_put_contents($installPath, $header . $schema . PHP_EOL . $seed);
        echo "Install script regenerated." . PHP_EOL;
    }

    private static function commandMarkMigrations(array $args = []): void
    {
        self::requireCore();
        $pdo = new PDO('sqlite:' . DB_PATH);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $migrationDir = self::$appRoot . '/db/migrations';
        if (!is_dir($migrationDir)) {
            throw new RuntimeException('Migration directory not found: ' . $migrationDir);
        }

        $files = array_merge(
            glob($migrationDir . '/*.sql') ?: [],
            glob($migrationDir . '/*.php') ?: []
        );

        $migrations = [];
        foreach ($files as $file) {
            if (str_contains($file, '_rollback')) {
                continue;
            }
            $name = preg_replace('/\.(sql|php)$/i', '', basename($file));
            if ($name) {
                $migrations[$name] = true;
            }
        }

        if (empty($migrations)) {
            echo "No migration files detected." . PHP_EOL;
            return;
        }

        ksort($migrations);

        $stmt = $pdo->prepare('INSERT OR IGNORE INTO schema_migrations (migration, executed_at) VALUES (?, datetime(\'now\'))');
        $count = 0;
        foreach (array_keys($migrations) as $migration) {
            $stmt->execute([$migration]);
            $count++;
        }

        echo "Marked {$count} migrations as executed." . PHP_EOL;
    }

    private static function commandListTables(array $args = []): void
    {
        self::requireCore();
        $pdo = new PDO('sqlite:' . DB_PATH);
        $tables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name")->fetchAll(PDO::FETCH_COLUMN);
        foreach ($tables as $table) {
            echo $table . PHP_EOL;
        }
    }

    private static function commandSmokeTests(array $args = []): void
    {
        $options = self::parseOptionArgs($args);
        $testTarget = $options['tests'] ?? null;
        self::executeSmokeTests($testTarget);
    }

    private static function requireCore(): void
    {
        if (!self::$configLoaded) {
            require_once self::$appRoot . '/config/config.php';
            self::$configLoaded = true;
        }
        require_once self::$appRoot . '/src/Lib/Database.php';
    }

    private static function ensureDirectory(string $path): void
    {
        if (!is_dir($path)) {
            mkdir($path, 0775, true);
        }
    }

    private static function parseOptionArgs(array $args): array
    {
        $options = [];
        $count = count($args);
        for ($i = 0; $i < $count; $i++) {
            $arg = $args[$i];
            if (strpos($arg, '--') !== 0) {
                continue;
            }
            $arg = substr($arg, 2);
            if ($arg === '') {
                continue;
            }

            if (strpos($arg, '=') !== false) {
                [$key, $value] = explode('=', $arg, 2);
            } else {
                $key = $arg;
                $value = null;
                $next = $args[$i + 1] ?? null;
                if ($next !== null && strpos($next, '--') !== 0) {
                    $value = $next;
                    $i++;
                }
            }

            if ($value === null) {
                $options[$key] = true;
            } else {
                $options[$key] = $value;
            }
        }

        return $options;
    }

    private static function executeSmokeTests(?string $testTarget = null): void
    {
        if (!isset(self::$appRoot)) {
            self::$appRoot = dirname(__DIR__, 2);
        }

        $phpunitExecutable = self::$appRoot . '/vendor/bin/phpunit';
        if (DIRECTORY_SEPARATOR === '\\') {
            $phpunitExecutable .= '.bat';
        }

        if (!file_exists($phpunitExecutable)) {
            throw new RuntimeException('PHPUnit executable not found at ' . $phpunitExecutable);
        }

        $target = $testTarget ?? 'tests/HeaderManagerTest.php';
        $commands = [];

        $commands[] = escapeshellarg($phpunitExecutable) . ' --colors=always --dont-report-useless-tests ' . escapeshellarg($target);

        if ($testTarget === null) {
            $functionalRunner = self::$appRoot . '/tests/functional/run_all.php';
            if (file_exists($functionalRunner)) {
                $commands[] = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($functionalRunner);
            }
        }

        $previousCwd = getcwd();
        chdir(self::$appRoot);
        foreach ($commands as $command) {
            passthru($command, $exitCode);
            if ($exitCode !== 0) {
                chdir($previousCwd !== false ? $previousCwd : self::$appRoot);
                throw new RuntimeException('Smoke tests failed (exit code ' . $exitCode . ') while running: ' . $command);
            }
        }
        chdir($previousCwd !== false ? $previousCwd : self::$appRoot);

        echo 'Smoke tests passed.' . PHP_EOL;
    }

    private static function printHelp(): void
    {
        echo "Usage: php bin/console <command>" . PHP_EOL . PHP_EOL;
        echo "Available commands:" . PHP_EOL;
        echo "  help                Display this help message" . PHP_EOL;
        echo "  init-db             Initialize the SQLite database (use --with-tests to run smoke tests)" . PHP_EOL;
        echo "  run-migrations      Execute pending migrations" . PHP_EOL;
        echo "  migrations-status   Show migration execution status" . PHP_EOL;
        echo "  schema-dump         Dump current schema to schema-current.sql" . PHP_EOL;
        echo "  build-install       Regenerate db/install.sql from schema snapshot" . PHP_EOL;
        echo "  mark-migrations     Mark known migrations as executed" . PHP_EOL;
        echo "  list-tables         List tables in the SQLite database" . PHP_EOL;
        echo "  smoke-tests         Run lightweight PHPUnit checks (defaults to tests/HeaderManagerTest.php)" . PHP_EOL;
    }

    private static function printError(string $message): void
    {
        fwrite(STDERR, $message . PHP_EOL);
    }
}
