<?php


namespace QyDiscount\lib;


class SolutionContext
{
    public $solution;
    public $stages;
    public $promotions;
    public $context;

    public $totalSaved = 0;
    public $workable = false;

    public $amount = 0;
    public $actualAmount = 0;

    public $planContextCache = [];

    /** @var GoodsItem[] $goodsItems */
    public array $goodsItems;
    public $goodsItemsContext;

    public $promotionSaved = [];

    public function __construct(&$context, $solution, &$planContextCache, &$promotions, &$goodsItems, $stages){
        $this->solution = $solution;
        $this->planContextCache = $planContextCache;
        $this->promotions = $promotions;
        $this->goodsItems = $goodsItems;
        $this->stages = $stages;
        $this->context = $context;

        foreach ($goodsItems as $key=>$goodsItem){
            $this->amount += $goodsItem->amount;
            $this->goodsItemsContext[$key] = new GoodsItemContext($key, $goodsItem->amount);
        }

        $planDefineList = $context->getPlanDefineList($this->solution);

        foreach ($promotions as $key=>$promotion){
            $this->promotionSaved[$key]['goodsitems'] = $promotion->goodsItemsAll;
            $this->promotionSaved[$key]['saved'] = 0;
            $this->promotionSaved[$key]['minSave'] = round($promotion->minSave,2);
            $this->promotionSaved[$key]['maxSave'] = round($promotion->maxSave,2);
        }

        $context->addLog("solution", "new solution: ".json_encode($planDefineList));
    }

    public static function estimateSolutionPrice($planContextCacheIdxList, Context $context){
        $totalSaved = 0;
        for($i=0;$i<count($planContextCacheIdxList);$i++){
            $planContextCacheIdx = $planContextCacheIdxList[$i];
            $planContextCacheItem = $context->planContextCache[$planContextCacheIdx];

            foreach ($planContextCacheItem['goodsItemsContext'] as $idx=>$goodsItem){
                foreach ($goodsItem->promotionRecords as $promotionRecord){
                    $totalSaved += $promotionRecord['saved'];
                }
            }
        }

        $amount = 0;
        foreach ($context->goodsItems as $key=>$goodsItem){
            $amount += $goodsItem->amount;
        }

        return $amount - $totalSaved;
    }

    public function checkSolution(&$calcResult, $solutionSeq){
        $this->workable = false;

        $planContextCacheIdxList = $this->solution;

        for($i=0;$i<count($planContextCacheIdxList);$i++){
            $planContextCacheIdx = $planContextCacheIdxList[$i];//$this->context->solutionData[$this->stages[$i]][$planContextCacheIdxList[$i]];
            $planContextCacheItem = $this->planContextCache[$planContextCacheIdx];

            foreach ($planContextCacheItem['goodsItemsContext'] as $idx=>$goodsItem){
                foreach ($goodsItem->promotionRecords as $promotionRecord){
                    $promotion = $this->promotions[$promotionRecord['promotion_key']];

                    if(in_array($promotion->bondType, $this->goodsItemsContext[$idx]->promotionExclusiveList)){
                        $calcResult->increaseRunCnt('solution_skip_3');
                        $calcResult->addLog('solution_skip_3', "solution_".$solutionSeq,
                            'promotion is exclusived, goods: '.$idx.", promotion: ".$promotion->key.' '.$promotion->bondType.", ".json_encode($this->goodsItemsContext[$idx]->promotionExclusiveList),
                            null
                        );
                        return false;
                    }

                    foreach ($this->goodsItemsContext[$idx]->promotionRecords as $promotionRecord2){
                        if(in_array($promotionRecord2['bond_type'], $promotion->exclusiveList)){
                            $calcResult->increaseRunCnt('solution_skip_4');
                            $calcResult->addLog('solution_skip_4', "solution_".$solutionSeq,
                                'promotion exclusive, goods: '.$idx.", promotion: ".$promotionRecord2['promotion_key'].' '.$promotionRecord2['bond_type'].", ".json_encode($promotion->exclusiveList),
                                null
                            );
                            return false;
                        }
                    }

                    $this->totalSaved += $promotionRecord['saved'];
                    $this->goodsItemsContext[$idx]->promotionRecords[] = $promotionRecord;
                    $this->goodsItemsContext[$idx]->isPromoted[$promotionRecord['bond_type']] = true;
                    $this->goodsItemsContext[$idx]->actualAmount -= $promotionRecord['saved'];

                    $this->promotionSaved[$promotionRecord['promotion_key']]['saved'] += $promotionRecord['saved'];

                    $this->goodsItemsContext[$idx]->promotionExclusiveList = array_merge($this->goodsItemsContext[$idx]->promotionExclusiveList, $promotion->exclusiveList);
                }
            }
        }

        foreach ($this->goodsItemsContext as $key=>$goodsItem){
            $this->actualAmount += $goodsItem->actualAmount;
            $this->goodsItemsContext[$key]->actualAmount = round($goodsItem->actualAmount, 2);
        }

        foreach ($this->promotionSaved as $key=>$item){
            $this->promotionSaved[$key]['saved'] = round($item['saved'], 2);
        }

        $this->actualAmount = round($this->actualAmount, 2);
        $this->totalSaved = round($this->totalSaved, 2);
        $this->workable = true;
        return true;
    }

}