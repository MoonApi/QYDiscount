<?php


namespace QyDiscount\lib;


use QyDiscount\lib\PlanContext;

class StageContext
{
    public $stageSolution;
    public $promotions;
    public $goodsItems;
    public $context;

    public $planContextList = [];

    public function __construct(Context &$context, $stageSolution, &$promotions, $goodsItems){
        $this->stageSolution = $stageSolution;
        $this->promotions = $promotions;
        $this->goodsItems = $goodsItems;
        $this->context = $context;
        $this->context->stageCnt++;

        //file_put_contents("test.log", "new stage: ".().', '.json_encode($this->stageSolution)."\n", FILE_APPEND);
    }

    public function initStage(){
        foreach ($this->promotions as &$promotion){
            $promotion->setGoodsItems([]);
        }
    }
}