<?php

namespace App\Console\Commands\Service;

use App\Utils\TemplateManager;
use Exception;
use Illuminate\Config\Repository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\Auth\User;

/**
 * Class Base
 * @package App\Console\Commands\Service
 */

class Base
{
    protected $user;
    protected $fileOverWrite;

    /**
     * Base constructor.
     * @param User $user
     * @param bool $orderWrite
     */
    public function __construct(User $user, $orderWrite = true)
    {
        $this->user = $user;
        $this->fileOverWrite = $orderWrite;
    }

    /**
     * @param $table
     * @return Repository|Application|mixed
     * @throws Exception
     */
    protected function getTableEnum($table)
    {
        if ($table) {
            $enumList = config('table.' . $table);
            if (empty($enumList)) {
                throw new Exception('Not found `' . $table . '`');
            }
            $tableInfo[$table] = $enumList;
        } else {
            $tableInfo = config('table');
        }

        return $tableInfo;
    }

    /**
     * @param $template
     * @param $data
     * @return string|string[]
     */
    protected function replace($template, $data)
    {
        return TemplateManager::replaceTemplate($template, $data);
    }

    /**
     * @param $templatePath
     * @param $data
     * @return false|string|string[]
     */
    protected function replaceByFile($templatePath, $data)
    {
        return TemplateManager::create($templatePath, $data)->make();
    }

    /**
     * @param $templatePath
     * @param $data
     * @param $savePath
     * @return false|int
     */
    protected function saveByFile($templatePath, $data, $savePath)
    {
        return TemplateManager::create($templatePath, $data)->save($savePath);
    }

    /**
     * @param $storageTemplatePath
     * @param $data
     * @return false|string|string[]
     */
    protected function replaceByStorageFile($storageTemplatePath, $data)
    {
        return $this->replaceByFile(storage_path($storageTemplatePath), $data);
    }

    /**
     * @param $storageTemplatePath
     * @param $data
     * @param $savePath
     * @return false|string|string[]
     */
    protected function saveByStorageFile($storageTemplatePath, $data, $savePath)
    {
        return $this->saveByFile(storage_path($storageTemplatePath), $data, $savePath);
    }

    /**
     * @param $value
     * @param int $indentNumber
     * @return string
     */
    protected function formatValue($value, $indentNumber = 2)
    {
        return $this->indent($indentNumber) . "'{$value}',";
    }

    /**
     * @param $key
     * @param $value
     * @param int $indentNumber
     * @return string
     */
    protected function formatKeyValue($key, $value, $indentNumber = 2)
    {
        return $this->indent($indentNumber) . "'{$key}' => '{$value}',";
    }

    /**
     * @param int $num
     * @return string
     */
    protected function indent($num = 1)
    {
        return str_repeat('    ', $num);
    }
}
