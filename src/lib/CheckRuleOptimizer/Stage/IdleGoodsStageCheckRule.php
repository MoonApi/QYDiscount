<?php


namespace QyDiscount\lib\CheckRuleOptimizer\Stage;


use QyDiscount\lib\CalcResult;

//强制将空闲商品放入某优惠券，
//1. 但可能存在优惠券未达到使用门槛的情况或已达到最大优惠条件，忽略
//2. 达到使用门槛的且未达到最大优惠，需要放入空闲商品, PlanCheckRule已保证workable
class IdleGoodsStageCheckRule extends \QyDiscount\lib\CheckRuleOptimizer\CheckRule
{
    public $showLog = false;
    function check($params, CalcResult &$calcResult)
    {
        list($curCombination, $context, $stageContext, $stageKey, $promotionPlansSeq) = $params;

        if($context->getConfig('showLogIdleGoodsStageCheckRule'))
            $this->showLog = true;

        $invalidCombination = false;
        //check goods is dle
        $plan = $curCombination;

        if($context->getConfig('showPromotionTracing')){
            foreach ($curCombination as $promotionKey=>$item){
                $context->setPromotionTracing($promotionKey, $this);
            }
        }

        foreach ($stageContext->goodsItems as $goodsItem) {
            $found = false;
            foreach ($plan as $key=>$items){
                if(in_array($goodsItem->idx,$items)){
                    $found = true;
                    break;
                }
            }
            if(!$found && isset($context->stageGoodsItems[$stageKey][$goodsItem->idx])){
                foreach ($plan as $key=>$items){
                    $cacheKey = $key.'-'.$context->getGoodsSameSkuKeyRaw($items);
                    if(!isset($context->promotions[$key]->promotionFullFilledCache[$cacheKey])){
                        $calcResult->increaseRunCnt('stage_skip_7');
                        if($this->showLog)
                            $calcResult->addLog('stage_skip_7', "plan_".$promotionPlansSeq."_".$calcResult->planCalculatorTotalCnt,
                                'promotion '.$key.' can take goods '.$goodsItem->idx.", ".json_encode($plan)
                            );
                        $invalidCombination = true;
                        break;
                    }
                }
                if($invalidCombination)
                    break;
            }
        }
        return !$invalidCombination;
    }
}