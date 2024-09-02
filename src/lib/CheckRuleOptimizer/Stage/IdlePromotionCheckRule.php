<?php


namespace QyDiscount\lib\CheckRuleOptimizer\Stage;


use QyDiscount\lib\CalcResult;
use function QyDiscount\lib\Permutation\promotion_combination;

class IdlePromotionCheckRule extends \QyDiscount\lib\CheckRuleOptimizer\CheckRule
{
    function check($params, CalcResult &$calcResult)
    {
        list($curCombination, $context, $stageContext, $stageKey, $promotionPlansSeq) = $params;
        $invalidCombination = false;

        $curCombinationKeys = array_keys($curCombination);

        $idleGoodsItems = [];
        foreach ($stageContext->goodsItems as $goodsItem) {
            $found = false;
            foreach ($curCombination as $key => $items) {
                if (in_array($goodsItem->idx, $items)) {
                    $found = true;
                    break;
                }
            }
            if(!$found)
                $idleGoodsItems[] = $goodsItem->idx;
        }

        $stagePromotions = $context->getStagePromotions($stageKey);

        foreach ($stagePromotions as $promotionKey=>$promotion){
            $context->setPromotionTracing($promotionKey, $this);
            if(!in_array($promotionKey, $curCombinationKeys)){
                //$cacheKey = join("-", $stageContext->promotions[$promotionKey]->goodsItemsAll);
                //$plans = $context->goodsItemCombinationCache[$cacheKey];
                $goodsItemsAll = array_values(array_intersect($idleGoodsItems, $stageContext->promotions[$promotionKey]->goodsItemsAll));
                if(empty($goodsItemsAll))
                    continue;

                $generator = promotion_combination($goodsItemsAll);

                foreach ($generator as $plan){
                    $stageContext->promotions[$promotionKey]->setGoodsItems($plan);
                    if($stageContext->promotions[$promotionKey]->hasMeet()){
                        //goodsitem belongs to other exclusive promotion
                        $isExclusive = false;
                        foreach ($plan as $p){
                            if(count($context->goodsPromotionExclusiveList[$p]) > 0){
                                foreach ($context->goodsPromotionExclusiveList[$p] as $key=>$promotionList){
                                    if($stageContext->promotions[$key]->bondType != $stageKey){
                                        if(in_array($promotionKey, $promotionList)){
                                            $isExclusive = true;
                                            break;
                                        }
                                    }
                                }
                            }
                            if($isExclusive)
                                break;
                        }
                        if(!$isExclusive){
                            $invalidCombination = true;
                            break;
                        }
                    }
                }
            }

            if($invalidCombination)
                break;
        }

        return !$invalidCombination;
    }
}