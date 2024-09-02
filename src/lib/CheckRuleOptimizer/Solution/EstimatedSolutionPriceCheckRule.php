<?php


namespace QyDiscount\lib\CheckRuleOptimizer\Solution;


use QyDiscount\lib\CalcResult;
use QyDiscount\lib\SolutionContext;

class EstimatedSolutionPriceCheckRule extends \QyDiscount\lib\CheckRuleOptimizer\CheckRule
{

    function check($params, CalcResult &$calcResult)
    {
        list($planContextCacheIdxList, $context, $solutionSeq) = $params;
        $invalidSolution = false;

        $estimateSolutionPrice = SolutionContext::estimateSolutionPrice($planContextCacheIdxList, $context);

        //$calcResult->finalPrice is updated at each solution, use current finalPrice to filter the later solution
        if(bccomp($calcResult->finalPrice, $estimateSolutionPrice) < 0)
            $invalidSolution = true;
        else if(bccomp($context->promtionMaxSave, ($context->originalPrice - $estimateSolutionPrice), 2) == 1){
            $calcResult->increaseRunCnt('solution_skip_6');
            $calcResult->addLog('solution_skip_6', "solution_".$solutionSeq,
                'solution: promtionMaxSave '.$context->promtionMaxSave.' > current save '.($context->originalPrice - $estimateSolutionPrice).", ".json_encode($context->getPlanDefineList($planContextCacheIdxList)),
            );
            $invalidSolution = true;
        }

        //return $calcResult->finalPrice > $estimateSolutionPrice && $context->promtionMaxSave <= ($context->originalPrice - $estimateSolutionPrice);
        return !$invalidSolution;
    }
}