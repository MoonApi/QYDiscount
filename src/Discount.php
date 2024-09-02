<?php
namespace QyDiscount;

use QyDiscount\lib\Calculator;
use QyDiscount\lib\Context;


class Discount
{
    public function calc($goodsItems, $promotions, $runMode = 'debug', $runName = ''){
        date_default_timezone_set('Asia/Shanghai');

        $context = new Context($goodsItems, $promotions, $runMode, $runName);

        $calculator = new Calculator();

        $context->originalPrice =  $calculator->calcOriginalAmount($context);

        $calcResult = $calculator->calc($context);

        return $calcResult;
    }
}