<?php

namespace App\Console\Commands\Service;

use App\Utils\TableConfigManager;
use App\Utils\TemplateManager;
use Carbon\Carbon;
use Exception;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Class CreateMigration
 * @package App\Console\Commands\Service
 *
 * @property SplFileInfo[] $migrationFiles
 */

class CreateMigration extends Base
{
    const TEMPLATE = 'app/template/Migration.txt';
    const TEMPLATE_FOREIGN = 'app/template/MigrationForeignKey.txt';

    const CREATE_CLASS_PREFIX = 'Create';
    const ADD_FOREIGN_CLASS_PREFIX = 'AddForeignKey';
    const CLASS_SUFFIX = 'Table';

    const DEST_DIRECTORY = 'migrations' . DIRECTORY_SEPARATOR;

    const FIRST_CREATE_CONTENT = '            $table';

    const DEFAULT_ON_UPDATE = 'NO ACTION';
    const DEFAULT_ON_DELETE = 'NO ACTION';

    protected $foreignKeyConstraint = [];

    protected $migrationFiles = [];

    /**
     * CreateMigration constructor.
     * @param User $user
     * @param boolean $orderWrite
     */
    public function __construct(User $user, $orderWrite = true)
    {
        parent::__construct($user, $orderWrite);

        $this->migrationFiles = File::files(database_path('migrations'));
    }

    /**
     * @param $table
     * @return false|int
     * @throws Exception
     */
    public function handle($table)
    {
        foreach ($this->getTableEnum($table) as $table => $enumList) {
            $class = $this->formatClass($enumList['table']);
            $this->createMigration($class, $enumList);
        }

        sleep(1);

        $foreignKeyList = collect($this->foreignKeyConstraint)->groupBy('table_src')->toArray();
        foreach ($foreignKeyList as $values) {
            $class = $this->formatForeignKeyClass(current($values)['table_src'], Arr::pluck($values, 'column_src'));
            $this->createForeignMigration($class, $values);
        }

        return 0;
    }

    /**
     * @param $class
     * @param $enumList
     * @return false|int
     * @throws Exception
     */
    private function createMigration($class, $enumList)
    {
        $columns = [];
        $columns[] = $this->formatMethodSetting('id');
        foreach ($enumList['columns'] as $name => $value) {
            $columns[] = $this->formatMethod($enumList['table'], $name, $value);
            if (! empty($value['foreign_table']) && ! empty($value['foreign_column'])) {
                if (! ($foreignColumnType = $this->getTableColumnType($value['foreign_table'], $value['foreign_column']))) {
                    throw new Exception('Not found foreign table and columns: ' . $value['foreign_table'] . ' - ' . $value['foreign_column']);
                }
                if ($value['type'] !== $foreignColumnType) {
                    throw new Exception(
                        'Incorrect foreign column type: ' . $value['foreign_table'] . ' - ' . $value['foreign_column']
                        . ' `' . $value['type']['function'] . '` expected `' . $foreignColumnType['function'] . '`'
                    );
                }
                $this->setForeignKey($enumList['table'], $name, $value['foreign_table'], $value['foreign_column']);
            }
        }

        $settings = $enumList['settings'];
        if (! empty($settings['by_user'])) {
            $columns[] = $this->formatMethodSetting('unsignedBigInteger', 'created_by');
            $columns[] = $this->formatMethodSetting('unsignedBigInteger', 'updated_by');
            $this->setForeignKey($enumList['table'], 'created_by', $this->user->getTable(), $this->user->getKeyName());
            $this->setForeignKey($enumList['table'], 'updated_by', $this->user->getTable(), $this->user->getKeyName());
        }
        if (! empty($settings['index'])) {
            $columns[] = $this->formatMultipleIndex($enumList['table'], $settings['index'], false);
        }
        if (! empty($settings['unique'])) {
            $columns[] = $this->formatMultipleIndex($enumList['table'], $settings['unique'], true);
        }
        $columns[] = $this->formatMethodSetting('timestamps');
        if (! empty($settings['soft_delete'])) {
            $columns[] = $this->formatMethodSetting('softDeletes');
        }

        $result = [
            'class' => $class,
            'table' => $enumList['table'],
            'columns' => implode("\n", $columns),
        ];

        $savePath = $this->savePath($class);
        dump($savePath);

        return TemplateManager::create(
            $this->templatePath(), $result
        )->save($savePath);
    }

    /**
     * @param $class
     * @param $values
     * @return bool|int
     */
    private function createForeignMigration($class, $values)
    {
        $table = current($values)['table_src'];

        $columns = [];
        $dropColumns = [];
        foreach ($values as $value) {
            $name = implode('_', ['fk', $table, $value['column_src'], $value['table_dest'], $value['column_dest']]);
            $methods = [
                self::FIRST_CREATE_CONTENT,
                $this->methodStrings('foreign', [$value['column_src'], $name]),
                $this->methodString('references', $value['column_dest']),
                $this->methodString('on', $value['table_dest']),
                $this->methodString('onUpdate', self::DEFAULT_ON_UPDATE),
                $this->methodString('onDelete', self::DEFAULT_ON_DELETE),
            ];
            $columns[] = implode('->', $methods) . ';';
            $dropColumns[] = implode('->', [
                    self::FIRST_CREATE_CONTENT,
                    $this->methodString('dropForeign', $name),
                ]) . ';';
        }

        $result = [
            'class' => $class,
            'table' => $table,
            'columns' => implode("\n", $columns),
            'dropColumns' => implode("\n", $dropColumns),
        ];

        $savePath = $this->savePath($class);
        dump($savePath);

        return TemplateManager::create(
            $this->templateForeignPath(), $result
        )->save($savePath);
    }

    /**
     * @param $table
     * @param $column
     * @return array|false|mixed
     */
    private function getTableColumnType($table, $column)
    {
        if (! ($config = config('table.' . Str::singular($table) . '.columns'))) {
            return false;
        }

        if ('id' === $column) {
            return TableConfigManager::TYPE_UNSIGNED_BIGINT;
        }

        return isset($config[$column]) ? $config[$column]['type'] : false;
    }

    /**
     * @param $tableSrc
     * @param $columnSrc
     * @param $tableDest
     * @param $columnDest
     */
    private function setForeignKey($tableSrc, $columnSrc, $tableDest, $columnDest)
    {
        $this->foreignKeyConstraint[] = [
            'table_src' => $tableSrc,
            'column_src' => $columnSrc,
            'table_dest' => $tableDest,
            'column_dest' => $columnDest,
        ];
    }

    /**
     * @param $table
     * @param $column
     * @param $value
     * @return string
     */
    private function formatMethod($table, $column, $value)
    {
        $methods = [self::FIRST_CREATE_CONTENT];
        $arguments = [$this->wrapString($column)];

        if (TableConfigManager::TYPE_ENUM === $value['type']) {
            $arguments[] = $value['enum'] . '::values()';
        } elseif (TableConfigManager::TYPE_SET === $value['type']) {
            $arguments[] = $value['enum'] . '::values()';
        } elseif (! empty($value['type']['decimal'])) {
            $arguments[] = $value['digit'];
            $arguments[] = $value['decimal_places'];
        } elseif (! empty($value['type']['digit']) && ! empty($value['digit'])) {
            if ('string' === TableConfigManager::searchTypeGroup($value['type'])) {
                $arguments[] = $value['digit'];
            }
        }
        $methods[] = $value['type']['function'] . '(' . implode(', ', $arguments) . ')';

        if (empty($value['required'])) {
            $methods[] = $this->methodString('nullable');
        }
        if (isset($value['default_value'])) {
            $methods[] = $this->methodString('default', $value['default_value']);
        }
        if (! empty($value['comment'])) {
            $methods[] = $this->methodString('comment', $value['comment']);
        }

        if (! empty($value['unique'])) {
            $methods[] = $this->methodString('unique', $table . '_' . $column);
        } elseif (! empty($value['index'])) {
            $methods[] = $this->methodString('index', $table . '_' . $column . '_idx');
        }

        return implode('->', $methods) . ';';
    }

    /**
     * @param $method
     * @param null $text
     * @return string
     */
    private function formatMethodSetting($method, $text = null)
    {
        $methods = [
            self::FIRST_CREATE_CONTENT,
            $this->methodString($method, $text)
        ];

        return implode('->', $methods) . ';';
    }

    /**
     * @param $table
     * @param array $indexes
     * @param false $unique
     * @return string
     */
    private function formatMultipleIndex($table, array $indexes, $unique = false)
    {
        $key = $table . '_' . implode('_', $indexes);
        if ($unique) {
            $method = 'unique';
        } else {
            $method = 'index';
            $key .= '_idx';
        }

        $values = [];
        foreach ($indexes as $index) {
            $values[] = $this->wrapString($index);
        }

        $methods = [
            self::FIRST_CREATE_CONTENT,
            $method . '([' . implode(', ', $values) . '], ' . $this->wrapString($key) . ')'
        ];

        return implode('->', $methods) . ';';
    }

    /**
     * @param $text
     * @return string
     */
    private function wrapString($text)
    {
        return "'" . $text . "'";
    }

    /**
     * @param $method
     * @param null $text
     * @return string
     */
    private function methodString($method, $text = null)
    {
        $argument = '';
        if (isset($text)) {
            $argument = is_numeric($text) ? $text : $this->wrapString($text);
        }

        return $method . '(' . $argument . ')';
    }

    /**
     * @param $method
     * @param array $text
     * @return string
     */
    private function methodStrings($method, $text = [])
    {
        $arguments = [];
        foreach ($text as $item) {
            $arguments[] = is_numeric($item) ? $item : $this->wrapString($item);
        }

        return $method . '(' . implode(', ', $arguments) . ')';
    }

    /**
     * @param $name
     * @return string
     */
    private function formatClass($name)
    {
        return self::CREATE_CLASS_PREFIX . ucfirst(Str::camel($name)) . self::CLASS_SUFFIX;
    }

    /**
     * @param $name
     * @param $columns
     * @return string
     */
    private function formatForeignKeyClass($name, $columns)
    {
        foreach ($columns as $key => $column) {
            $columns[$key] = ucfirst(Str::camel($column));
        }

        return self::ADD_FOREIGN_CLASS_PREFIX . ucfirst(Str::camel($name)) . implode('', $columns) . self::CLASS_SUFFIX;
    }

    /**
     * @return string
     */
    private function templatePath()
    {
        return storage_path(self::TEMPLATE);
    }

    /**
     * @return string
     */
    private function templateForeignPath()
    {
        return storage_path(self::TEMPLATE_FOREIGN);
    }

    /**
     * @param $class
     * @return string
     */
    private function savePath($class)
    {
        if ($this->fileOverWrite && $file = $this->checkFileByClass($class)) {
            return $file->getPathname();
        }

        $fileName = Carbon::now()->format('Y_m_d_His_') . Str::snake($class) . '.php';

        return database_path(self::DEST_DIRECTORY . $fileName);
    }

    /**
     * @param $class
     * @return false|SplFileInfo
     */
    private function checkFileByClass($class)
    {
        $filePart = '_' . Str::snake($class) . '.php';
        foreach ($this->migrationFiles as $migrationFile) {
            if (strpos($migrationFile->getFilename(), $filePart)) {
                return $migrationFile;
            }
        }
        return false;
    }
}
