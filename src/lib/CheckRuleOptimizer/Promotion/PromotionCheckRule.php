<?php


namespace QyDiscount\lib\CheckRuleOptimizer\Promotion;


use QyDiscount\lib\CalcResult;
use QyDiscount\lib\Common;

class PromotionCheckRule extends \QyDiscount\lib\CheckRuleOptimizer\CheckRule
{
    private $showLog = false;

    function check($params, CalcResult &$calcResult)
    {
        list($promotionKey, $goodsitems, $context, $stageContext, $stageKey) = $params;
        $invalidCombination = false;

        if(!$goodsitems || empty($goodsitems))
            return false;

        if($context->getConfig('showLogPromotionCheckRule'))
            $this->showLog = true;

        $calcResult->increaseRunCnt('plan');
        $context->setPromotionTracing($promotionKey, $this);

        $goodsItemSameSku = $context->getGoodsSameSku($goodsitems);
        $cacheKeySameSku = $promotionKey.'-'.$context->getGoodsSameSkuKey($goodsItemSameSku);

        if(isset($context->notWorkablePromotionPlans[$cacheKeySameSku])){
            if($this->showLog)
                $calcResult->addLog('promotion_skip_2', "plan_",
                    'plan notWorkablePromotionPlans: '.$cacheKeySameSku.", ".$promotionKey.'=>'.json_encode($goodsitems)
                );
            $calcResult->increaseRunCnt('promotion_skip_2');
            $invalidCombination = true;
            return !$invalidCombination;
        }

        $minWorkable = false;
        if(isset($context->minWorkableCache[$promotionKey])){
            foreach ($context->minWorkableCache[$promotionKey] as $minGoodsItems){
                if(empty(array_diff_key($minGoodsItems, array_count_values($goodsitems)))){
                    $minWorkable = true;
                    $calcResult->increaseRunCnt('minWorkableCache');
                    break;
                }
            }
        }
        if(!$minWorkable){
            $context->promotions[$promotionKey]->setGoodsItems($goodsitems);
            if (!$context->promotions[$promotionKey]->hasMeet()) {
                $context->notWorkablePromotionPlans[$cacheKeySameSku] = ['code'=>999, 'message'=>''];
                if ($this->showLog)
                    $calcResult->addLog('promotion_skip_1', "plan_",
                        'plan not workable: ' . $cacheKeySameSku
                    );
                $calcResult->increaseRunCnt('promotion_skip_5');
                $invalidCombination = true;
                return !$invalidCombination;
            }
            else{
                $context->minWorkableCache[$promotionKey][] = array_count_values($goodsitems);
            }
        }
        $calcResult->increaseRunCnt('promotion_check');

        return !$invalidCombination;
    }
}