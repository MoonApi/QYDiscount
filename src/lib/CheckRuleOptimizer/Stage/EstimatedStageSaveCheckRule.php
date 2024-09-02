<?php


namespace QyDiscount\lib\CheckRuleOptimizer\Stage;


use QyDiscount\lib\CalcResult;
use QyDiscount\lib\PlanContext;

class EstimatedStageSaveCheckRule extends \QyDiscount\lib\CheckRuleOptimizer\CheckRule
{
    private $showLog = false;

    function check($params, CalcResult &$calcResult)
    {
        list($curCombination, $context, $stageContext, $stageKey, $promotionPlansSeq) = $params;
        $invalidCombination = false;

        if($context->getConfig('showLogEstimatedStageSaveCheckRule'))
            $this->showLog = true;

        $estimatePlanSave = PlanContext::estimatePlanSave($curCombination, $context);

        if($context->getConfig('showPromotionTracing')){
            foreach ($curCombination as $promotionKey=>$item){
                $context->setPromotionTracing($promotionKey, $this);
            }
        }

        if(bccomp($context->stageMinSave[$stageKey], 0, 2) > 0 && bccomp($estimatePlanSave, $context->stageMinSave[$stageKey], 2)<0){
            $invalidCombination = true;
            $calcResult->increaseRunCnt('stage_skip_8');
            if($this->showLog)
                $calcResult->addLog('stage_skip_8', "plan_".$promotionPlansSeq."_".$calcResult->planCalculatorTotalCnt,
                    'estimatePlanSave '.$estimatePlanSave.' < stageMinSave '.$context->stageMinSave[$stageKey].", ".json_encode($curCombination)
                );
        }

        return !$invalidCombination;
    }
}