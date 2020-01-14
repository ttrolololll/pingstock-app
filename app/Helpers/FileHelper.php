<?php

namespace App\Helpers;

class FileHelper {
    public static function fileLineIterator($file)
    {
        while (($line = fgets($file)) !== false) {
            yield $line;
        }
    }

    public static function csvFileLineIterator($file)
    {
        while (($line = fgetcsv($file)) !== false) {
            yield $line;
        }
    }
}
