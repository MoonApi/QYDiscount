<?php


namespace QyDiscount\lib;


use function PHPUnit\Framework\lessThanOrEqual;

class Permutation
{
    public function getPermutations($dataArr){
        $permutations = array();
        $combinations = array();

        for ($i = 1; $i <= count($dataArr); $i++) {
            $this->doCombinations($dataArr, $i, 0, array(), $combinations);
        }

        foreach ($combinations as $value) {
            $items = $this->arrayCombination($value);
            $permutations = array_merge($permutations, $items);
        }

        return $permutations;
    }
    
    public function getCombinations($dataArr){
        $combinations = array();

        for ($i = 1; $i <= count($dataArr); $i++) {
            $this->doCombinations($dataArr, $i, 0, array(), $combinations);
        }

        return $combinations;
    }

    private function doCombinations($input, $length, $start = 0, $current = array(), &$result = array())
    {
        if (count($current) === $length) {
            $result[] = $current;
            return;
        }

        for ($i = $start; $i < count($input); $i++) {
            $current[] = $input[$i];
            $this->doCombinations($input, $length, $i + 1, $current, $result);
            array_pop($current);
        }
    }

    private function arrayCombination($arr)
    {
        $len = count($arr);
        if ($len == 1) {
            return [$arr];
        }
        $result = array();
        for ($i = 0; $i < $len; $i++) {
            $tmp_arr = $arr;
            unset($tmp_arr[$i]);
            $tmp_arr = array_values($tmp_arr);
            $tmp_result = $this->arrayCombination($tmp_arr);
            foreach ($tmp_result as $val) {
                $val[] = $arr[$i];
                $result[] = $val;
            }
        }
        return $result;
    }

    public function combinations($array) {
        if (count($array) == 1) {
            return reset($array);
        }
        $result = array();
        foreach (reset($array) as $value) {
            foreach ($this->combinations(array_slice($array, 1)) as $comb) {
                $result[] = array_merge($value, $comb);
            }
        }
        return $result;
    }

    protected function isD2ArrayEmpty($arr){
        $notEmpty = array_filter($arr, function($sub){
            return !empty($sub);
        });

        return !$notEmpty;
    }

    public function combinationWithKey($array, $emptyValue=false, $space=0, $checkCallback=null, $checkCallbackParams=null) {
        if($emptyValue!==false && count($array)!=count($array,1)){
            foreach ($array as $key=>$value){
                $empty = array_filter($value, function($sub){
                    return empty($sub);
                });

                if(!$empty){
                    $array[$key][] = $emptyValue;
                }
            }
        }

        if (count($array) == 1) {
            if($space == 0){
                return  array_map(function ($sub){
                    return [$sub];
                }, reset($array));
            }

            return reset($array);
        }
        $result = array();
        foreach (reset($array) as $key=>$value) {
            $subArr = $this->combinationWithKey(array_slice($array, 1), $emptyValue, $space+4);
            foreach ($subArr as $key2=>$value2) {

                $curCombination = [];

                if($emptyValue===false){
                    $curCombination = array_merge([$value], !is_array($value2)||(count($value2)==count($value2,1)||count(array_filter(array_keys($value2),'is_string')) > 0)?[$value2]:$value2);//
                }
                else{
                    if(empty($value2)){
                        $tmp = [$value];
                        $tmp[] = $emptyValue;
                        $curCombination = $tmp;
                    }
                    else{
                        $curCombination = array_merge([$value], count($value2)==count($value2,1)&&!$this->isD2ArrayEmpty($value2)?[$value2]:$value2);
                    }
                }

                if($space == 0 && $checkCallback){
                    if(!$checkCallback($checkCallbackParams, $curCombination)){
                        unset($curCombination);
                        continue;
                    }
                }

                $result[] = $curCombination;
            }
        }
        return $result;
    }

    public function getCartesian($data, $checkCallback=null, $emptyValue=false) {
        if($emptyValue!==false){
            foreach ($data as $key=>$value){
                $empty = array_filter($value, function($sub){
                    return empty($sub);
                });

                if(!$empty){
                    $data[$key][] = $emptyValue;
                }
            }
        }

        if(count($data) > 1){
            $result = array_shift($data);

            while ($arr2 = array_shift($data)) {
                $arr1 = $result;
                $result = [];
                foreach ($arr1 as $k1=>$v1) {
                    foreach ($arr2 as $k2=>$v2) {
                        $result[$k1.'#'.$k2] = 1;
                    }
                }
            }

            if($checkCallback && is_callable($checkCallback)){
                foreach ($result as $key=>$value){
                    if(!$checkCallback($key)){
                        unset($result[$key]);
                        continue;
                    }
                }
            }
        }
        else{
            $arr1 = array_shift($data);
            $result = [];
            foreach ($arr1 as $k1=>$v1) {
                $result[$k1] = $k1;
            }

            if($checkCallback && is_callable($checkCallback)){
                foreach ($result as $key=>$value){
                    if(!$checkCallback($key)){
                        unset($result[$key]);
                        continue;
                    }
                }
            }
        }

        return $result;
    }
}