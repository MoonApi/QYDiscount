<?php


namespace QyDiscount\lib\CheckRuleOptimizer\Solution;


use QyDiscount\lib\CalcResult;

class PromotionMutualSolutionCheckRule extends \QyDiscount\lib\CheckRuleOptimizer\CheckRule
{

    function check($params, CalcResult &$calcResult)
    {
        list($planContextCacheIdxList, $context, $solutionSeq) = $params;
        $stages = array_keys($context->solutionData);

        $goodsPromtionCache = [];
        $invalidSolution = false;

        for($i=0;$i<count($planContextCacheIdxList);$i++){
            $planContextCacheIdx = $planContextCacheIdxList[$i];
            $planDefine = $context->planContextCache[$planContextCacheIdx]['planDefine'];

            foreach($planDefine as $key=>$goodsItems){
                foreach ($goodsItems as $idx){
                    if(!isset($goodsPromtionCache[$idx]))
                        $goodsPromtionCache[$idx] = [$key];
                    else
                        $goodsPromtionCache[$idx][] = $key;

                    foreach ($goodsPromtionCache[$idx] as $item){
                        if(in_array($key, $context->goodsPromotionExclusiveList[$idx][$item])){
                            $calcResult->increaseRunCnt('solution_skip_1');
                            $calcResult->addLog('solution_skip_1', "solution_".$solutionSeq,
                                'promotion mutual, goods: '.$idx.", promotion: ".$key.", ".$item,
                                null
                            );
                            $invalidSolution = true;
                            break;
                        }
                    }

                    if($invalidSolution)
                        break;
                }

                if($invalidSolution)
                    break;
            }
        }

        return !$invalidSolution;
    }
}