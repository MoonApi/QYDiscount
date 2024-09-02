<?php


namespace QyDiscount\lib\CheckRuleOptimizer\Solution;


use QyDiscount\lib\CalcResult;
use QyDiscount\lib\SolutionContext;

class SolutionWorkableCheckRule extends \QyDiscount\lib\CheckRuleOptimizer\CheckRule
{

    function check($params, CalcResult &$calcResult)
    {
        list($planContextCacheIdxList, $context, $solutionSeq) = $params;
        $stages = array_keys($context->solutionData);
        $invalidSolution = false;

        $solutionContext = new SolutionContext($context, $planContextCacheIdxList, $context->planContextCache, $context->promotions, $context->goodsItems, $stages);

        $solutionContext->checkSolution($calcResult, $solutionSeq);

        if($solutionContext->workable){
            //check whether promotion is used, if it's maxsave > current solution save
            if(bccomp($context->promtionMaxSave, $solutionContext->totalSaved, 2) == 1){
                $calcResult->increaseRunCnt('solution_skip_2');
                $calcResult->addLog('solution_skip_2', "solution_".$solutionSeq,
                    'solution: promtionMaxSave '.$context->promtionMaxSave.' > current solution save '.$solutionContext->totalSaved,
                    null
                );
                $solutionContext->workable = false;
            }
        }

        if($solutionContext->workable){
            $calcResult->status = true;

            $calcResult->solutionCalculatorValidCnt++;

            if(bccomp($calcResult->finalPrice, $solutionContext->actualAmount, 2) == 1){
                foreach ($calcResult->finalSolutionSame as $key=>$solution){
                    unset($calcResult->finalSolutionSame[$key]);
                }

                $calcResult->finalPrice = $solutionContext->actualAmount;
                $calcResult->finalSolutionSame = [$solutionContext];
            }
            else if(bccomp($calcResult->finalPrice, $solutionContext->actualAmount, 2) == 0){
                $calcResult->finalSolutionSame[] = $solutionContext;
            }
            else{
                $calcResult->increaseRunCnt('solution_skip_5');
                $calcResult->addLog('solution_skip_5', "solution_".$solutionSeq,
                    'solution: less save '.$solutionContext->actualAmount.' > finalPrice '.$calcResult->finalPrice,
                    null
                );
                unset($solutionContext);
                $invalidSolution = true;
            }
        }
        else{
            unset($solutionContext);
            $invalidSolution = true;
        }
        return !$invalidSolution;
    }
}