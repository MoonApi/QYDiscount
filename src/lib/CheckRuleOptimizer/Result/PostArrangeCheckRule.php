<?php


namespace QyDiscount\lib\CheckRuleOptimizer\Result;


use QyDiscount\lib\CalcResult;
use QyDiscount\lib\PlanContext;
use QyDiscount\lib\SolutionContext;
use QyDiscount\lib\StageContext;

class PostArrangeCheckRule extends \QyDiscount\lib\CheckRuleOptimizer\CheckRule
{

    function check($params, CalcResult &$calcResult)
    {
        list($context) = $params;
        $stages = array_keys($context->solutionData);

        if(!$calcResult->status) return true;

        if(count($calcResult->finalSolutionSame) == 0) return false;

        $finalSolution = $calcResult->finalSolutionSame[0];

        $planDefineList = $context->getPlanDefineList($finalSolution->solution);

        $idleGoodsItems = [];
        $planGoodsItems = [];
        foreach ($context->goodsItems as $key=>$goodsItem) {
            $idleGoodsItems[$goodsItem->idx] = [];

            foreach ($planDefineList as $pdKey=>$pd){
                foreach ($pd as $promotionKey=>$items){
                    $planStr = $promotionKey.'-'.join("-", $items);

                    if(!isset($planGoodsItems[$planStr]))
                        $planGoodsItems[$planStr] = [];

                    if(!isset($idleGoodsItems[$goodsItem->idx][$planStr]))
                        $idleGoodsItems[$goodsItem->idx][$planStr] = [];

                    if(isset($context->sameFullFilledPlan[$planStr])){
                        foreach ($context->sameFullFilledPlan[$planStr] as $k=>$plan){
                            if(in_array($goodsItem->idx, $plan)){
                                if(count($finalSolution->goodsItemsContext[$key]->promotionRecords) == 0){
                                    $idleGoodsItems[$goodsItem->idx][$planStr][] = $k;
                                }

                                if(!isset($planGoodsItems[$planStr][$k]))
                                    $planGoodsItems[$planStr][$k] = ['pdKey'=>$pdKey, 'promotionKey'=>$promotionKey, 'goodsitems'=>[]];
                                $planGoodsItems[$planStr][$k]['goodsitems'] = $plan;
                            }
                        }
                    }
                }
            }
        }

        foreach ($planGoodsItems as $planStr=>$item){
            $max = 0;
            $maxK = 0;
            foreach($item as $k=>$info){
                if(count($info['goodsitems']) > $max){
                    $isExclusive = false;
                    foreach ($info['goodsitems'] as $idx){
                        if(in_array($idx, $planDefineList[$info['pdKey']][$info['promotionKey']])) continue;
                        if(in_array($context->promotions[$info['promotionKey']]->bondType, $finalSolution->goodsItemsContext[$idx]->promotionExclusiveList)){
                            $isExclusive = true;
                            break;
                        }
                    }
                    if(!$isExclusive){
                        $max = count($info['goodsitems']);
                        $maxK = $k;
                    }
                    else{
                        break;
                    }
                }
            }

            if($max > 0){
                $planDefineList[$item[$maxK]['pdKey']][$item[$maxK]['promotionKey']] = $context->sameFullFilledPlan[$planStr][$maxK];
            }
        }

        $solution = [];

        foreach ($planDefineList as $stageKey=>$stage){
            $stageContext = new StageContext($context, $stage, $context->promotions, $context->goodsItems);
            $planContext = new PlanContext($stage, $stageContext, $stageContext->goodsItems);
            $stageContext->initStage();

            foreach($planContext->planDefine as $key=>$p){
                $stageContext->promotions[$key]->setGoodsItems($p);
                $stageContext->promotions[$key]->apply($planContext);
                $context->setPromotionTracing($key, $this);
            }

            $solution[] = $context->planContextCacheIdx;
            $context->planContextCache[$context->planContextCacheIdx] = ['planDefine'=>$planContext->planDefine,
                'goodsItemsContext'=>$planContext->goodsItemsContext];
            $context->planContextCacheIdx++;
        }

        $solutionSeq = $calcResult->solutionCalculatorTotalCnt++;

        $solutionContext = new SolutionContext($context, $solution, $context->planContextCache, $context->promotions, $context->goodsItems, $stages);

        $solutionContext->checkSolution($calcResult, $solutionSeq);

        $calcResult->finalPrice = $solutionContext->actualAmount;
        $calcResult->finalSolution = $solutionContext;

        if($calcResult->finalPrice != $finalSolution->actualAmount){
            print("!!!!error\n");
        }

        return true;
    }
}