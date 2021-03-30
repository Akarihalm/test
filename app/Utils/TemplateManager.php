<?php

namespace App\Utils;

/**
 * Class TemplateManager
 * @package App\Utils
 */

class TemplateManager
{
    private $destPath;

    private $templatePath;

    private $data = [];

    /**
     * TemplateManager constructor.
     * @param string $templatePath
     */
    public function __construct($templatePath)
    {
        $this->templatePath = $templatePath;
    }

    /**
     * @param $data
     * @return $this
     */
    public function with($data)
    {
        $this->data = $data;
        return $this;
    }

    /**
     * @param $path
     * @return $this
     */
    public function dest($path)
    {
        $this->destPath = $path;
        return $this;
    }

    /**
     * @return false|string
     */
    public function make()
    {
        return $this->replace(
            file_get_contents($this->templatePath)
        );
    }

    /**
     * @param $template
     * @return string|string[]
     */
    public function replace($template)
    {
        foreach ($this->data as $key => $contents) {
            $template = str_replace("#{$key}#", $contents, $template);
        }

        return $template;
    }

    /**
     * @param $contents
     * @return false|int
     */
    public function put($contents)
    {
        return file_put_contents(
            $this->destPath,
            $contents
        );
    }

    /**
     * @param $destPath
     * @return false|int
     */
    public function save($destPath)
    {
        $this->dest($destPath);

        return $this->put(
            $this->make()
        );
    }

    /**
     * @param $templatePath
     * @param array $data
     * @return static
     */
    public static function create($templatePath, $data = [])
    {
        $class = new static($templatePath);

        if ($data) {
            $class->with($data);
        }

        return $class;
    }

    /**
     * @param $template
     * @param array $data
     * @return string|string[]
     */
    public static function replaceTemplate($template, $data = [])
    {
        $class = new static('');

        if ($data) {
            $class->with($data);
        }

        return $class->replace($template);
    }
}
