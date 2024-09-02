<?php


namespace QyDiscount\lib\CheckRuleOptimizer\Plan;


use QyDiscount\lib\CalcResult;

class MaxSaveCheckRule extends \QyDiscount\lib\CheckRuleOptimizer\CheckRule
{

    function check($params, CalcResult &$calcResult)
    {
        list($ss, $context, $stageKey) = $params;
        $invalidPlan = false;

        $stagePromotions = $context->getStagePromotions($stageKey);

        $promotionMaxSave = 0;
        foreach ($ss as $promotionKey){
            $context->setPromotionTracing($promotionKey, $this);
            if(bccomp($stagePromotions[$promotionKey]->minSave, $promotionMaxSave, 2) > 0){
                $promotionMaxSave = $stagePromotions[$promotionKey]->minSave;
            }
        }

        $stagePromotionMaxSave = 0;
        foreach ($stagePromotions as $stagePromotion){
            if(bccomp($stagePromotion->minSave, $stagePromotionMaxSave, 2) > 0){
                $stagePromotionMaxSave = $stagePromotion->minSave;
            }
        }

        if(bccomp($promotionMaxSave, $stagePromotionMaxSave, 2) < 0){
            $invalidPlan = true;
        }

        return !$invalidPlan;
    }
}