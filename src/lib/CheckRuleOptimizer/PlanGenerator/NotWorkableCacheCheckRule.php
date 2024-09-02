<?php


namespace QyDiscount\lib\CheckRuleOptimizer\PlanGenerator;


use QyDiscount\lib\CalcResult;

class NotWorkableCacheCheckRule extends \QyDiscount\lib\CheckRuleOptimizer\CheckRule
{
    private $showLog = false;

    function check($params, CalcResult &$calcResult)
    {
        list($promotionKey, $goodsitems, $context, $stageContext, $stageKey) = $params;
        $invalidCombination = false;

        if($context->getConfig('showLogNotWorkableCacheCheckRule'))
            $this->showLog = true;

        if($this->showLog)
            $context->addLog("NotWorkableCacheCheckRule", $promotionKey.', '.json_encode($goodsitems));

        if (!$goodsitems || empty($goodsitems))
            return false;

        $calcResult->increaseRunCnt('NotWorkableCacheCheckRule');

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

        if($minWorkable)
            return true;

        $goodsItemSameSku = $context->getGoodsSameSku($goodsitems);
        $cacheKeySameSku = $promotionKey . '-' . $context->getGoodsSameSkuKey($goodsItemSameSku);

        if (isset($context->notWorkablePromotionPlans[$cacheKeySameSku])) {
            $calcResult->increaseRunCnt('promotion_skip_3');
            $invalidCombination = true;
            return !$invalidCombination;
        }

        return !$invalidCombination;
    }
}