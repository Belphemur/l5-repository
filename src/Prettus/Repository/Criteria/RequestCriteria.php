<?php
namespace Prettus\Repository\Criteria;

use Cumulus\Repositories\ISearchValueBindable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Prettus\Repository\Contracts\RepositoryInterface;
use Prettus\Repository\Contracts\SearchableBindingInterface;

/**
 * Class RequestCriteria
 *
 * @package Cumulus\Repositories\Criteria
 */
class RequestCriteria extends IncludeCriteria
{



    /**
     * Apply criteria in query repository
     *
     * @param         Builder|Model                          $model *
     * @param SearchableBindingInterface|RepositoryInterface $repository
     *
     * @return mixed
     */
    public function apply($model, RepositoryInterface $repository)
    {
        $fieldsSearchable = $repository->getFieldsSearchable();

        $search       = $this->request->get(config('repository.criteria.params.search', 'search'), null);
        $searchFields = $this->request->get(config('repository.criteria.params.searchFields', 'searchFields'), null);
        $filter       = $this->request->get(config('repository.criteria.params.filter', 'filter'), null);
        $orderBy      = $this->request->get(config('repository.criteria.params.orderBy', 'orderBy'), null);
        $sortedBy     = $this->request->get(config('repository.criteria.params.sortedBy', 'sortedBy'), 'asc');

        $sortedBy     = !empty($sortedBy)
            ? $sortedBy
            : 'asc';

        if ($search && is_array($fieldsSearchable) && count($fieldsSearchable)) {
            $model = $this->processSearch($model, $repository, $searchFields, $fieldsSearchable, $search);
        }

        if (isset($orderBy) && !empty($orderBy)) {
            $model = $this->processOrderBy($model, $orderBy, $sortedBy);
        }

        if (isset($filter) && !empty($filter)) {
            $model = $this->processFilter($model, $filter);
        }

        return parent::apply($model, $repository);
    }

    /**
     * @param $search
     *
     * @return array
     */
    protected function parserSearchData($search)
    {
        $searchData = [];

        if (stripos($search, ':')) {
            $fields = explode(';', $search);

            foreach ($fields as $row) {
                try {
                    list($field, $value) = explode(':', $row);
                    $searchData[$field] = $value;
                } catch (\Exception $e) {
                    //Surround offset error
                }
            }
        }

        return $searchData;
    }

    /**
     * @param $search
     *
     * @return null
     */
    protected function parserSearchValue($search)
    {

        if (stripos($search, ';') || stripos($search, ':')) {
            $values = explode(';', $search);
            foreach ($values as $value) {
                $s = explode(':', $value);
                if (count($s) == 1) {
                    return $s[0];
                }
            }

            return null;
        }

        return $search;
    }


    protected function parserFieldsSearch(array $fields = [], array $searchFields = null)
    {
        if (!is_null($searchFields) && count($searchFields)) {
            $acceptedConditions = config('repository.criteria.acceptedConditions', [
                '=',
                'like',
            ]);
            $originalFields     = $fields;
            $fields             = [];

            foreach ($searchFields as $index => $field) {
                $field_parts    = explode(':', $field);
                $temporaryIndex = array_search($field_parts[0], $originalFields);

                if (count($field_parts) == 2) {
                    if (in_array($field_parts[1], $acceptedConditions)) {
                        unset($originalFields[$temporaryIndex]);
                        $field                  = $field_parts[0];
                        $condition              = $field_parts[1];
                        $originalFields[$field] = $condition;
                        $searchFields[$index]   = $field;
                    }
                }
            }

            foreach ($originalFields as $field => $condition) {
                if (is_numeric($field)) {
                    $field     = $condition;
                    $condition = "=";
                }
                if (in_array($field, $searchFields)) {
                    $fields[$field] = $condition;
                }
            }

            if (count($fields) == 0) {
                throw new \Exception(trans('repository::criteria.fields_not_accepted', ['field' => implode(',', $searchFields)]));
            }

        }

        return $fields;
    }

    protected function parseBinding(array $bindings, string $value, string $field)
    {
        try {
            if (!isset($bindings[$field])) {
                return $value;
            }

            return $bindings[$field]($value);
        } catch (\Exception $e) {
        }
    }

    /**
     * Process the search
     *
     * @param     Builder|Model      $model
     * @param RepositoryInterface    $repository
     * @param   string|string[]|null $searchFields
     * @param array                  $fieldsSearchable
     * @param string                 $search
     *
     * @return Builder|Model
     */
    protected function processSearch($model, RepositoryInterface $repository, $searchFields, array $fieldsSearchable,
                                     string $search)
    {
        $valueBindings = [];

        if ($repository instanceof SearchableBindingInterface) {
            $valueBindings = $repository->getFieldBindings();
        }

        $searchFields       = is_array($searchFields) || is_null($searchFields)
            ? $searchFields
            : explode(';', $searchFields);
        $fields             = $this->parserFieldsSearch($fieldsSearchable, $searchFields);
        $isFirstField       = true;
        $searchData         = $this->parserSearchData($search);
        $search             = $this->parserSearchValue($search);
        $modelForceAndWhere = false;

        $model = $model->where(function ($query) use (
            $fields, $search, $searchData, $isFirstField, $modelForceAndWhere, $valueBindings
        ) {
            /** @var Builder $query */

            foreach ($fields as $field => $condition) {

                if (is_numeric($field)) {
                    $field     = $condition;
                    $condition = "=";
                }

                $value = null;

                $condition = trim(strtolower($condition));

                if (isset($searchData[$field])) {
                    $value = $searchData[$field];
                } else {
                    $value = $search;
                }

                $value = $this->parseBinding($valueBindings, $value, $field);
                if (!$value) {
                    continue;
                }
                $value = ($condition == "like" || $condition == "ilike")
                    ? '%' . $value . '%'
                    : $value;


                $relation = null;
                if (stripos($field, '.')) {
                    $explode  = explode('.', $field);
                    $field    = array_pop($explode);
                    $relation = implode('.', $explode);
                }
                $modelTableName = $query->getModel()->getTable();
                if ($isFirstField || $modelForceAndWhere) {
                    if (!is_null($value)) {
                        if (!is_null($relation)) {
                            $query->whereHas($relation, function ($query) use ($field, $condition, $value) {
                                $query->where($field, $condition, $value);
                            });
                        } else {
                            $query->where($modelTableName . '.' . $field, $condition, $value);
                        }
                        $isFirstField = false;
                    }
                } else {
                    if (!is_null($value)) {
                        if (!is_null($relation)) {
                            $query->orWhereHas($relation, function ($query) use ($field, $condition, $value) {
                                $query->where($field, $condition, $value);
                            });
                        } else {
                            $query->orWhere($modelTableName . '.' . $field, $condition, $value);
                        }
                    }
                }
            }
        });

        return $model;
    }

    /**
     * Process order by
     *
     * @param     Builder|Model $model
     * @param string            $orderBy
     * @param string            $sortedBy
     *
     * @return Builder|Model
     */
    protected function processOrderBy($model, string $orderBy, string $sortedBy)
    {
        $split = explode('|', $orderBy);
        if (count($split) > 1) {
            /*
             * ex.
             * products|description -> join products on current_table.product_id = products.id order by description
             *
             * products:custom_id|products.description -> join products on current_table.custom_id = products.id order
             * by products.description (in case both tables have same column name)
             */
            $table      = $model->getModel()->getTable();
            $sortTable  = $split[0];
            $sortColumn = $split[1];

            $split = explode(':', $sortTable);
            if (count($split) > 1) {
                $sortTable = $split[0];
                $keyName   = $table . '.' . $split[1];
            } else {
                /*
                 * If you do not define which column to use as a joining column on current table, it will
                 * use a singular of a join table appended with _id
                 *
                 * ex.
                 * products -> product_id
                 */
                $prefix  = rtrim($sortTable, 's');
                $keyName = $table . '.' . $prefix . '_id';
            }

            $model = $model
                ->leftJoin($sortTable, $keyName, '=', $sortTable . '.id')
                ->orderBy($sortColumn, $sortedBy)
                ->addSelect($table . '.*');

            return $model;
        } else {
            $model = $model->orderBy($orderBy, $sortedBy);

            return $model;
        }
    }

    /**
     * Process Filter
     *
     * @param  Builder|Model $model
     * @param string         $filter
     *
     * @return Builder|Model
     */
    protected function processFilter($model, string $filter)
    {
        if (is_string($filter)) {
            $filter = explode(';', $filter);
        }

        $model = $model->select($filter);

        return $model;
    }
}
