<?php

namespace App\Service;

use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

/**
 * Class BaseResource
 * @package App\Service
 */

class BaseResource extends JsonResource
{
    /** -----------------
     * 継承先で定義する定数
     * ----------------- */

    const TARGET_MODEL = '';
    const EMBED_RESOURCES = [];
    const DEFAULT_FIELDS = [];
    const FORCE_WITHOUT_FIELDS = [];
    const ENUM_FIELDS = [];
    const ENUM_VALUE_SUFFIX = '';

    /**
     * @var Model $model
     */
    protected $model;

    /**
     * @var array $fields
     */
    protected $fields = null;

    /**
     * @var array $embed
     */
    protected $embed = null;

    /**
     * Resources namespace
     */
    const RESOURCE_NAMESPACE_PREFIX = 'App\\Resources\\';

    /**
     * Datetime format
     */
    const DATE_TIME_DISPLAY_FORMAT = 'Y-m-d H:i:s';

    /**
     * Project constructor.
     * @param $resource
     */
    public function __construct($resource)
    {
        parent::__construct($resource);

        $targetModel = static::TARGET_MODEL;
        $this->model = new $targetModel();
    }

    /**
     * @param array $fields
     * @return $this
     */
    public function setFields(array $fields = [])
    {
        $this->fields = $fields;
        return $this;
    }

    /**
     * @param array $embed
     * @return $this
     */
    public function setEmbed(array $embed = [])
    {
        $this->embed = $embed;
        return $this;
    }

    /**
     * @param Request $request
     * @return array
     */
    public function getFields(Request $request)
    {
        if (isset($this->fields)) {
            return $this->fields;
        }

        if ($fields = $request->input('fields')) {
            return explode(',', $fields);
        }

        return [];
    }

    /**
     * @param Request $request
     * @return array
     */
    public function getEmbed(Request $request)
    {
        if (isset($this->embed)) {
            return $this->embed;
        }

        if ($embed = $request->input('embed')) {
            return explode(',', $embed);
        }

        return [];
    }

    /**
     * @param $embed
     * @return mixed
     */
    protected function loadMissing($embed)
    {
        $load = array_intersect(
            array_keys(static::EMBED_RESOURCES),
            $embed
        );

        return $this->resource->loadMissing($load);
    }

    /**
     * @return Response
     */
    protected function authorize()
    {
        return Gate::authorize('view', $this->resource);
    }

    /**
     * Transform the resource into an array.
     *
     * @param  $request
     * @return array
     */
    final public function toArray($request)
    {
        $this->authorize();

        $embed = $this->getEmbed($request);
        $field = $this->getFields($request);

        $this->loadMissing($embed);

        $returnFields = $this->pickupFields(
            $this->getAllFields($field, $embed),
            $field
        );

        $returnFields = $this->exceptFields($returnFields);

        $result = $this->format(
            $this->only($returnFields)
        );

        if ($embed) {
            $result = array_merge($result, $this->embedRelation($embed));
        }

        return $result;
    }

    /**
     * @param array $fields
     * @param array $embed
     * @return array
     */
    protected function getAllFields(array $fields, array $embed)
    {
        $result = array_merge(
            array_keys($this->model->getCasts()),
            $this->model->getFillable()
        );

        $result = array_diff($result, $this->model->getHidden());

        if ($this->model->usesTimestamps()) {
            $result[] = $this->model->getCreatedAtColumn();
            $result[] = $this->model->getUpdatedAtColumn();
        }

        foreach (array_keys(static::EMBED_RESOURCES) as $key) {
            if (in_array($key, $fields) || in_array($key, $embed)) {
                $result[] = $key;
            }
        }

        return $result;
    }

    /**
     * @param array $fields
     * @param array $selected
     * @return array
     */
    protected function pickupFields(array $fields, $selected = [])
    {
        if (empty($selected) && empty(static::DEFAULT_FIELDS)) {
            return $fields;
        }

        return array_values(array_intersect(
            $fields,
            $selected ? $selected : static::DEFAULT_FIELDS
        ));
    }

    /**
     * @param $fields
     * @return array
     */
    protected function exceptFields($fields)
    {
        if (empty(static::FORCE_WITHOUT_FIELDS)) {
            return $fields;
        }

        return array_filter($fields, function ($field) {
            return ! in_array($field, static::FORCE_WITHOUT_FIELDS);
        });
    }

    /**
     * @param array $embed
     * @return mixed|null
     *
     * @uses \App\Service\BaseResource::setFields()
     * @uses \App\Service\BaseResource::setEmbed()
     */
    protected function embedRelation(array $embed)
    {
        $result = [];
        $format = $this->formatEmbed($embed);

        foreach ($format as $relation => $fieldEmbed) {
            if ($resource = $this->getRelation($relation)) {
                $result[$relation] = $this->setFieldEmbed($resource, $fieldEmbed);
            }
        }

        return $result;
    }

    /**
     * @param $relation
     * @return $this|false|null
     */
    protected function getRelation($relation)
    {
        if (! method_exists($this->model, $relation)) {
            return false;
        }

        if (! isset($this->$relation)) {
            return false;
        }

        if ($resource = $this->getResourceObject($relation)) {
            return $resource;
        }

        return false;
    }

    /**
     * @param array $embed
     * @return array
     */
    protected function formatEmbed(array $embed)
    {
        $result = [];

        foreach ($embed as $column) {
            $valueArray = explode('.', $column);
            $relation = array_shift($valueArray);

            if (!isset($result[$relation])) {
                $result[$relation] = ['fields' => [], 'embed' => []];
            }

            if (empty($valueArray)) {
                continue;
            }

            if (1 === count($valueArray)) {
                $value = current($valueArray);
                $key = $this->getResourceObject($value) ? 'embed' : 'fields';
            } else {
                $value = implode('.', $valueArray);
                $key = 'embed';
            }

            $result[$relation][$key][] = $value;
        }

        return $result;
    }

    /**
     * @param $relation
     * @return static|null
     */
    protected function getResourceObject($relation)
    {
        $class = $this->getResourceClass($relation);

        if (! class_exists($class)) {
            return null;
        }

        $relationObject = $this->$relation;
        $hasManyRelation = $relationObject instanceof Collection;
        if ($relationProperty = $this->getRelationProperty($relationObject)) {
            $property = $relationProperty['value'];
            $add = clone $this->resource;
            unset($add->$relation);
            if ($relationProperty['type'] === 'hasMany') {
                $add = new Collection([$add]);
            }
            if ($hasManyRelation) {
                $relationObject = $relationObject->map(function (Model $model) use ($property, $add) {
                    $model->$property = $add;
                    return $model;
                });
            } else {
                $relationObject->$property = $add;
            }
        }

        return $hasManyRelation
            ? $class::collection($relationObject)
            : new $class($relationObject);
    }

    /**
     * @param Model|Collection|null $sourceModel
     * @return array|null
     */
    protected function getRelationProperty($sourceModel)
    {
        $model = $sourceModel instanceof Collection
            ? $sourceModel->first()
            : $sourceModel;

        if ($belongsTo = $this->getBelongsToCurrentModel($model)) {
            return ['type' => 'belongsTo', 'value' => $belongsTo];
        }

        if ($hasMany = $this->getHasManyCurrentModel($model)) {
            return ['type' => 'hasMany', 'value' => $hasMany];
        }

        return null;
    }

    /**
     * @param Model|null $sourceModel
     * @return string|null
     */
    protected function getBelongsToCurrentModel(?Model $sourceModel)
    {
        if (! $sourceModel) {
            return false;
        }

        $currentTable = Str::singular($this->resource->getTable());

        if (method_exists($sourceModel, $currentTable)) {
            return $sourceModel->$currentTable() instanceof BelongsTo ? $currentTable : false;
        }

        return false;
    }

    /**
     * @param Model|null $sourceModel
     * @return false|string
     */
    protected function getHasManyCurrentModel(?Model $sourceModel)
    {
        if (! $sourceModel) {
            return false;
        }

        $currentTable = Str::plural($this->resource->getTable());

        if (method_exists($sourceModel, $currentTable)) {
            return $sourceModel->$currentTable() instanceof HasMany ? $currentTable : false;
        }

        return false;
    }

    /**
     * @param $relation
     * @return mixed
     */
    protected function getResourceClass($relation)
    {
        if (isset(static::EMBED_RESOURCES[$relation])) {
            return static::EMBED_RESOURCES[$relation];
        }

        return static::RESOURCE_NAMESPACE_PREFIX . ucfirst(Str::camel(Str::singular($relation)));
    }

    /**
     * @param $resource
     * @param array $fieldEmbed
     * @return mixed
     */
    protected function setFieldEmbed($resource, array $fieldEmbed)
    {
        if ($resource instanceof self) {
            $resource
                ->setFields($fieldEmbed['fields'])
                ->setEmbed($fieldEmbed['embed']);

            return $resource;
        }

        if (! ($resource instanceof ResourceCollection)) {
            return $resource;
        }

        foreach ($resource as $res) {
            if ($res instanceof self) {
                $res->setFields($fieldEmbed['fields'])
                    ->setEmbed($fieldEmbed['embed']);
            }
        }

        return $resource;
    }

    /**
     * @param array $fields
     * @return array
     */
    protected function only(array $fields)
    {
        $result = [];

        foreach ($fields as $column) {
            if (isset($this->$column)) {
                $result[$column] = $this->$column;
            }
        }

        return $result;
    }

    /**
     * @param array $result
     * @return array|void
     */
    public function format(array $result)
    {
        $result = $this->formatDateTime($result);
        $result = $this->formatEnum($result);
        return $result;
    }

    /**
     * @param array $result
     * @return array
     */
    protected function formatDateTime(array $result)
    {
        foreach ($result as $key => $value) {
            if ($value instanceof Carbon) {
                $result[$key] = $value->format(static::DATE_TIME_DISPLAY_FORMAT);
            }
        }

        return $result;
    }

    /**
     * @param array $result
     * @return array
     */
    protected function formatEnum(array $result)
    {
        /** @var BaseEnum $enumClass */
        foreach ($this->getEnumClass() as $col => $enumClass) {
            if (! isset($result[$col])) {
                continue;
            }
            $key = $col . static::ENUM_VALUE_SUFFIX;
            if (is_array($result[$col])) {
                $result[$key] = collect($result[$col])->map(function($value) use ($enumClass) {
                    return $enumClass::get($value)->value();
                });
            } else {
                $result[$key] = $enumClass::get($result[$col])->value();
            }
        }

        return $result;
    }

    /**
     * @return array
     */
    protected function getEnumClass()
    {
        if (static::ENUM_FIELDS) {
            return static::ENUM_FIELDS;
        }

        return collect($this->getColumnSettings())->flatMap(function ($column, $key) {
            return [$key => isset($column['enum']) ? $column['enum'] : null];
        })->filter()->all();
    }

    /**
     * @return array
     */
    protected function getColumnSettings()
    {
        $tableSingular = Str::singular($this->model->getTable());

        return config('table.' . $tableSingular . '.columns', []);
    }

    /**
     * @param Model|array $model
     * @return array|void
     */
    public static function staticFormat($model)
    {
        $value = is_array($model) ? $model : $model->toArray();

        return (new static($model))->format($value);
    }
}
