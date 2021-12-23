<?php

class Core
{

    public function __construct()
    {
    }

    public static function getSizeInBytes(string $size): int
    {
        sscanf($size, '%u%c', $number, $suffix);
        if (isset($suffix)) {
            $number = $number * pow(1024, strpos(' KMG', strtoupper($suffix)));
        }
        return $number;
    }

    public static function getBytesAsSize(int|string $size = 0): string
    {
        if ($size == 0) {

            return "0 B";
        }

        $base = log($size) / log(1024);
        $suffix = array("B", "KB", "MB", "GB", "TB");
        $f_base = floor($base);
        return round(pow(1024, $base - floor($base)), 1) . $suffix[$f_base];
    }

    public static function checkAgainstDimension(string|bool $condition, int|string $value): bool
    {
        $result = false;
        $part = 0;

        //if no condition is set, let everything through
        if ($condition === false) {

            $result = true;
            $part = 1;
        }

        $value = intval($value);
        $match = intval(preg_replace('/\D/', '', $condition));

        if (strpos($condition, "<") === 0 && $value < $match) {
            $part = 2;
            $result = true;
        } else if (strpos($condition, ">") === 0 && $value > $match) {
            $part = 3;
            $result = true;
        } else if ($value === $match) {
            $part = 4;
            $result = true;
        }

        return $result;
    }
    
    /**
     * serialize and base64 on the last item of $delRow
     */
    public static function delEncode( array $delRow ): string {
        
        return base64_encode(serialize(array_slice($delRow, -1, 1)));
    }
    
    
    public static function delDecode( string $delEncoded ):bool|array {
    
        $decoded = base64_decode($delEncoded);
        
        //I don't like to block notices with @, but I check on the result anyway.
        $row = @unserialize($decoded) ?? false;
        
        if (!$row || !is_array($row)){
            
            return false;
        }
        
        return $row;
    }

    
    public static function deleteFile(string $path):void
    {

        if (is_file($path)) {

            unlink($path);
        }
    }
}
