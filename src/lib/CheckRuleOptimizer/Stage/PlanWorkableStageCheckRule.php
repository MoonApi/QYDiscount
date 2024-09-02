<?php


namespace QyDiscount\lib\CheckRuleOptimizer\Stage;


use QyDiscount\lib\CalcResult;
use QyDiscount\lib\PlanContext;

class PlanWorkableStageCheckRule extends \QyDiscount\lib\CheckRuleOptimizer\CheckRule
{

    function check($params, CalcResult &$calcResult)
    {
        list($curCombination, $context, $stageContext, $stageKey, $promotionPlansSeq) = $params;
        $invalidCombination = false;

        $planContext = new PlanContext($curCombination, $stageContext, $stageContext->goodsItems);

        $stageContext->initStage();

        $workable = true;
        foreach($planContext->planDefine as $key=>$p){
            $context->setPromotionTracing($key, $this);
            $cacheKey = $key.'-'.join("-", $p);

            if(isset($context->notWorkableCache[$cacheKey])){
                $calcResult->increaseRunCnt('stage_skip_3');
                $calcResult->addLog('stage_skip_3', "plan_".$promotionPlansSeq."_".$calcResult->planCalculatorTotalCnt,
                    'plan: ignored'
                );
                $workable = false;
                break;
            }

            $stageContext->promotions[$key]->setGoodsItems($p);

            if(!$stageContext->promotions[$key]->apply($planContext)){
                $calcResult->increaseRunCnt('stage_skip_4');
                $calcResult->addLog('stage_skip_4', "plan_".$promotionPlansSeq."_".$calcResult->planCalculatorTotalCnt,
                    'plan: miss promotion code as '.$stageContext->promotions[$key]->missCode.', '.$stageContext->promotions[$key]->message
                );
                $workable = false;

                if(in_array($stageContext->promotions[$key]->missCode, [1,2,3,4,5,6])){
                    $context->notWorkableCache[$cacheKey] = $stageContext->promotions[$key]->missCode;//['code'=>$stageContext->promotions[$key]->missCode, 'message'=>$stageContext->promotions[$key]->message];
                    if(!isset($context->missCodeMessage[$stageContext->promotions[$key]->missCode]))
                        $context->missCodeMessage[$stageContext->promotions[$key]->missCode] = $stageContext->promotions[$key]->message;
                }

                break;
            }
        }

        if($workable){
            //check whether promotion is used, if it's minsave > current plan save
            $stagePromotions = $context->getStagePromotions($stageKey);
            foreach ($stagePromotions as $promotion){
                if(bccomp($promotion->minSave,$planContext->totalSaved, 2) == 1){
                    $calcResult->increaseRunCnt('stage_skip_5');
                    $calcResult->addLog('stage_skip_5', "plan_".$promotionPlansSeq."_".$calcResult->planCalculatorTotalCnt,
                        'plan: idle '.$promotion->key.' minsave '.$promotion->minSave.' > current plan save '.$planContext->totalSaved. ", ".json_encode($planContext->planDefine)
                    );
                    $workable = false;
                    break;
                }
            }
            unset($promotion);
        }

        if($workable){
            //ignroe same sku plan, only need it once in the workable plan list
            $workablePlanSameSku = [];
            foreach($curCombination as $key=>$p){
                $workablePlanSameSku[] = $key.'-'.$context->getGoodsSameSkuKeyRaw($p);
            }

            $workablePlanSameSku = join("#", $workablePlanSameSku);

            if(isset($context->workablePlanSameSkuCache[$workablePlanSameSku])){
                $calcResult->increaseRunCnt('stage_skip_6');
                //$context->addLog("planSku", "new Sku skip: ".$workablePlanSameSku.", ".json_encode($curCombination));
                $calcResult->addLog('stage_skip_6', "plan_".$promotionPlansSeq."_".$calcResult->planCalculatorTotalCnt,
                    'plan: same sku exists',
                    json_encode($workablePlanSameSku)
                );
                $workable = false;
            }
            else{
                $context->workablePlanSameSkuCache[$workablePlanSameSku] = 1;
            }
        }

        if($workable){
            //LOG
            $planGoodsItems = $planContext->planDefine;

            $context->addLog("plan", "new plan: stage ".$stageKey."_".($stageContext->context->planCnt++).', '.json_encode($planGoodsItems, JSON_UNESCAPED_UNICODE));

            $calcResult->planCalculatorValidCnt++;

            //record
            $planContext->workable = true;
            $context->solutionData[$stageKey][] = $context->planContextCacheIdx;
            $context->planContextCache[$context->planContextCacheIdx] = ['planDefine'=>$planContext->planDefine,
                'goodsItemsContext'=>$planContext->goodsItemsContext];
            $context->planContextCacheIdx++;
            unset($planContext);

            $calcResult->addLog('', "plan_".$promotionPlansSeq."_".$calcResult->planCalculatorTotalCnt,
                '=========='.json_encode($curCombination),
                null
            );
        }
        else{
            unset($planContext);
            $invalidCombination = true;
        }

        return !$invalidCombination;
    }
}