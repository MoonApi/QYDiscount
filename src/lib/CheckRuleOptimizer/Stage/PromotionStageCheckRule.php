<?php


namespace QyDiscount\lib\CheckRuleOptimizer\Stage;


use QyDiscount\lib\CalcResult;
use QyDiscount\lib\Common;

class PromotionStageCheckRule extends \QyDiscount\lib\CheckRuleOptimizer\CheckRule
{
    private $showLog = false;

    function check($params, CalcResult &$calcResult)
    {
        list($curCombination, $context, $stageContext, $stageKey, $promotionPlansSeq) = $params;
        $calcResult->increaseRunCnt('plan');
        $invalidCombination = false;

        if($context->getConfig('showLogPromotionStageCheckRule'))
            $this->showLog = true;

//            $planFullFilledCache = [];
//            $workablePlanSameSkuPromotionPlans = [];

        foreach ($curCombination as $promotionKey=>$goodsitems) {
            $context->setPromotionTracing($promotionKey, $this);
            $cacheKeySameSku = $promotionKey.'-'.$context->getGoodsSameSkuKeyRaw($goodsitems);

            if($this->showLog)
                $calcResult->addLog('plan', "plan_",
                    'plan : '.$promotionKey.", ".json_encode($goodsitems)
                );

            $minWorkable = false;
            if(isset($context->minWorkableCache[$promotionKey])){
                foreach ($context->minWorkableCache[$promotionKey] as $minGoodsItems){
                    if(empty(array_diff_key($minGoodsItems, array_count_values($goodsitems)))){
                        $minWorkable = true;
                        break;
                    }
                }
            }
            if(!$minWorkable){
                $context->promotions[$promotionKey]->setGoodsItems($goodsitems);
                if (!$context->promotions[$promotionKey]->hasMeet()) {
                    if ($this->showLog)
                        $calcResult->addLog('plan_skip_5', "plan_",
                            'plan not in workableCache: ' . $cacheKeySameSku
                        );
                    $calcResult->increaseRunCnt('plan_skip_5');
                    $invalidCombination = true;
                    return !$invalidCombination;
                }
                else{
                    $context->minWorkableCache[$promotionKey][] = array_count_values($goodsitems);
                }
            }

            if(isset($context->notWorkablePromotionPlans[$cacheKeySameSku])){
                if($this->showLog)
                    $calcResult->addLog('plan_skip_1', "plan_",
                        'plan notWorkablePromotionPlans: '.$cacheKeySameSku.", ".$promotionKey.'=>'.json_encode($goodsitems)
                    );
                $calcResult->increaseRunCnt('plan_skip_1');
                $invalidCombination = true;
                return !$invalidCombination;
            }

            $ignorePlan = false;
            $ignorePlanFullFilledItem = null;

            //same full filled plan exists, ignore current plan
            if(isset($context->sameFullFilledPlanL2SMap[$promotionKey][$cacheKeySameSku])){
                $ignorePlan = true;
                $ignorePlanFullFilledItem = $context->sameFullFilledPlanL2SMap[$promotionKey][$cacheKeySameSku];
            }


            if($ignorePlan){
                $context->notWorkablePromotionPlans[$cacheKeySameSku] = ['code'=>999, 'message'=>''];
                if($this->showLog)
                    $calcResult->addLog('plan_skip_2', "plan_",
                        'plan ignorePlan: '.$cacheKeySameSku.", ".$promotionKey.'=>'.json_encode($goodsitems)." === ".json_encode($ignorePlanFullFilledItem)
                    );
                $calcResult->increaseRunCnt('plan_skip_2');
                $invalidCombination = true;
                return !$invalidCombination;
            }
        }
        $calcResult->increaseRunCnt('plan_out');

        return !$invalidCombination;
    }
}