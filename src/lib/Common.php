<?php

namespace QyDiscount\lib;

class Common{
    public static function array_diff_fast($a, $b)
    {
        $map = array();
        foreach($a as $val) $map[$val] = 1;
        foreach($b as $val) unset($map[$val]);
        return array_keys($map);
    }
}