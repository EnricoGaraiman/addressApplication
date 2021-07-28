<?php

namespace App\Service;

class DataVersion
{
    private $url_pattern;

    public function __construct(string $url_pattern)
    {
        $this->url_pattern = $url_pattern;
    }

    public function getVersion($ok=false)
    {
        if (!file_exists($this->url_pattern) or $ok)
        {
            $date = date('Y-m-d H:i:s');
            $handle = fopen($this->url_pattern, "w");
            fwrite($handle, $date);
            fclose($handle);
        }
        return file_get_contents($this->url_pattern);
    }
}