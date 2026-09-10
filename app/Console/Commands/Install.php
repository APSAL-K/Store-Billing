<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use PDO;
use Throwable;

class Install extends Command
{
    protected $signature = 'app:install {--fresh : Drop the schema and rebuild it from scratch}';

    protected $description = 'Prepare a working copy: environment file, app key, database, schema and seed data';

    public function handle(): int
    {
        $this->components->info('Setting up '.config('app.name'));

        if (! $this->environmentFile()) {
            return self::FAILURE;
        }

        $this->applicationKey();

        if (! $this->database()) {
            return self::FAILURE;
        }

        $this->schema();
        $this->summary();

        return self::SUCCESS;
    }

    private function environmentFile(): bool
    {
        if (File::exists(base_path('.env'))) {
            $this->components->twoColumnDetail('Environment file', '<fg=gray>already present</>');

            return true;
        }

        if (! File::exists(base_path('.env.example'))) {
            $this->components->error('No .env and no .env.example to copy from.');

            return false;
        }

        File::copy(base_path('.env.example'), base_path('.env'));
        $this->components->twoColumnDetail('Environment file', '<fg=green>copied from .env.example</>');

        return true;
    }

    private function applicationKey(): void
    {
        if (config('app.key')) {
            $this->components->twoColumnDetail('Application key', '<fg=gray>already set</>');

            return;
        }

        Artisan::call('key:generate', ['--force' => true]);
        $this->components->twoColumnDetail('Application key', '<fg=green>generated</>');
    }

    /**
     * Creates the schema if the server is reachable and it is missing. SQLite
     * only needs the file to exist.
     */
    private function database(): bool
    {
        $connection = config('database.default');
        $config = config("database.connections.{$connection}");

        if ($connection === 'sqlite') {
            $path = $config['database'];

            if ($path !== ':memory:' && ! File::exists($path)) {
                File::put($path, '');
                $this->components->twoColumnDetail('Database file', '<fg=green>created</>');
            }

            return true;
        }

        $name = $config['database'];

        try {
            $server = new PDO(
                sprintf('%s:host=%s;port=%s', $connection === 'pgsql' ? 'pgsql' : 'mysql', $config['host'], $config['port']),
                $config['username'],
                $config['password'],
                [PDO::ATTR_TIMEOUT => 5, PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
            );
        } catch (Throwable $e) {
            $this->components->error("Could not reach the {$connection} server. Check the DB_ block in .env.");
            $this->line('  <fg=gray>'.$e->getMessage().'</>');

            return false;
        }

        if ($connection === 'pgsql') {
            $exists = $server->query('SELECT 1 FROM pg_database WHERE datname = '.$server->quote($name))->fetchColumn();

            if (! $exists) {
                $server->exec('CREATE DATABASE "'.str_replace('"', '', $name).'"');
                $this->components->twoColumnDetail('Database', "<fg=green>created {$name}</>");

                return true;
            }
        } else {
            $server->exec('CREATE DATABASE IF NOT EXISTS `'.str_replace('`', '', $name).'`');
        }

        $this->components->twoColumnDetail('Database', "<fg=green>{$name} ready</>");

        return true;
    }

    private function schema(): void
    {
        $this->newLine();

        Artisan::call(
            $this->option('fresh') ? 'migrate:fresh' : 'migrate',
            ['--seed' => true, '--force' => true],
            $this->output,
        );
    }

    private function summary(): void
    {
        $this->newLine();
        $this->components->info('Ready.');

        $this->components->bulletList([
            'Start everything:  <fg=cyan>composer dev</>',
            'Or separately:     <fg=cyan>php artisan serve</> and <fg=cyan>php artisan queue:work</>',
            'Front end assets:  <fg=cyan>npm install && npm run build</>',
            'Run the tests:     <fg=cyan>php artisan test</>',
        ]);

        $this->line('  Sample logins are not needed; the app has no authentication.');
        $this->line('  Try <fg=cyan>thomas@example.com</> or <fg=cyan>divya@example.com</> at the counter.');
        $this->newLine();
    }
}
