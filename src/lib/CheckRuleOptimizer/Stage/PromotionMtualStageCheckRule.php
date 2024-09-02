<?php


namespace QyDiscount\lib\CheckRuleOptimizer\Stage;


use QyDiscount\lib\CalcResult;

class PromotionMtualStageCheckRule extends \QyDiscount\lib\CheckRuleOptimizer\CheckRule
{
    private $showLog = false;

    function check($params, CalcResult &$calcResult)
    {
        list($curCombination, $context, $stageContext, $stageKey, $promotionPlansSeq) = $params;
        $invalidCombination = false;

        if($context->getConfig('showLogPromotionMtualStageCheckRule'))
            $this->showLog = true;

        $goodsPromtionCache = [];

        //promotion mutual
        foreach($curCombination as $key=>$goodsItems){
            $context->setPromotionTracing($key, $this);
            foreach ($goodsItems as $idx){
                if(!isset($goodsPromtionCache[$idx]))
                    $goodsPromtionCache[$idx] = [];

                $goodsPromtionCache[$idx][] = $key;

                if(count($goodsPromtionCache[$idx]) == 1)
                    continue;

                foreach ($goodsPromtionCache[$idx] as $item){
                    if(in_array($key, $context->goodsPromotionExclusiveList[$idx][$item])){
                        $calcResult->increaseRunCnt('stage_skip_1');
                        if($this->showLog)
                            $calcResult->addLog('stage_skip_1', "plan_".$promotionPlansSeq."_".$calcResult->planCalculatorTotalCnt,
                                'promotion mutual, goods: '.$idx.", promotion: ".$key.", ".$item,
                            );
                        $invalidCombination = true;
                        break;
                    }
                }
                if($invalidCombination)
                    break;
            }
            if($invalidCombination)
                break;
        }

        return !$invalidCombination;
    }
}