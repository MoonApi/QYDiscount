<?php


namespace QyDiscount\lib\CheckRuleOptimizer\PlanGenerator;


use QyDiscount\lib\CalcResult;
use function QyDiscount\lib\Permutation\promotion_combination;

class EstimatePlanGenerator extends \QyDiscount\lib\CheckRuleOptimizer\CheckRule
{
    public $showLog = false;

    function check($params, CalcResult &$calcResult)
    {
        list($generators, $context, $stageContext, $stageKey) = $params;
        $estimateInvalid = false;

        if($context->getConfig('showLogEstimatePlanGenerator'))
            $this->showLog = true;

        $plan = [];
        $maxSave = 0;
        $planGoodsitemCnt = 0;
        $info = 'ok';

        if($context->getConfig('showPromotionTracing')){
            foreach ($stageContext->stageSolution as $promotionKey){
                $context->setPromotionTracing($promotionKey, $this);
            }
        }

        for($i=0;$i<count($generators);$i++){
            $plan[$stageContext->stageSolution[$i]] = $generators[$i]->current();

            $maxSave += $context->promotions[$stageContext->stageSolution[$i]]->goodsCntMaxSave[count($plan[$stageContext->stageSolution[$i]])]['amount'];

            $planGoodsitemCnt += count($plan[$stageContext->stageSolution[$i]]);
        }

        if(!$estimateInvalid && bccomp($maxSave, $context->stageMinSave[$stageKey], 2) < 0){
            $estimateInvalid = true;
            $info = $maxSave . ' < '. $context->stageMinSave[$stageKey];
        }

        if(!$estimateInvalid){
            $cacheKey = join('-', $stageContext->stageSolution);
            if(isset($context->planGoodsItemTotalCnt[$cacheKey]))
                $totalCnt = $context->planGoodsItemTotalCnt[$cacheKey];
            else{
                $goodsPromtionCache = [];
                $totalCnt = 0;

                //promotion mutual
                foreach($stageContext->stageSolution as $key){
                    $goodsItems = $context->promotions[$key]->goodsItemsAll;
                    $totalCnt += count($goodsItems);

                    foreach ($goodsItems as $idx){
                        if(!isset($goodsPromtionCache[$idx]))
                            $goodsPromtionCache[$idx] = [];

                        $goodsPromtionCache[$idx][] = $key;
                    }
                }

                foreach ($goodsPromtionCache as $idx=>$list){
                    for ($i=0;$i<count($list);$i++){
                        for($j=$i;$j<count($list);$j++){
                            if(in_array($list[$i], $context->goodsPromotionExclusiveList[$idx][$list[$j]])){
                                $totalCnt--;
                            }
                        }
                    }
                }
                $context->planGoodsItemTotalCnt[$cacheKey] = $totalCnt;
            }

            if($planGoodsitemCnt > $totalCnt){
                $estimateInvalid = true;

                $info = '$planGoodsitemCnt '.$planGoodsitemCnt.' >= '.$totalCnt;
            }
        }

        if($this->showLog)
            $calcResult->addLog('EstimatePlanGenerator', "EstimatePlanGenerator_",
                'valid : '.(!$estimateInvalid?1:0).", ".$info.', '.json_encode($plan)
            );


        return !$estimateInvalid;
    }
}