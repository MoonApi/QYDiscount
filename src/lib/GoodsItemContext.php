<?php


namespace QyDiscount\lib;


class GoodsItemContext
{
    public $idx;
    public $promotionRecords = [];
    public $actualAmount;
    public $isPromoted = [];
    public $promotionExclusiveList = [];

    public function __construct($idx, $amount){
        $this->idx = $idx;
        $this->actualAmount = $amount;
    }
}