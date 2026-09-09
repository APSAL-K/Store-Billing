<?php

namespace Tests\Feature;

use App\Console\Commands\PlaceOrder;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use PDO;
use Symfony\Component\Process\Process;
use Tests\TestCase;
use Throwable;

/**
 * Requirement: if two orders for the same product land at nearly the same time
 * and only one unit is left, exactly one should succeed.
 *
 * A single-process test cannot prove that, so this one launches real, separate
 * PHP processes against a real MySQL server. It runs on a throwaway schema so
 * it never touches development data, and skips with a note if no server is
 * reachable.
 */
class ConcurrentOrderTest extends TestCase
{
    private const TILLS = 6;

    private const UNITS_IN_STOCK = 2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->skipUnlessDatabaseServerIsReachable();

        DB::setDefaultConnection('concurrency');
        Artisan::call('migrate:fresh', ['--database' => 'concurrency', '--force' => true]);
    }

    protected function tearDown(): void
    {
        if (DB::getDefaultConnection() === 'concurrency') {
            DB::purge('concurrency');
        }

        parent::tearDown();
    }

    public function test_overlapping_tills_cannot_sell_the_same_last_units(): void
    {
        $product = Product::factory()->create([
            'name' => 'Last Carton of Milk',
            'unit_price' => 68.00,
            'tax_percentage' => 0,
            'stock_on_hand' => self::UNITS_IN_STOCK,
        ]);

        Customer::factory()->create(['email' => 'rush@example.com', 'name' => 'Rush Hour']);

        $results = $this->runTillsInParallel($product);

        $placed = array_filter($results, fn (int $exitCode): bool => $exitCode === 0);
        $short = array_filter($results, fn (int $exitCode): bool => $exitCode === PlaceOrder::EXIT_SHORT_ON_STOCK);

        $this->assertCount(self::UNITS_IN_STOCK, $placed, 'Exactly as many sales as units in stock should succeed.');
        $this->assertCount(self::TILLS - self::UNITS_IN_STOCK, $short, 'Every other till should be told the stock ran out.');

        $this->assertSame(0, $product->refresh()->stock_on_hand, 'Stock must land on zero, never below it.');
        $this->assertSame(self::UNITS_IN_STOCK, Order::count());
        $this->assertSame(self::UNITS_IN_STOCK, (int) DB::table('order_items')->sum('quantity'));
    }

    /**
     * Start every till at once and wait for all of them, so the writes really do
     * overlap rather than queueing up behind each other.
     *
     * @return array<int, int> Exit code per process.
     */
    private function runTillsInParallel(Product $product): array
    {
        $processes = [];

        foreach (range(1, self::TILLS) as $till) {
            $process = new Process([
                PHP_BINARY,
                'artisan',
                'orders:place',
                '--connection=concurrency',
                '--email=rush@example.com',
                '--name=Rush Hour',
                "--item={$product->id}:1",
            ], base_path());

            $process->setTimeout(30);
            $process->start();

            $processes[$till] = $process;
        }

        return array_map(function (Process $process): int {
            $process->wait();

            return $process->getExitCode();
        }, $processes);
    }

    private function skipUnlessDatabaseServerIsReachable(): void
    {
        $config = config('database.connections.concurrency');

        try {
            $pdo = new PDO(
                "mysql:host={$config['host']};port={$config['port']}",
                $config['username'],
                $config['password'],
                [PDO::ATTR_TIMEOUT => 3, PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
            );

            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$config['database']}`");
        } catch (Throwable $e) {
            $this->markTestSkipped(
                'Concurrency test needs a reachable MySQL server (DB_HOST/DB_PORT in .env): '.$e->getMessage()
            );
        }
    }
}
