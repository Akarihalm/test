<?php

namespace App\Service;

use App\Utils\TimeMemory;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use SplFileObject;

/**
 * Class BaseImport
 * @package App\Service
 *
 * @property Model $model
 * @property Collection|Model[] $registered
 *
 * @property Collection $temp
 * @property Collection $new
 * @property Collection $edit
 * @property Collection $delete
 */

abstract class BaseImport
{
    const MODEL = null;
    const UNIQUE_COLUMNS = [];

    protected $model;
    protected $registered;

    protected $temp;
    protected $new;
    protected $edit;
    protected $delete;
    protected $count = [];

    protected $timeKey;
    protected $allColumns;
    protected $enablePreload = true;

    const UNIQUE_COLUMN_GLUE = ',';
    const COUNT_DISPLAY_BY = 1000;
    const PERMIT_UNIQUE_BLANK = false;
    const CHUNK_DEFAULT_SIZE = 100;

    const PRELOAD_LIMIT = 1000000;

    const FORCE_ADJUST_LIMIT = 0;
    const LOAD_DATA_LOCAL_INFILE = false;

    const DELETE_KEY = null;
    const DELETE_VALUE = null;

    /**
     * BaseImport constructor.
     *
     * @throws Exception
     */
    public function __construct()
    {
        if (empty(static::MODEL)) {
            throw new Exception('Not found model class');
        }

        if (empty(static::UNIQUE_COLUMNS)) {
            throw new Exception('Not found model unique columns');
        }

        if (count(static::UNIQUE_COLUMNS) > 2) {
            throw new Exception('unique columns is less than 2' . json_encode(static::UNIQUE_COLUMNS));
        }

        $this->timeKey = static::MODEL . '_' . Str::random();
        TimeMemory::setup($this->timeKey);

        $modelName = static::MODEL;
        $this->model = new $modelName();

        if (static::PRELOAD_LIMIT < $this->getRecordColumnAll()) {
            $this->enablePreload = false;
        } else {
            $this->registered = $this->model->newQuery()
                ->select(array_merge([$this->model->getKeyName()], $this->model->getFillable()))
                ->get()
                ->keyBy(function($item) {
                    return $this->getUniqueString($item);
                });
        }

        if (static::LOAD_DATA_LOCAL_INFILE) {
            $table = $this->model->getTable();
            $columns = Schema::connection('mysql')->getColumnListing($table);
            $this->allColumns = collect($columns)->flatMap(function ($key) {
                return [$key => 'NULL'];
            })->toArray();
        }

        $this->clear();
        $this->clearCount();
    }

    /**
     * @return bool
     */
    public function clear()
    {
        $this->temp = new Collection();
        $this->new = new Collection();
        $this->edit = new Collection();
        $this->delete = new Collection();

        return true;
    }

    /**
     * @return bool
     */
    public function clearCount()
    {
        $this->count = [
            'new' => 0,
            'edit' => 0,
            'delete' => 0,
            'target' => 0,
            'all' => 0,
        ];

        return true;
    }

    /**
     * @param $data
     * @param bool $allCount
     * @throws Exception
     */
    public function set($data, $allCount = true)
    {
        if ($allCount) {
            ++$this->count['all'];
        }

        try {
            $format = $this->format($data);
            if (is_null($format)) {
                return;
            }
            $format = $this->castAll($format);
        } catch (Exception $e) {
            dump($e->getMessage());
            dump(json_encode($data));
            return;
        }

        if ($this->enablePreload) {
            $this->setDivision($this->pullRegistered($format), $format);
        } else {
            $this->temp->push($format);
        }

        ++$this->count['target'];

        if (static::FORCE_ADJUST_LIMIT && 0 === $this->count['target'] % static::FORCE_ADJUST_LIMIT) {
            $this->adjust(false);
            TimeMemory::countDisplay(
                $this->timeKey,
                '[loading] ' . $this->getCountString() . ' ---',
                false
            );
            $this->clear();
        }
    }

    /**
     * @param bool $complete
     * @return bool
     * @throws Exception
     */
    public function adjust($complete = true)
    {
        if (! $this->enablePreload) {
            $this->resetNewEdit();
        }

        $this->create();
        $this->edit();
        $this->delete();

        if ($complete) {
            $this->complete();
        }

        return true;
    }

    /**
     * @return int
     */
    private function create()
    {
        $timeKey = $this->timeKey;
        $count = $this->new->count();
        $this->count['new'] += $count;
        if (static::LOAD_DATA_LOCAL_INFILE) {
            $this->insertByFile($this->new);
        } else {
            $chunkSize = static::CHUNK_DEFAULT_SIZE;
            $this->new->chunk($chunkSize)->each(function (Collection $values) use ($chunkSize, $timeKey) {
                $this->model->newQuery()->insert($values->toArray());
            });
        }
        for ($i=0; $i<$count; $i++) {
            TimeMemory::countUp($timeKey, static::COUNT_DISPLAY_BY);
        }
        return $count;
    }

    /**
     * @param $list
     */
    private function insertByFile(Collection $list)
    {
        $columns = $this->allColumns;
        $filePath = storage_path(Str::random() . '.csv');
        $file = new SplFileObject($filePath, 'w');
        $list->each(function ($item) use ($file, $columns) {
            foreach ($item as $key => $value) {
                if (is_null($value)) {
                    $item[$key] = 'NULL';
                }
            }
            $item = array_merge($columns, $item);
            $file->fputcsv($item);
        });

        DB::statement("LOAD DATA LOCAL INFILE '$filePath' INTO TABLE `{$this->model->getTable()}` FIELDS terminated by ',' ENCLOSED BY '\"' ESCAPED BY ''");
        unlink($filePath);
    }

    /**
     * @return int
     */
    private function edit()
    {
        $timeKey = $this->timeKey;
        $count = $this->edit->count();
        $this->count['edit'] += $count;
        $this->edit->each(function(Model $model) use ($timeKey) {
            $model->save();
            TimeMemory::countUp($timeKey, static::COUNT_DISPLAY_BY);
        });
        return $count;
    }

    /**
     * @return int
     * @throws Exception
     */
    protected function delete()
    {
        $timeKey = $this->timeKey;
        $count = $this->delete->count();
        $this->count['delete'] += $count;
        $this->delete->each(function(Model $model) use ($timeKey) {
            $model->delete();
            TimeMemory::countUp($timeKey, static::COUNT_DISPLAY_BY);
        });
        return $count;
    }

    /**
     * @return int
     * @throws Exception
     */
    public function deleteNoExist()
    {
        if (! $this->enablePreload) {
            throw new Exception('cannot delete too big table');
        }

        $this->delete->merge($this->registered);

        return $this->delete();
    }

    /**
     * @return bool
     */
    public function complete()
    {
        TimeMemory::countDisplay(
            $this->timeKey,
            '[complete] ' . $this->getCountString() . ' ---',
            false
        );
        dump('');

        $this->clear();
        $this->clearCount();

        return true;
    }

    /**
     * @return string
     */
    protected function getCountString()
    {
        return collect($this->count)->map(function ($value, $key) {
            return $key . ': ' . number_format($value);
        })->implode(', ');
    }

    /**
     * @return float|int
     */
    private function getRecordColumnAll()
    {
        return count($this->model->getFillable()) * $this->model->newQuery()->count();
    }

    /**
     * resetNewEdit
     */
    private function resetNewEdit()
    {
        if (2 === count(static::UNIQUE_COLUMNS)) {
            $group = [
                $this->temp->groupBy(static::UNIQUE_COLUMNS[0]),
                $this->temp->groupBy(static::UNIQUE_COLUMNS[1])
            ];
            $groupCol = $group[0]->count() < $group[1]->count() ? 0 : 1;
            $group[$groupCol]->each(function (Collection $items, $key) use ($groupCol) {
                $mailCol = static::UNIQUE_COLUMNS[$groupCol];
                $subCol = static::UNIQUE_COLUMNS[1 - $groupCol];
                $registered = $this->model->newQuery()
                    ->select(array_merge([$this->model->getKeyName()], $this->model->getFillable()))
                    ->where($mailCol, $key)
                    ->whereIn($subCol, $items->pluck($subCol))
                    ->get()
                    ->keyBy($subCol);
                $items->each(function ($item) use ($subCol, $registered) {
                    $this->setDivision(
                        isset($registered[$item[$subCol]]) ? $registered[$item[$subCol]] : null,
                        $item
                    );
                });
            });
        } else {
            $uniqueCol = static::UNIQUE_COLUMNS[0];
            $registered = $this->model->newQuery()
                ->select(array_merge([$this->model->getKeyName()], $this->model->getFillable()))
                ->whereIn($uniqueCol, $this->temp->pluck($uniqueCol))
                ->get()
                ->keyBy($uniqueCol);
            $this->temp->each(function ($item) use ($uniqueCol, $registered) {
                $this->setDivision(
                    isset($registered[$item[$uniqueCol]]) ? $registered[$item[$uniqueCol]] : null,
                    $item
                );
            });
        }

        $this->temp = new Collection();
    }

    /**
     * @param $item
     * @return string
     */
    public function getUniqueString($item)
    {
        return implode(static::UNIQUE_COLUMN_GLUE, $this->getUniqueValue($item));
    }

    /**
     * @param $item
     * @return array
     */
    private function getUniqueValue($item)
    {
        return collect(static::UNIQUE_COLUMNS)->map(function($column) use ($item) {
            $result = is_object($item) ? $item->$column : $item[$column];
            if (empty($result)) {
                if (static::PERMIT_UNIQUE_BLANK) {
                    return '';
                }
                throw new Exception('Not found unique values : ' . $column . ' in ' . json_encode($item));
            }
            return $result;
        })->toArray();
    }

    /**
     * @param $data
     * @return Model|null
     */
    private function pullRegistered($data)
    {
        if (! $this->enablePreload) {
            return null;
        }

        $uniqueKey = $this->getUniqueString($data);
        if (empty($this->registered[$uniqueKey])) {
            return null;
        }

        $model = $this->registered[$uniqueKey];
        unset($this->registered[$uniqueKey]);

        return $model;
    }

    /**
     * @param Model|null $model
     * @param $data
     */
    private function setDivision(?Model $model, $data)
    {
        $isDelete = $this->isDelete($data);

        if (empty($model)) {
            if ($isDelete) {
                return;
            }
            $this->new->push(
                $this->insertFormat($data)
            );
            return;
        }

        if ($isDelete) {
            $this->delete->push($model);
        }

        $newModel = $this->fillModel($model, $data);

        if ($newModel->isDirty()) {
            $this->edit->push($newModel);
        }
    }

    /**
     * @param $data
     * @return bool
     */
    private function isDelete($data)
    {
        return ! is_null(static::DELETE_KEY) && $data[static::DELETE_KEY] === static::DELETE_VALUE;
    }

    /**
     * @param Model $model
     * @param $data
     * @return Model
     */
    protected function fillModel(Model $model, $data)
    {
        $casts = $this->model->getCasts();
        foreach ($data as $key => $value) {
            if (empty($casts[$key])) {
                continue;
            }
            if ('json' === $casts[$key]) {
                if (json_decode($value) === $model->$key) {
                    unset($data[$key]);
                } else {
                    $data[$key] = json_decode($value);
                }
            } elseif ('set' === $casts[$key]) {
                $new = explode(',', $value);
                $old = explode(',', $model->$key);
                sort($new);
                sort($old);
                if ($new === $old) {
                    unset($data[$key]);
                }
            }
        }

        return $model->fill($data);
    }

    /**
     * @param $data
     * @return mixed
     */
    protected function insertFormat($data)
    {
        $now = now()->format('Y-m-d H:i:s');
        $data['created_at'] = $now;
        $data['updated_at'] = $now;

        return $data;
    }

    /**
     * @param array $data
     * @return array
     */
    protected function castAll(array $data)
    {
        $casts = $this->model->getCasts();
        foreach ($data as $key => $value) {
            if (empty($casts[$key])) {
                continue;
            }
            $data[$key] = $this->cast($casts[$key], $value);
        }

        return $data;
    }

    /**
     * @param $type
     * @param $value
     * @return bool|float|int|mixed|string
     */
    private function cast($type, $value)
    {
        if (is_null($value) || '' === $value) {
            return null;
        }

        switch ($type) {
            case 'int':
            case 'integer':
                return (int) $value;
            case 'real':
            case 'float':
            case 'double':
                return $this->model->fromFloat($value);
            case 'decimal':
                return $this->model->asDecimal($value, explode(':', $type, 2)[1]);
            case 'string':
                return (string) $value;
            case 'bool':
            case 'boolean':
                return (bool) $value;
            case 'object':
            case 'array':
            case 'json':
                return json_encode($value);
            case 'set':
                return $value ? implode(',', $value) : '';
            case 'date':
                return Carbon::parse($value)->format('Y-m-d');
            case 'datetime':
            case 'custom_datetime':
            case 'timestamp':
                return Carbon::parse($value)->format('Y-m-d H:i:s');
            case 'time':
                return Carbon::parse(sprintf('%06d', $value))->format('H:i:s');
        }

        return $value;
    }

    /**
     * @param array $data
     * @return array
     */
    abstract protected function format(array $data);
}
