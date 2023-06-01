<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Support\Traits;

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Rebing\GraphQL\Tests\TestCase;

/**
 * Including this trait will **automatically** start counting SQL queries
 * for every test.
 *
 * Use `$this->assertSqlCount(..);` to test for them.
 *
 * @mixin TestCase
 */
trait SqlAssertionTrait
{
    /**
     * Recorded SQL query events.
     *
     * @var QueryExecuted[]
     */
    protected $sqlQueryEvents = [];

    protected function setupTraitForSqlAssertion(): void
    {
        $this->sqlCounterReset();

        DB::listen(function (QueryExecuted $event): void {
            $this->sqlQueryEvents[] = $event;
        });
    }

    /**
     * Assert the number of SQL queries performed.
     *
     * After "reading" this value, the counters are reset.
     *
     * @param string $msg Will only be displayed if the assertion fails
     */
    protected function assertSqlCount(int $expectedCount, string $msg = ''): void
    {
        $numSqlQueries = \count($this->sqlQueryEvents);

        if ($expectedCount === $numSqlQueries) {
            $this->sqlCounterReset();

            return;
        }

        if ($msg) {
            $msg .= "\n\n";
        }

        $msg .= \Safe\sprintf(
            "Expected number of SQL statements of %d does not match the actual value of %d\nQueries:\n\n%s\n",
            $expectedCount,
            $numSqlQueries,
            implode(
                "\n",
                array_map(
                    static function (QueryExecuted $query) {
                        return \Safe\sprintf(
                            '[%s] %s',
                            $query->connectionName,
                            $query->sql
                        );
                    },
                    $this->sqlQueryEvents
                )
            )
        );
        self::assertSame($expectedCount, $numSqlQueries, $msg);
    }

    /**
     * Assert the actual SQL queries (without bindings).
     *
     * After "reading" this value, the counters are reset.
     *
     * @param string $msg Will only be displayed if the assertion fails
     */
    protected function assertSqlQueries(string $expectedQueries, string $msg = ''): void
    {
        $expectedQueries = trim($expectedQueries);
        $actualQueries = trim(
            implode(
                "\n",
                array_map(
                    static function (QueryExecuted $query): string {
                        // Replace any numeric literals with "fake" bind
                        // placeholders. The framework recently optimized
                        // whereIn queries to contain all-only integer
                        // literals directly, which means it includes
                        // IDs which may change during multiple test
                        // runs, which we now manually need to normalize
                        return \Safe\preg_replace(
                            [
                                // Covers integers in `WHERE IN ()`
                                '/\d+(,|\))/',
                                // Covers simple `WHERE x =`
                                '/= \d+/',
                            ],
                            [
                                '?$1',
                                '= ?',
                            ],
                            $query->sql
                        ) . ';';
                    },
                    $this->sqlQueryEvents
                )
            )
        );

        $this->sqlCounterReset();

        if (!$msg) {
            $msg = 'SQL queries mismatch';
        }

        self::assertSame($expectedQueries, $actualQueries, $msg);
    }

    protected function sqlCounterReset(): void
    {
        $this->sqlQueryEvents = [];
    }
}
