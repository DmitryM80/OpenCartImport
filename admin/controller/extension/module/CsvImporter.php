<?php

/**
 * Class CsvImporter
 * works with CSV files
 */
class CsvImporter
{
    private $fh;
    private $header;
    private $separator;
    private $length;

    private $tableHead;

    public function __construct($fileName, $header=false, $separator=',', $length=8000)
    {
        $this->fh = fopen($fileName, 'r');

        $this->header = $header;
        $this->separator = $separator;
        $this->length = $length;

        if ($this->header)
        {
            $this->tableHead = fgetcsv($this->fh, $this->length);
        }
    }

    /**
     * @return array Table header
     */
    public function getHead()
    {
        return $this->tableHead;
    }


    /**
     * @param int Lines count, 0 = all
     *
     * @return array CSV file content
     */
    public function getCsv($max = 0)
    {
        $data = array();

        for ($line = 0; $row = fgetcsv($this->fh, $this->length, $this->separator); $line++)
        {
            if ($this->header)
            {
                foreach ($this->tableHead as $k => $v)
                {
                    $row1[$v] = $row[$k];
                }
                $data[] = $row1;
            }
            else
            {
                $data[] = $row;
            }

            if ($max > 0 && $max == $line) break;
        }

        return $data;
    }

    public function __destruct()
    {
        if ($this->fh)
        {
            fclose($this->fh);
        }
    }
}
