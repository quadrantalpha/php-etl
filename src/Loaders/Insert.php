<?php

namespace Marquine\Etl\Loaders;

use Generator;
use Marquine\Etl\Database\Statement;
use Marquine\Etl\Database\Transaction;

class Insert extends Loader
{
    /**
     * The connection name.
     *
     * @var string
     */
    public $connection = 'default';

    /**
     * The columns to insert.
     *
     * @var array
     */
    public $columns = [];

    /**
     * Indicates if the table has timestamps columns.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * Transaction mode.
     *
     * @var mixed
     */
    public $transaction = 'single';

    /**
     * The database table.
     *
     * @var string
     */
    protected $table;

    /**
     * Timestamps columns value.
     *
     * @var string
     */
    protected $time;

    /**
     * The insert statement.
     *
     * @var \PDOStatement
     */
    protected $insert;

    /**
     * Load data into the given destination.
     *
     * @param  \Generator  $data
     * @param  string  $destination
     * @return void
     */
    public function load(Generator $data, $destination)
    {
        $this->normalizeColumns($data);

        $this->table = $destination;

        $this->time = date('Y-m-d G:i:s');

        Transaction::connection($this->connection)->mode($this->transaction)->data($data)->run(function ($row) {
            $this->insert(array_intersect_key($row, $this->columns));
        });
    }

    /**
     * Execute the insert statement.
     *
     * @param  array  $row
     * @return void
     */
    protected function insert($row)
    {
        if (! $this->insert) {
            $this->insert = Statement::connection($this->connection)->insert($this->table, $this->columns)->prepare();
        }

        if ($this->timestamps) {
            $row['created_at'] = $this->time;
            $row['updated_at'] = $this->time;
        }

        $this->insert->execute($row);
    }

    /**
     * Normalize columns.
     *
     * @param  \Generator  $data
     * @return void
     */
    protected function normalizeColumns($data)
    {
        if (empty($this->columns)) {
            $this->columns = array_keys($data->current());
        }

        if ($this->timestamps) {
            $this->columns[] = 'created_at';
            $this->columns[] = 'updated_at';
        }

        $this->columns = array_combine($this->columns, $this->columns);
    }
}
