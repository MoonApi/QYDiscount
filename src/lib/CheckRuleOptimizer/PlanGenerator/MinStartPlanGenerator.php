<?php


namespace QyDiscount\lib\CheckRuleOptimizer\PlanGenerator;


use QyDiscount\lib\CalcResult;

class MinStartPlanGenerator extends \QyDiscount\lib\CheckRuleOptimizer\CheckRule
{
    public $showLog = false;

    function check($params, CalcResult &$calcResult)
    {
        list($generators, $generatorsSource, $cur, $context, $stageContext, $stageKey) = $params;

        if($context->getConfig('showLogMinStartPlanGenerator'))
            $this->showLog = true;

        $keys = array_keys($generatorsSource);

        $cacheKey = join('-', $keys).'-'.$keys[$cur].'-'.join('-', $generatorsSource[$keys[$cur]]);

        if(isset($context->nextGeneratorMinStartCache[$cacheKey])){
            $estimateMinStart = $context->nextGeneratorMinStartCache[$cacheKey];
            return $estimateMinStart;
        }

        $plan = [];
        $planGoodsItemCnt = [];
        $maxSave = 0;
        $minStart = false;

        $curPromotion = $context->promotions[$stageContext->stageSolution[$cur]];
        $context->setPromotionTracing($stageContext->stageSolution[$cur], $this);

        for($i=0;$i<$cur;$i++){
            $plan[$stageContext->stageSolution[$i]] = $generators[$i]->current();
            foreach ($plan[$stageContext->stageSolution[$i]] as $idx){
                $planGoodsItemCnt[$idx] = 1;
            }

            $maxSave += $context->promotions[$stageContext->stageSolution[$i]]->goodsCntMaxSave[count($plan[$stageContext->stageSolution[$i]])]['amount'];
        }

        if(bccomp($maxSave, $context->stageMinSave[$stageKey], 2) >= 0){
            $minStart = 1;
        }
        else{
            $leftMaxSave = 0;
            for($j=$cur+1;$j<count($stageContext->stageSolution);$j++){
                $leftMaxSave += end($context->promotions[$stageContext->stageSolution[$j]]->goodsCntMaxSave)['amount'];
            }
            for($i=1; $i<=count($curPromotion->goodsItemsAll);$i++){
                //($leftMaxSave - 1), in case $leftMaxSave == stageMinSave
                if(bccomp($maxSave + ($leftMaxSave>0?$leftMaxSave - 1:0) + $curPromotion->goodsCntMaxSave[$i]['amount'], $context->stageMinSave[$stageKey], 2) >= 0){
                    $minStart = $i;
                    break;
                }
            }
        }

        if($minStart!==false && count($planGoodsItemCnt)>0 && count(array_diff_key($curPromotion->goodsItemsAllCnt, $planGoodsItemCnt)) < $minStart){
            $minStart = false;
        }

        if($this->showLog)
            if(!$minStart)
                $calcResult->addLog('plan-generator_skip_1', "plan_",
                    'plan EstimateMinStart: '.$cur.", stageMinSave ".$context->stageMinSave[$stageKey].', '.$stageKey.', $maxSave '.$maxSave. ', $leftMaxSave '.$leftMaxSave.', $minStart: '.$minStart.', '.json_encode($plan).' === '.json_encode($stageContext->stageSolution).' === '.json_encode($curPromotion->goodsCntMaxSave)
                );
            else
                $calcResult->addLog('plan-generator_minstart', "plan_generator",
                    'plan EstimateMinStart: '.$cur.", stageMinSave ".$context->stageMinSave[$stageKey].', '.$stageKey.', $maxSave '.$maxSave. ', $leftMaxSave '.$leftMaxSave.', $minStart: '.$minStart.', '.json_encode($plan).' === '.json_encode($stageContext->stageSolution).' === '.json_encode($curPromotion->goodsCntMaxSave)
                );

        $context->nextGeneratorMinStartCache[$cacheKey] = $minStart;

        return $minStart;
    }
}