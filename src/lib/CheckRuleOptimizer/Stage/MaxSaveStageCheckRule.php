<?php


namespace QyDiscount\lib\CheckRuleOptimizer\Stage;


use QyDiscount\lib\CalcResult;
use QyDiscount\lib\PlanContext;

class MaxSaveStageCheckRule extends \QyDiscount\lib\CheckRuleOptimizer\CheckRule
{

    function check($params, CalcResult &$calcResult)
    {
        list($curCombination, $context, $stageContext, $stageKey, $promotionPlansSeq) = $params;
        $invalidCombination = false;

        $maxSave = 0;
        foreach ($curCombination as $promotionKey=>$goodsitems){
            $context->setPromotionTracing($promotionKey, $this);
            $maxSave += $context->promotions[$promotionKey]->goodsCntMaxSave[count($goodsitems)]['amount'];
        }

        if(bccomp($context->stageMinSave[$stageKey], 0, 2) > 0 && bccomp($maxSave, $context->stageMinSave[$stageKey], 2)<0){
            $invalidCombination = true;
            $calcResult->increaseRunCnt('stage_skip_9');
        }

        return !$invalidCombination;
    }
}