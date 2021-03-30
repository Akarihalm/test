<?php

namespace App\Console\Commands\Service;

use App\Utils\TableConfigManager;
use \Exception;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * Class CreateModel
 * @package App\Console\Commands\Service
 */

class CreateModel extends Base
{
    const MODEL_TEMPLATE_PATH = 'app/template/Model.txt';
    const MODEL_RELATION_TEMPLATE_PATH = 'app/template/ModelRelation.txt';

    const ANNOTATION_TEMPLATE = ' * @property #type# $#var#';
    const USE_TEMPLATE = 'use #classFull#;';

    const MODEL_NAMESPACE = 'App\\Models\\';
    const APP_MODEL_DIRECTORY = 'Models/';

    private $relations = [];
    private $uses = [];
    private $annotations = [];

    /**
     * @param $table
     * @return false|int
     * @throws Exception
     */
    public function handle($table)
    {
        $data = [];

        foreach ($this->getTableEnum($table) as $table => $enumList) {
            $data[$table] = [
                'class' => ucfirst(Str::camel($table)),
                'table' => $enumList['table'],
                'annotations' => [],
                'casts' => [],
                'dates' => [],
                'fillable' => [],
                'relations' => [],
                'uses' => [],
                'trait' => [],
            ];

            $data[$table]['annotations'][] = $this->formatAnnotation('id', 'int', false);

            foreach ($enumList['columns'] as $column => $detail) {
                $type = TableConfigManager::searchTypeGroup($detail['type']);
                $data[$table]['annotations'][] = $this->formatAnnotation($column, $type, empty($detail['required']));
                if (! empty($detail['foreign_table']) && ! empty($detail['foreign_column'])) {
                    $this->setRelation($table, $detail['foreign_table'], ! empty($detail['unique']));
                }
                if ('time' === $detail['type']['function']) {
                    $data[$table]['casts'][] = $this->formatKeyValue($column, 'time');
                } elseif ('Carbon' === $type) {
                    $data[$table]['dates'][] = $this->formatValue($column);
                    $data[$table]['casts'][] = $this->formatKeyValue($column, $detail['type']['function']);
                } elseif ('array' === $type) {
                    $data[$table]['casts'][] = $this->formatKeyValue($column, 'json');
                } elseif ('string' !== $type) {
                    $data[$table]['casts'][] = $this->formatKeyValue($column, $type);
                }
                $data[$table]['fillable'][] = $this->formatValue($column);
            }

            if (! empty($enumList['settings']['by_user'])) {
                $data[$table]['annotations'][] = $this->formatAnnotation('created_by', 'int');
                $data[$table]['annotations'][] = $this->formatAnnotation('updated_by', 'int');
                $data[$table]['fillable'][] = $this->formatValue('created_by');
                $data[$table]['fillable'][] = $this->formatValue('updated_by');
            }

            $data[$table]['annotations'][] = $this->formatAnnotation('created_at', 'Carbon');
            $data[$table]['annotations'][] = $this->formatAnnotation('updated_at', 'Carbon');
            if (! empty($enumList['settings']['soft_delete'])) {
                $data[$table]['annotations'][] = $this->formatAnnotation('deleted_at', 'Carbon');
                $data[$table]['uses'][] = $this->formatUse(SoftDeletes::class);
                $data[$table]['trait'][] = $this->indent(1) . 'use SoftDeletes;';
            }
        }

        foreach ($this->relations as $table => $relations) {
            foreach ($relations as $relation) {
                $data[$table]['relations'][] = $this->replaceByStorageFile(self::MODEL_RELATION_TEMPLATE_PATH, $relation);
            }
        }

        foreach ($this->annotations as $table => $annotations) {
            if (0 < count($annotations)) {
                $data[$table]['annotations'][] = '';
                $data[$table]['annotations'] = array_merge($data[$table]['annotations'], $annotations);
            }
        }

        foreach ($this->uses as $table => $uses) {
            foreach ($uses as $use) {
                $data[$table]['uses'][] = $this->formatUse($use);
            }
        }

        $modelDirectory = app_path(self::APP_MODEL_DIRECTORY);
        if (! file_exists($modelDirectory)) {
            mkdir($modelDirectory);
        }

        $arrayList = ['annotations', 'casts', 'dates', 'fillable', 'relations', 'uses', 'trait'];
        foreach ($data as $table => $values) {
            foreach ($arrayList as $key) {
                $values[$key] = isset($values[$key]) ? array_unique($values[$key]) : null;
                $data[$table][$key] = $values[$key] ? implode("\n", $values[$key]) : '';
                if ($data[$table][$key] && in_array($key, ['casts', 'dates', 'fillable', 'uses', 'trait'])) {
                    $data[$table][$key] = "\n" . $data[$table][$key];
                }
                if ('trait' === $key) {
                    $data[$table][$key] .= "\n";
                }
            }

            $path = $this->getSavePath($table);
            dump($path);

            $this->saveByStorageFile(
                self::MODEL_TEMPLATE_PATH,
                $data[$table],
                $path
            );
        }

        return 0;
    }

    /**
     * @param $table
     * @return string
     */
    private function getSavePath($table)
    {
        return app_path(self::APP_MODEL_DIRECTORY . ucfirst(Str::camel($table)) . '.php');
    }

    /**
     * @param $tableSrc
     * @param $tableDest
     * @param boolean $unique
     */
    private function setRelation($tableSrc, $tableDest, $unique = false)
    {
        $tableSrc = Str::singular($tableSrc);
        $tableDest = Str::singular($tableDest);

        if (empty($this->relations[$tableSrc])) $this->relations[$tableSrc] = [];
        if (empty($this->relations[$tableDest])) $this->relations[$tableDest] = [];

        $classSrc = ucfirst(Str::camel($tableSrc));
        $classDest = ucfirst(Str::camel($tableDest));

        $this->relations[$tableSrc][] = [
            'method' => $tableDest,
            'class' => $classDest,
            'type' => 'belongsTo',
        ];
        $this->annotations[$tableSrc][] = $this->formatAnnotation($tableDest, $classDest, false);

        $relationDest = [
            'class' => $classSrc
        ];

        // NOTE: 同じディレクトリであれば不要
        // $this->uses[$tableSrc][] = self::MODEL_NAMESPACE . $classDest;
        // $this->uses[$tableDest][] = self::MODEL_NAMESPACE . $classSrc;

        if ($unique) {
            $relationDest['type'] = 'hasOne';
            $relationDest['method'] = $tableSrc;
            $this->annotations[$tableDest][] = $this->formatAnnotation($relationDest['method'], $classSrc, false);
        } else {
            $relationDest['type'] = 'hasMany';
            $relationDest['method'] = Str::plural($tableSrc);
            $this->annotations[$tableDest][] = $this->formatAnnotation($relationDest['method'], 'Collection|' . $classSrc . '[]', false);
            $this->uses[$tableDest][] = 'Illuminate\Database\Eloquent\Collection';
        }

        $this->relations[$tableDest][] = $relationDest;
    }

    /**
     * @param $var
     * @param $type
     * @param $nullable
     * @return string|string[]
     */
    private function formatAnnotation($var, $type, $nullable = true)
    {
        if ($nullable) {
            $type .= '|null';
        }

        return $this->replace(self::ANNOTATION_TEMPLATE, [
            'type' => $type,
            'var' => $var,
        ]);
    }

    /**
     * @param $value
     * @return string
     */
    private function formatUse($value)
    {
        return $this->replace(self::USE_TEMPLATE, [
            'classFull' => $value,
        ]);
    }
}
