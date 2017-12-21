<?php

namespace Prettus\Repository\Criteria;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneOrMany;
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
    public function apply(Builder $model, RepositoryInterface $repository)
    {
        $fieldsSearchable = $repository->getFieldsSearchable();

        $search       = $this->request->get(config('repository.criteria.params.search', 'search'), null);
        $searchFields = $this->request->get(config('repository.criteria.params.searchFields', 'searchFields'), null);
        $filter       = $this->request->get(config('repository.criteria.params.filter', 'filter'), null);
        $orderBy      = $this->request->get(config('repository.criteria.params.orderBy', 'orderBy'), null);
        $sortedBy     = $this->request->get(config('repository.criteria.params.sortedBy', 'sortedBy'), 'asc');

        $sortedBy = !empty($sortedBy)
            ? $sortedBy
            : 'asc';

        if ($search && is_array($fieldsSearchable) && count($fieldsSearchable)) {
            $model = $this->processSearch($model, $repository, $searchFields, $fieldsSearchable, $search);
        }

        if (isset($orderBy) && !empty($orderBy)) {
            $model = $this->processOrderBy($model, $orderBy, $sortedBy, $repository->makeModel());
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
    protected function processSearch(
        $model,
        RepositoryInterface $repository,
        $searchFields,
        array $fieldsSearchable,
        string $search
    ) {

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
            $fields,
            $search,
            $searchData,
            $isFirstField,
            $modelForceAndWhere,
            $valueBindings
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
     * @param     Builder|Model $query
     * @param string            $orderBy
     * @param string            $sortedBy
     *
     * @param Model             $modelObject
     *
     * @return Builder|Model
     */
    protected function processOrderBy($query, string $orderBy, string $sortedBy, Model $modelObject)
    {
        $split = explode('|', $orderBy);
        if (count($split) > 1) {
            $relation = $split[0];
            $field    = $split[1];
            /**
             * relationName|field
             *
             * Ex:
             * authorOfBook|name
             *
             */

            if (!method_exists($modelObject, $relation)) {
                return $query;
            }

            $relationObj = $modelObject->$relation();

            if (!$relationObj instanceof HasOneOrMany && !$relationObj instanceof BelongsTo) {
                return $query;
            }

            if ($relationObj instanceof HasOneOrMany) {
                $foreignKey = $relationObj->getForeignKeyName();
            } else {
                $foreignKey = $relationObj->getForeignKey();
            }

            $sortTable   = $relationObj->getRelated()->getTable();
            $sortTableAs = $sortTable . '_sorting_' . mt_rand(0, 128);
            $sortColumn  = $sortTableAs . '.' . $field;
            $foreignKey  = $modelObject->getTable() . '.' . $foreignKey;
            $keyName     = $sortTableAs . '.' . $relationObj->getRelated()->getKeyName();

            $query = $query
                ->leftJoin($sortTable . ' AS ' . $sortTableAs, $keyName, '=', $foreignKey)
                ->orderBy($sortColumn, $sortedBy)
                ->addSelect([
                    $modelObject->getTable() . '.*',
                    $sortColumn . ' AS sorting_field_' . mt_rand(),
                ]);

            return $query;
        } else {
            $query = $query->orderBy($orderBy, $sortedBy);

            return $query;
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
