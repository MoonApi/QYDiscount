<?php


namespace QyDiscount\lib\CheckRuleOptimizer\Plan;


use QyDiscount\lib\CalcResult;

class PlanPreCheckRule extends \QyDiscount\lib\CheckRuleOptimizer\CheckRule
{
    public $showLog = false;

    function check($params, CalcResult &$calcResult)
    {
        list($ss, $context, $stageKey) = $params;
        $stagePromotionsRequired = $context->getStagePromotionRequired($stageKey);

        if($context->getConfig('showLogPlanPreCheckRule'))
            $this->showLog = true;

        if($context->getConfig('showPromotionTracing')){
            foreach ($ss as $promotionKey) {
                $context->setPromotionTracing($promotionKey, $this);
            }
        }

        //pre-exclude, will reduce the plan cnt
        $invalidPlan = false;
        if(!empty(array_diff_key($stagePromotionsRequired, array_count_values($ss)))){
            if($this->showLog)
                $calcResult->addLog('plan_skip_4', "plan_",
                    'plan isRequiredByStrage: '.json_encode(array_diff_key($stagePromotionsRequired, array_count_values($ss))).", ".json_encode($ss)
                );
            $invalidPlan = true;
        }

//        $promotionPlansKeys = array_keys($promotionPlans);
//        foreach ($context->promotions as $promotionKey=>$promotion){
//            if($promotion->bondType != $stageKey || !$promotion->isMaxSaveSatisfacted) continue;
//
//            if($promotion->isRequiredByStrage && !in_array($promotionKey, $promotionPlansKeys)){
//                if($this->showLog)
//                    $calcResult->addLog('plan_skip_4', "plan_",
//                        'plan isRequiredByStrage: '.$promotion->key.", ".json_encode($promotionPlansKeys)
//                    );
//                $promotionPlans = [];
//                break;
//            }
//        }

        return !$invalidPlan;
    }
}