<?php

namespace App\Utils;

use Exception;
use Illuminate\Support\Str;
use SplFileObject;

/**
 * Class CsvManager
 * @package App\Utils
 *
 * @property SplFileObject csv
 */

class CsvManager
{
    /**
     * 対象エンコード
     */
    const ENCODING_UTF_8 = 'UTF-8';
    const ENCODING_SJIS = 'SJIS';
    const ENCODING_EUC_JP = 'EUC-JP';

    /**
     * 事前にエンコード変換するファイルの上限（MByte）
     */
    const BEFORE_ENCODING_LIMIT_M_BYTE = 256;

    protected $path;
    protected $header = [];
    protected $csv = null;
    protected $encoding = self::ENCODING_UTF_8;

    protected $tmpPath = null;

    private $afterEncoding = false;

    private $readFirst = false;

    /**
     * CsvManager constructor.
     * @param $path
     */
    public function __construct($path)
    {
        $this->path = $path;
    }

    /**
     * CsvManager destructor.
     */
    public function __destruct()
    {
        if ($this->tmpPath) {
            unlink($this->tmpPath);
        }
    }

    /**
     * @param $encoding
     */
    public function setEncoding($encoding)
    {
        $this->encoding = $encoding;
    }

    /**
     * @param array $header
     */
    public function setHeader(array $header)
    {
        $this->header = $header;
    }

    /**
     * @return SplFileObject|null
     * @throws Exception
     */
    public function getCsv()
    {
        if ($this->csv) {
            return $this->csv;
        }

        if (! ($path = $this->getPath())) {
            throw new Exception('Not Found Encoding ' . $this->path);
        }

        $this->csv = new SplFileObject($path);
        $this->csv->setFlags(
            SplFileObject::READ_CSV
            | SplFileObject::READ_AHEAD
            | SplFileObject::SKIP_EMPTY
            | SplFileObject::DROP_NEW_LINE
        );
        $this->csv->setCsvControl(",", "\"", "");

        return $this->csv;
    }

    /**
     * @return false|mixed
     */
    private function getPath()
    {
        if (self::ENCODING_UTF_8 === $this->encoding) {
            return $this->path;
        }

        if (self::ENCODING_SJIS === $this->encoding
            || self::ENCODING_EUC_JP === $this->encoding) {
            if ($this->checkPathSize()) {
                return $this->getEncodingChangedPath($this->path);
            }
            $this->afterEncoding = true;
            return $this->path;
        }

        return false;
    }

    /**
     * @return bool
     */
    private function checkPathSize()
    {
        if (! file_exists($this->path)) {
            return false;
        }

        $limitSize = static::BEFORE_ENCODING_LIMIT_M_BYTE * pow(2, 20);

        return filesize($this->path) <= $limitSize;
    }

    /**
     * @param $path
     * @return mixed
     */
    private function getEncodingChangedPath($path)
    {
        $data = file_get_contents($path);
        $data = mb_convert_encoding($data, self::ENCODING_UTF_8, $this->encoding);

        $this->tmpPath = storage_path(Str::random() . '.csv');
        if (file_put_contents($this->tmpPath, $data)) {
            return $this->tmpPath;
        }

        return null;
    }

    /**
     * ヘッダー項目が合致しているかチェック
     *
     * @param $array
     * @return bool
     * @throws Exception
     */
    private function checkHeaders($array)
    {
        if ($this->header !== $array) {
            throw new Exception('Incorrect Header');
        }

        return true;
    }

    /**
     * @param $callback
     * @param bool $keyHeader
     * @return bool
     */
    public function each($callback, $keyHeader = false)
    {
        while (! $this->csv->eof()) {
            $items = $this->csv->current();
            if ($keyHeader) {
                $callback(array_combine($this->header, $items));
            } else {
                $callback($items);
            }
            $this->csv->next();
        }

        return true;
    }

    /**
     * @param bool $keyHeader
     * @return array|false|string|null
     */
    public function getNext($keyHeader = false)
    {
        if ($this->csv->eof()) {
            return null;
        }

        $data = $this->csv->current();

        if (! $data) {
            return $this->getNext($keyHeader);
        }

        $this->csv->next();

        if ($this->afterEncoding) {
            mb_convert_variables(self::ENCODING_UTF_8, $this->encoding, $data);
        }

        return $keyHeader ? array_combine($this->header, $data) : $data;
    }

    public function rewind()
    {
        $this->readFirst = false;
        $this->csv->rewind();
    }

    /**
     * @param $path
     * @param array $headers
     * @param string $encoding
     * @param boolean $checkHeader
     * @return static
     * @throws Exception
     */
    public static function make($path, $headers = [], $encoding = '', $checkHeader = true)
    {
        $class = new static($path);

        if ($encoding) {
            $class->setEncoding($encoding);
        }

        $csv = $class->getCsv();

        if ($headers) {
            $class->setHeader($headers);
            if ($checkHeader) {
                $class->checkHeaders($csv->current());
                $csv->next();
            }
        }

        return $class;
    }
}
