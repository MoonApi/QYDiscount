<?php


namespace QyDiscount\lib\CheckRuleOptimizer;


use QyDiscount\lib\CalcResult;

Abstract class CheckRule
{
    protected $name = null;

    abstract function check($params, CalcResult &$calcResult);

    public function getName(){
        if($this->name) return $this->name;
        $nameInfo = explode('\\', get_class($this));

        $this->name = join("\\", array_slice($nameInfo, -2, 2));

        return $this->name;
    }
}