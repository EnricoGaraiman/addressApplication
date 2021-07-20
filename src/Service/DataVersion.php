<?php

namespace App\Service;

class DataVersion
{
    private $url_pattern;

    public function __construct(string $url_pattern){
        $this->url_pattern = $url_pattern;
    }

    public function getVersion()
    {
        if (!file_exists($this->url_pattern)){
            $date = date('Y-m-d H:i:s');
            $handle = fopen($this->url_pattern, "w");
            fwrite($handle, $date);
            fclose($handle);
        }
        else {
            $date = date('Y-m-d H:i:s');
            $handle = fopen($this->url_pattern, "w");
            fwrite($handle, $date);
            fclose($handle);
        }
        return file_get_contents($this->url_pattern);
    }
}