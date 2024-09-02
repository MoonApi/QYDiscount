<?php


namespace QyDiscount\lib;

use function QyDiscount\lib\Permutation\promotion_combination;

class Context
{
    public $originalPrice;
    public $calcResult;

    /** @var GoodsItem[] $goodsItems */
    public array $goodsItems;
    public array $goodsPromotionExclusiveList = [];
    public array $goodsItemSameSkuInfo = [];

    public $promotions;
    public $permutation;
    public $stageCnt = 0;
    public $planCnt = 0;
    public $promotionPlansCnt = 0;

    public $missCodeMessages = [];

    //public $stateContext;
    public $stageContextList;

    //'order','shops','shop','goods','subtotal','category','bundle','package','delivery','paid'
    public $stageOrder = array('goods','category','bundle','package','subtotal','shop','shops','order','delivery','paid');


    public $solutionData = [];

    public $notWorkableCache = [];
    public $workableCache = [];
    public $minWorkableCache = [];
    public $planContextCache = [];
    public $planContextCacheIdx = 0;
    public $workablePlanSameSkuCache = [];
    public $notWorkablePromotionPlans = [];

    public $sameFullFilledPlan = [];
    public $sameFullFilledPlanKey = [];
    public $sameFullFilledPlanL2SMap = []; //long to short map
    //public $goodsItemCombinationCache = [];

    public $goodsSameSkuCache = [];
    public $planFlipCache = [];

    public $runMode;
    public $runId = 0;
    public $logCache = [];
    public $logCnt = 0;
    public $runName = 0;

    public $stagePromotions = [];
    public $stagePromotionRequired = [];
    public $promtionMaxSave = 0;
    public $stageGoodsItems = [];
    public $stageMinSave = [];  //min save has exclude all shared with other stage goodsitems
    public $planGoodsItemTotalCnt = [];

    public $nextGeneratorMinStartCache = [];

    public $config = [];

    public $promotionTracing = [];

    public function __construct(&$goodsItems, &$promotions, $runMode = 'debug', $runName='') {
        $this->config = require("config.php");

        $this->calcResult = new CalcResult($this);
        $this->calcResult->startTimer('initContext');

        $this->goodsItems = $goodsItems;
        $this->promotions = $promotions;

        $this->permutation = new \QyDiscount\lib\Permutation();

        foreach ($goodsItems as $goodsItem){
            $this->goodsItemSameSkuInfo[$goodsItem->idx] = -1;

            foreach ($goodsItems as $goodsItem2){
                if($goodsItem2->idx >= $goodsItem->idx){
                    break;
                }

                if($goodsItem2->cartId == $goodsItem->cartId){
                    $this->goodsItemSameSkuInfo[$goodsItem->idx] = $goodsItem2->idx;
                    break;
                }
            }
        }

        $this->runId = date("YmdHsi").rand(100, 999);
        $this->runMode = $runMode;
        $this->runName = $runName;

        foreach($this->promotions as $key=>$p){
            $this->promotionTracing[$key] = [];
            $this->promotions[$key]->init($this, $this->goodsItems);
        }

        foreach ($goodsItems as $goodsItem){
            foreach ($this->goodsPromotionExclusiveList as $key=>$item){
                if($goodsItems[$key]->cartId == $goodsItem->cartId){
                    $this->goodsPromotionExclusiveList[$goodsItem->idx] = $item;
                    break;
                }
            }

            if(isset($this->goodsPromotionExclusiveList[$goodsItem->idx])) continue;

            $this->goodsPromotionExclusiveList[$goodsItem->idx] = [];

            foreach ($promotions as $key=>$promotion){
                if(in_array($goodsItem->idx, $promotion->goodsItemsAll)){
                    $exclusivePromotion = [];
                    foreach ($promotion->exclusiveList as $item){
                        foreach ($promotions as $key2=>$promotion2){
                            if($promotion2->bondType == $item && $key != $key2){
                                $exclusivePromotion[] = $key2;
                            }
                        }
                    }
                    $this->goodsPromotionExclusiveList[$goodsItem->idx][$key] = $exclusivePromotion;
                }
            }
        }

        foreach($this->promotions as $key=>$p){
            $this->promotions[$key]->preAnalyse($this->promotions);
        }

        foreach ($this->stageOrder as $item){
            $this->stageGoodsItems[$item] = [];
            $this->stageMinSave[$item] = 0;
        }

        foreach ($this->promotions as $promotion){
            if(bccomp($promotion->maxSave, $this->promtionMaxSave, 2) == 1){
                $this->promtionMaxSave = $promotion->maxSave;
            }

            $this->stageGoodsItems[$promotion->bondType] = array_merge($this->stageGoodsItems[$promotion->bondType], $promotion->goodsItemsAll);

            if(bccomp($promotion->minSave, 0, 2) > 0 && bccomp($promotion->minSave, $this->stageMinSave[$promotion->bondType], 2) > 0){
                $this->stageMinSave[$promotion->bondType] = $promotion->minSave;
            }
        }

        foreach ($this->stageOrder as $item){
            $this->stageGoodsItems[$item] = array_count_values($this->stageGoodsItems[$item]);
        }

        $this->calcResult->pauseTimer('initContext');
    }

    public function addLog($type, $content, $subFolder = null){
        if($type == 'Combination' && !$this->getConfig('outputCombination')) return;

        $this->logCache[] = [$type, $content, $subFolder, date("Y-m-d H:i:s")];

        $this->logCnt++;

        if(count($this->logCache) > ($this->logCnt<1000?100:1000))
            $this->logFlush();
    }

    public function logFlush(){
        foreach ($this->logCache as $log){
            list($type, $content, $subFolder, $time) = $log;
            $folder = "test/log/{$this->runId}_{$this->runName}";
            if(!file_exists($folder))
                mkdir($folder);

            if($subFolder){
                if(!file_exists($folder."/".$subFolder))
                    mkdir($folder."/".$subFolder);

                $folder .= "/".$subFolder;
            }

            if($type == 'result' || $this->runMode == 'debug'){
                if($type != 'result')
                    file_put_contents($folder."/{$type}.log", $time."    ", FILE_APPEND);
                file_put_contents($folder."/{$type}.log", $content, FILE_APPEND);
                file_put_contents($folder."/{$type}.log", "\n", FILE_APPEND);
            }

        }

        $this->logCache = [];
    }

    public function getPlanDefineList($planContextCacheIdxList){
        $planDefineList = [];
        for($i=0;$i<count($planContextCacheIdxList);$i++) {
            $planContextCacheIdx = $planContextCacheIdxList[$i];//$this->context->solutionData[$this->stages[$i]][$planContextCacheIdxList[$i]];
            $planDefineList[] = $this->planContextCache[$planContextCacheIdx]['planDefine'];
        }
        return $planDefineList;
    }

    public function getMemoryInfo(){
        $info = ['total'=>round(memory_get_usage()/1024/1024, 2).'MB'];

        foreach ($this as $k=>$v){
            if(is_array($v) && count($v, COUNT_RECURSIVE)>30){
                $info[$k] = count($v).", ".count($v, COUNT_RECURSIVE);
            }
        }

        foreach ($this->promotions as $promotionKey=>$promotion){
            $info[$promotionKey] = $promotion->getMemoryInfo();
        }

        return $info;
    }

//    public function getGoodsCombination($goodsItems){
//        $cacheKey = join("-", $goodsItems);
//        if(!isset($this->goodsItemCombinationCache[$cacheKey])){
//            $this->goodsItemCombinationCache[$cacheKey] = promotion_combination($goodsItems)->asArray();//$this->permutation->getCombinations($goodsItems);
//        }
//
//        return $this->goodsItemCombinationCache[$cacheKey];
//    }

    public function getGoodsSameSku($goodsItems){
        //$cacheKey = join("-", $goodsItems);
        //if(isset($this->goodsSameSkuCache[$cacheKey]))
        //    return $this->goodsSameSkuCache[$cacheKey];

        $goodsItemSameSku = [];
        foreach ($goodsItems as $idx){
            $goodsIdx = $this->goodsItemSameSkuInfo[$idx];
            $goodsItemSameSku[] = $goodsIdx != -1?$goodsIdx:$idx;
        }

        //$this->goodsSameSkuCache[$cacheKey] = $goodsItemSameSku;

        return $goodsItemSameSku;
    }

    public function getGoodsSameSkuKeyRaw($goodsItems){
        $goodsItemSameSku = $this->getGoodsSameSku($goodsItems);
        $planFlip = array_count_values($goodsItemSameSku);

        return http_build_query($planFlip);
    }

    public function getGoodsSameSkuKey($goodsItemSameSku){
        $planFlip = array_count_values($goodsItemSameSku);

        return http_build_query($planFlip);
    }

    public function getStagePromotions($stage){
        if(isset($this->stagePromotions[$stage]))
            return $this->stagePromotions[$stage];

        $this->stagePromotions[$stage] = [];

        foreach ($this->promotions as $key=>$p){
            if(count($p->goodsItemsAll) > 0 && $p->bondType == $stage && $p->isMaxSaveSatisfacted){
                $this->stagePromotions[$stage][$key] = $p;
            }
        }

        return $this->stagePromotions[$stage];
    }

    public function getStagePromotionRequired($stage){
        if(isset($this->stagePromotionRequired[$stage]))
            return $this->stagePromotionRequired[$stage];

        $this->stagePromotionRequired[$stage] = [];

        foreach ($this->getStagePromotions($stage) as $key=>$p){
            if($p->isRequiredByStrage){
                $this->stagePromotionRequired[$stage][$key] = $p;
            }
        }

        return $this->stagePromotionRequired[$stage];
    }

    public function initPromotionTracing($checkRules){
        foreach ($checkRules as $rules){
            foreach ($rules as $rule){
                foreach ($this->promotionTracing as $k=>$v){
                    $this->promotionTracing[$k][$rule->getName()] = 0;
                }
            }
        }
    }

    public function setPromotionTracing($promotionKey, $checkRule){
        if(!$this->getConfig('showPromotionTracing')) return;

        $this->promotionTracing[$promotionKey][$checkRule->getName()] = 1;
    }

    public function getConfig($name){
        if(isset($this->config[$name]))
            return $this->config[$name];

        return null;
    }
}