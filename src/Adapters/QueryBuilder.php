<?php

namespace DataTables\Adapters;

use Phalcon\Paginator\Adapter\QueryBuilder as PQueryBuilder;
use Phalcon\Mvc\Model\Query\Builder as PhalconQueryBuilder;

class QueryBuilder extends AdapterInterface
{

    /**
     * @var PhalconQueryBuilder
     */
    protected $builder;

    private $global_search;

    private $column_search;

    private $_bind;

    public function setBuilder($builder)
    {
        $this->builder = $builder;
    }

    public function columnExists($column, $getAlias = false)
    {
        $result = parent::columnExists($column, $getAlias);

        if ($result !== null) return $result;

        $alias = null;

        $modelClass = null;

        $from = $this->builder->getFrom();

        list($table, $alias) = strpos($column, '.')
            ? explode('.', $column)
            : [null, $column];

        if (!is_array($from)) {
            $modelClass = $from;
        } else {
            if (!$table) {
                $modelClass = array_values($from)[0];
            } elseif (array_key_exists($table, $from)) {
                $modelClass = $from[$table];
            }
        }

        if (!$modelClass) {
            $joins = $this->builder->getJoins();

            if ($joins) {
                foreach ($joins as $join) {
                    if ($table === $join[2]) {
                        $modelClass = $join[0];
                        break;
                    }
                }
            }
        }

        if (!$modelClass) {
            return $result;
        }

        /** @var \Phalcon\Mvc\Model $model */
        $model = new $modelClass;

        $attributes = $model->getModelsMetaData()->getAttributes($model);

        if (in_array($alias, $attributes, true)) {
            return $column;
        }

        return $result;
    }

    public function getResponse()
    {
        $builder = new PQueryBuilder([
            'builder' => $this->builder,
            'limit' => 1,
            'page' => 1,
        ]);

        $total = $builder->getPaginate();
        $this->global_search = [];
        $this->column_search = [];

        $this->bind('global_search', false, function ($column, $search) {
            $key = "keyg_" . str_replace(".", "", $column);
            $this->global_search[] = "{$column} LIKE :{$key}:";
            $this->_bind[$key] = "%{$search}%";
        });

        $this->bind('column_search', false, function ($column, $search) {
            $key = "keyc_" . str_replace(" ", "", str_replace(".", "", $column));
            $this->column_search[] = "{$column} LIKE :{$key}:";
            $this->_bind[$key] = "%{$search}%";
        });

        $this->bind('order', false, function ($order) {
            if (!empty($order)) {
                $this->builder->orderBy(implode(', ', $order));
            }
        });

        if (!empty($this->global_search) || !empty($this->column_search)) {
            $where = implode(' OR ', $this->global_search);
            if (!empty($this->column_search))
                $where = (empty($where) ? '' : ('(' . $where . ') AND ')) . implode(' AND ', $this->column_search);
            $this->builder->andWhere($where, $this->_bind);
        }

        $builder = new PQueryBuilder([
            'builder' => $this->builder,
            'limit' => $this->parser->getLimit($total->total_items),
            'page' => $this->parser->getPage(),
        ]);

        $filtered = $builder->getPaginate();

        return $this->formResponse([
            'total' => $total->total_items,
            'filtered' => $filtered->total_items,
            'data' => $filtered->items->toArray(),
        ]);
    }
}
