<?php
namespace QyDiscount\lib;

use IteratorIterator;
use QyDiscount\lib\CalcResult;
use QyDiscount\lib\CheckRuleOptimizer\Plan\MaxSaveCheckRule;
use QyDiscount\lib\CheckRuleOptimizer\Plan\PlanPreCheckRule;
use QyDiscount\lib\CheckRuleOptimizer\PlanGenerator\EstimatePlanGenerator;
use QyDiscount\lib\CheckRuleOptimizer\PlanGenerator\MinStartPlanGenerator;
use QyDiscount\lib\CheckRuleOptimizer\PlanGenerator\NotWorkableCacheCheckRule;
use QyDiscount\lib\CheckRuleOptimizer\Promotion\PromotionCheckRule;
use QyDiscount\lib\CheckRuleOptimizer\Solution\EstimatedSolutionPriceCheckRule;
use QyDiscount\lib\CheckRuleOptimizer\Solution\PromotionMutualSolutionCheckRule;
use QyDiscount\lib\CheckRuleOptimizer\Solution\SolutionWorkableCheckRule;
use QyDiscount\lib\CheckRuleOptimizer\Result\PostArrangeCheckRule;
use QyDiscount\lib\CheckRuleOptimizer\Stage\EstimatedStageSaveCheckRule;
use QyDiscount\lib\CheckRuleOptimizer\Stage\IdleGoodsStageCheckRule;
use QyDiscount\lib\CheckRuleOptimizer\Stage\IdlePromotionCheckRule;
use QyDiscount\lib\CheckRuleOptimizer\Stage\MaxSaveStageCheckRule;
use QyDiscount\lib\CheckRuleOptimizer\Stage\PlanWorkableStageCheckRule;
use QyDiscount\lib\CheckRuleOptimizer\Stage\PromotionStageCheckRule;
use QyDiscount\lib\CheckRuleOptimizer\Stage\PromotionMtualStageCheckRule;
use QyDiscount\lib\Permutation\GeneratorCombination;
use function QyDiscount\lib\Permutation\cartesian_product;
use function QyDiscount\lib\Permutation\promotion_combination;

class Calculator
{
    public $planCnt = 0;

    public function calc(Context &$context){
        $calcResult = $context->calcResult;
        $calcResult->init($context->originalPrice);
        $calcResult->startTimer('calculator');

        $checkRules = ['plan'=>[], 'promotion'=>[], 'plan-generator-start'=>[], 'plan-generator-estimate'=>[], 'plan-generator-cache'=>[], 'stage'=>[], 'solution'=>[]];
        $checkRules['plan'][] = new PlanPreCheckRule();
        $checkRules['plan'][] = new MaxSaveCheckRule();
        $checkRules['promotion'][] = new PromotionCheckRule();
        $checkRules['plan-generator-start'][] = new MinStartPlanGenerator();
        $checkRules['plan-generator-estimate'][] = new EstimatePlanGenerator();
        $checkRules['plan-generator-cache'][] = new NotWorkableCacheCheckRule();
        $checkRules['stage'][] = new MaxSaveStageCheckRule();
        $checkRules['stage'][] = new PromotionMtualStageCheckRule();
        $checkRules['stage'][] = new PromotionStageCheckRule();
        //$checkRules['stage'][] = new EstimatedStageSaveCheckRule();
        $checkRules['stage'][] = new IdleGoodsStageCheckRule();
        $checkRules['stage'][] = new IdlePromotionCheckRule();
        $checkRules['stage'][] = new PlanWorkableStageCheckRule();
        $checkRules['solution'][] = new EstimatedSolutionPriceCheckRule();
        $checkRules['solution'][] = new PromotionMutualSolutionCheckRule();
        $checkRules['solution'][] = new SolutionWorkableCheckRule();
        $checkRules['result'][] = new PostArrangeCheckRule();

        $calcResult->initCheckRuleCounter($checkRules);
        $context->initPromotionTracing($checkRules);

        $promotionPlansSeq = 0;

        $calcResult->startTimer('stage');

        foreach ($context->stageOrder as $stageKey){
            $stagePromotions = $context->getStagePromotions($stageKey);

            if(count($stagePromotions) == 0) continue;

            if(!isset($context->solutionData[$stageKey])) $context->solutionData[$stageKey] = [];

            //remove all required and add them later as one, as no need to use their sub combination
            $stagePromotionRequired = $context->getStagePromotionRequired($stageKey);
            $stagePromotionRequiredKeys = array_keys($stagePromotionRequired);

            foreach ($stagePromotions as $key=>$p){
                if(in_array($key, $stagePromotionRequiredKeys)){
                    unset($stagePromotions[$key]);
                }
            }

            $generator = promotion_combination(array_keys($stagePromotions));
           // $context->addLog("promotion_combination", json_encode($context->validPromotionIds[$stageKey]));

            //$ss, combination of promotion, eg: [['A','B'],['A','C']]
            foreach($generator as $ss){
                $ss = array_merge($ss, $stagePromotionRequiredKeys);
                $calcResult->startTimer('stageContext_1');
                foreach ($checkRules['plan'] as $planCheckRule){
                    $validPlan = $planCheckRule->check([$ss, $context, $stageKey], $calcResult);

                    $calcResult->increaseCheckRuleCounter($planCheckRule->getName(), 1, $validPlan?1:0);

                    if(!$validPlan)
                        break;
                }
                $calcResult->pauseTimer('stageContext_1');

                if(!$validPlan){
                    continue;
                }

                $calcResult->startTimer('stageContext_2');

                //$context->addLog("promotion_combination", 'start++++++++++'.json_encode($ss));
                $stageContext = new StageContext($context, $ss, $context->promotions, $context->goodsItems);

                $promotionPlans = [];
//                foreach ($stageContext->stageSolution as $promotionKey){
//                    $promotionPlans[$promotionKey] = $context->getGoodsCombination($stageContext->promotions[$promotionKey]->goodsItemsAll);
//                }

                $promotionPlanGenerators = [];
                foreach ($stageContext->stageSolution as $promotionKey){
                    $promotionPlanGenerators[$promotionKey] = $stageContext->promotions[$promotionKey]->goodsItemsAll;//promotion_combination($stageContext->promotions[$promotionKey]->goodsItemsAll)->getIterator();
                }

                $GeneratorCombination = new GeneratorCombination($promotionPlanGenerators, $context, function($promotionKey, $goodsitems)use($checkRules, $context, $stageContext, $stageKey, $calcResult){
                    $calcResult->increaseRunCnt('stageContext_2.1');
                    foreach ($checkRules['promotion'] as $planCheckRule){
                        $calcResult->startTimer('stageContext_2.1.'.$planCheckRule->getName());
                        $validPrmotion = $planCheckRule->check([$promotionKey, $goodsitems, $context, $stageContext, $stageKey], $calcResult);

                        $calcResult->increaseCheckRuleCounter($planCheckRule->getName(), 1, $validPrmotion?1:0);

                        if(!$validPrmotion){
                            $calcResult->pauseTimer('stageContext_2.1.'.$planCheckRule->getName());
                            return false;
                        }
                        $calcResult->pauseTimer('stageContext_2.1.'.$planCheckRule->getName());
                    }
                    return true;
                }, function(&$generators, $generatorsSource, $cur) use($checkRules, $context, $stageContext, $stageKey, $calcResult){
                    $calcResult->increaseRunCnt('stageContext_2.3');
                    $minStartArr = [];
                    foreach ($checkRules['plan-generator-start'] as $planCheckRule){
                        $calcResult->startTimer('stageContext_2.3.'.$planCheckRule->getName());
                        $minStart = $planCheckRule->check([$generators, $generatorsSource, $cur, $context, $stageContext, $stageKey], $calcResult);

                        $calcResult->increaseCheckRuleCounter($planCheckRule->getName(), 1, $minStart === false?0:1);

                        if($minStart === false){
                            $calcResult->pauseTimer('stageContext_2.3.'.$planCheckRule->getName());
                            return false;
                        }

                        $minStartArr[] = $minStart;

                        $calcResult->pauseTimer('stageContext_2.3.'.$planCheckRule->getName());
                    }

                    if(count($minStartArr) > 0){
                        return max($minStartArr);
                    }

                    return 1;
                }, function(&$generators) use($checkRules, $context, $stageContext, $stageKey, $calcResult){
                    $calcResult->increaseRunCnt('stageContext_2.4');
                    foreach ($checkRules['plan-generator-estimate'] as $planCheckRule){
                        $calcResult->startTimer('stageContext_2.4.'.$planCheckRule->getName());
                        $estimateValid = $planCheckRule->check([$generators, $context, $stageContext, $stageKey], $calcResult);

                        $calcResult->increaseCheckRuleCounter($planCheckRule->getName(), 1, $estimateValid === false?0:1);

                        if(!$estimateValid){
                            $calcResult->pauseTimer('stageContext_2.4.'.$planCheckRule->getName());
                            return false;
                        }

                        $calcResult->pauseTimer('stageContext_2.4.'.$planCheckRule->getName());
                    }
                    return true;
                }, function($promotionKey, $goodsitems) use($checkRules, $context, $stageContext, $stageKey, $calcResult){
                    $calcResult->increaseRunCnt('stageContext_2.5');
                    foreach ($checkRules['plan-generator-cache'] as $planCheckRule){
                        $calcResult->startTimer('stageContext_2.5.'.$planCheckRule->getName());
                        $cacheValid = $planCheckRule->check([$promotionKey, $goodsitems, $context, $stageContext, $stageKey], $calcResult);

                        $calcResult->increaseCheckRuleCounter($planCheckRule->getName(), 1, $cacheValid === false?0:1);

                        if(!$cacheValid){
                            $calcResult->pauseTimer('stageContext_2.5.'.$planCheckRule->getName());
                            return false;
                        }

                        $calcResult->pauseTimer('stageContext_2.5.'.$planCheckRule->getName());
                    }
                    return true;
                });

                ////get all valid plan, [['A'=>[[0],[1],[0,1]],'B'=>...],['A'=>...,'C'=>...]]
                ////get all valid plan, [['A'=>[0,1],'B'=>...],['A'=>...,'C'=>...]]
                foreach ($GeneratorCombination as $curCombination){
                    if(count($curCombination) == 0)
                        break;

                    $context->addLog("Combination",
                        json_encode($curCombination)
                    );

                    foreach ($checkRules['stage'] as $planCheckRule){
                        $calcResult->startTimer('stageContext_2.2.'.$planCheckRule->getName());
                        $validCombination = $planCheckRule->check([$curCombination, $context, $stageContext, $stageKey, $promotionPlansSeq], $calcResult);
                        //$cntOut = count($promotionPlans, true);

                        $calcResult->increaseCheckRuleCounter($planCheckRule->getName(), 1, $validCombination?1:0);

                        $calcResult->pauseTimer('stageContext_2.2.'.$planCheckRule->getName());
                        if(!$validCombination)
                            break;
                    }
                }

                $calcResult->pauseTimer('stageContext_2');

                $promotionPlansSeq++;

//                $calcResult->addLog('', "promotionPlans_".$promotionPlansSeq,
//                    $stageKey.'_promotionPlans',
//                    json_encode($promotionPlans)
//                );

//                $calcResult->startTimer('stageContext_3');
//                $promotionPlansCnt = count(cartesian_product($promotionPlans));
//                $context->promotionPlansCnt += $promotionPlansCnt;
//
//                //$context->addLog("promotionPlans", "new promotionPlans: ".$promotionPlansCnt.", ".$context->promotionPlansCnt);
//
//                //combination of promotion and its goodsitems
//                foreach (cartesian_product($promotionPlans) as $curCombination){
//
//                    if($calcResult->planCalculatorTotalCnt % 1000 == 0){
//                        $context->addLog('memory', $calcResult->planCalculatorTotalCnt.", ".json_encode($context->getMemoryInfo($calcResult->planCalculatorTotalCnt), JSON_UNESCAPED_UNICODE));
//                    }
//
//                    $calcResult->planCalculatorTotalCnt++;
//
//                    foreach ($checkRules['stage'] as $planCheckRule){
//                        $validCombination = $planCheckRule->check([$curCombination, $context, $stageContext, $stageKey, $promotionPlansSeq], $calcResult);
//
//                        $calcResult->increaseCheckRuleCounter($planCheckRule->getName(), 1, $validCombination?1:0);
//
//                        if(!$validCombination)
//                            break;
//                    }
//                }
//                foreach ($checkRules['plan'] as $planCheckRule){
//                    //$cntIn = count($promotionPlans, true);
//                    $cntIn = 0;
//                    $cntOut = 0;
//
//                    $promotionPlans = $planCheckRule->check([$promotionPlans, $stageContext, $context, $stageKey], $calcResult);
//                    //$cntOut = count($promotionPlans, true);
//
//                    $calcResult->increaseCheckRuleCounter($planCheckRule->getName(), $cntIn, $cntOut);
//
//                    if(count($promotionPlans) == 0)
//                        break;
//                }

//                $calcResult->pauseTimer('stageContext_2');
//
//                if(count($promotionPlans) == 0){
//                    //$context->addLog("promotion_combination", 'end--+++--'.json_encode($ss));
//                    continue;
//                }
//
//                $promotionPlansSeq++;
//
////                $calcResult->addLog('', "promotionPlans_".$promotionPlansSeq,
////                    $stageKey.'_promotionPlans',
////                    json_encode($promotionPlans)
////                );
//
//                $calcResult->startTimer('stageContext_3');
//                $promotionPlansCnt = count(cartesian_product($promotionPlans));
//                $context->promotionPlansCnt += $promotionPlansCnt;
//
//                //$context->addLog("promotionPlans", "new promotionPlans: ".$promotionPlansCnt.", ".$context->promotionPlansCnt);
//
//                //combination of promotion and its goodsitems
//                foreach (cartesian_product($promotionPlans) as $curCombination){
//
//                    if($calcResult->planCalculatorTotalCnt % 1000 == 0){
//                        $context->addLog('memory', $calcResult->planCalculatorTotalCnt.", ".json_encode($context->getMemoryInfo($calcResult->planCalculatorTotalCnt), JSON_UNESCAPED_UNICODE));
//                    }
//
//                    $calcResult->planCalculatorTotalCnt++;
//
//                    foreach ($checkRules['stage'] as $planCheckRule){
//                        $validCombination = $planCheckRule->check([$curCombination, $context, $stageContext, $stageKey, $promotionPlansSeq], $calcResult);
//
//                        $calcResult->increaseCheckRuleCounter($planCheckRule->getName(), 1, $validCombination?1:0);
//
//                        if(!$validCombination)
//                            break;
//                    }
//                }

                unset($stageContext);
                //$context->addLog("promotion_combination", 'end------'.json_encode($ss));
                //$calcResult->pauseTimer('stageContext_3');
            }
        }

        $context->promotionFullFilledCache = [];
        $context->workablePlanSameSkuCache = [];

        $calcResult->pauseTimer('stage');

        $calcResult->startTimer('solution');
        foreach ($context->solutionData as $key=>$value){
            if(count($value) == 0){
                unset($context->solutionData[$key]);
            }
        }

        if(count($context->solutionData) == 0){
            $calcResult->status = false;

            $context->addLog('result', json_encode($calcResult->getInfo(), JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));

            $context->logFlush();

            $calcResult->pauseTimer('calculator');

            return $calcResult;
        }

        $calcResult->solutionData = $context->solutionData;

        $solutionDataLog = [];
        foreach ($context->solutionData as $stage=>$sd){
            $solutionDataLog[$stage] = [];
            foreach ($sd as $v){
                $solutionDataLog[$stage][] = $context->planContextCache[$v]['planDefine'];
            }
        }

        $context->addLog("solutionData", json_encode($solutionDataLog, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));

        foreach (cartesian_product($context->solutionData) as $curItem){

            $invalidSolution = false;
            $planContextCacheIdxList = array_values($curItem);
            $calcResult->increaseRunCnt('solution');

            $solutionSeq = $calcResult->solutionCalculatorTotalCnt++;

//            $calcResult->addLog('', "solution_".$solutionSeq,
//                "start ".json_encode($context->getPlanDefineList($planContextCacheIdxList)),
//                json_encode($context->getPlanDefineList($planContextCacheIdxList))
//            );

            foreach ($checkRules['solution'] as $solutionCheckRule){
                $validSolution = $solutionCheckRule->check([$planContextCacheIdxList, $context, $solutionSeq], $calcResult);

                $calcResult->increaseCheckRuleCounter($solutionCheckRule->getName(), 1, $validSolution?1:0);

                if(!$validSolution)
                    break;
            }

            if($invalidSolution)
                continue;

//            $calcResult->addLog('', "solution_".$solutionSeq,
//                "end=========="
//            );
        }
        $calcResult->pauseTimer('solution');

        $calcResult->startTimer('result');
        foreach ($checkRules['result'] as $arrangeCheckRule){
            $doNext = $arrangeCheckRule->check([$context], $calcResult);

            $calcResult->increaseCheckRuleCounter($arrangeCheckRule->getName(), 1, $doNext?1:0);
            if(!$doNext)
                break;
        }
        $calcResult->pauseTimer('result');

        $calcResult->pauseTimer('calculator');

        $context->addLog('result', json_encode($calcResult->getInfo(), JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));

        $context->logFlush();

        return $calcResult;
    }

    public function calcOriginalAmount(Context &$context){
        $totalAmount = 0;

        foreach ($context->goodsItems as $goodsItem) {
            $totalAmount += $goodsItem->amount;
        }

        return $totalAmount;
    }
}