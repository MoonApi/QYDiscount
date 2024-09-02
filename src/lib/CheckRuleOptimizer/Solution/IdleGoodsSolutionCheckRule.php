<?php


namespace QyDiscount\lib\CheckRuleOptimizer\Solution;


use QyDiscount\lib\CalcResult;

class IdleGoodsSolutionCheckRule extends \QyDiscount\lib\CheckRuleOptimizer\CheckRule
{

    function check($params, CalcResult &$calcResult)
    {
        //            //check goods is idle
//            foreach ($stageContext->goodsItems as $goodsItem) {
//                if(count($context->goodsPromotionExclusiveList[$goodsItem->idx]) == 0){
//                    continue;
//                }
//                $found = false;
//                foreach ($goodsItemsStage as $stage=>$goodsItems){
//                    foreach ($goodsItems as $key => $items) {
//                        if (in_array($goodsItem->idx, $items)) {
//                            $found = true;
//                            break;
//                        }
//                    }
//                    if($found)
//                        break;
//                }
//                //fit promotion, but not applied
//                if(!$found){
//                    $invalidSolution = true;
//                    break;
//                }
//            }
    }
}