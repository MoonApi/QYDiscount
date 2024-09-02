<?php


namespace QyDiscount\lib;


use QyDiscount\lib\Promotion;
use QyDiscount\lib\StageContext;

class PlanContext
{
    public $planDefine;
    public $goodsItems;
    public $goodsItemsContext;

    public $totalSaved = 0;
    public $workable = false;

    public $stageContext;

    public function __construct($planDefine, StageContext &$stageContext, $goodsItems){
        $this->planDefine = $planDefine;
        $this->stageContext = $stageContext;
        $this->goodsItems = $goodsItems;

        foreach ($goodsItems as $key=>$goodsItem){
            $this->goodsItemsContext[$key] = new GoodsItemContext($key, $goodsItem->amount);
        }
    }

    public function isPromoted($bondType, $goodsItemIdx){
        return in_array($bondType, $this->goodsItemsContext[$goodsItemIdx]->promotionExclusiveList);
    }

    public function setPromoted($bondType, $goodsItemIdx, Promotion $promotion, $amount){
        if($this->isPromoted($bondType, $goodsItemIdx)) return false;

        $this->totalSaved += $this->goodsItemsContext[$goodsItemIdx]->actualAmount - $amount;

        #print('plan: '.json_encode($this->planDefine).', '.$this->stageContext->goodsItems[$goodsItemIdx]->actualAmount.', '. $amount."\n");

        $this->goodsItemsContext[$goodsItemIdx]->promotionRecords[] = [
            'bond_type'=>$bondType,
            'promotion_claim_id'=>$promotion->claimId,
            'promotion_key'=>$promotion->key,
            'from'=>$this->goodsItemsContext[$goodsItemIdx]->actualAmount,
            'to'=>$amount,
            'saved'=>$this->goodsItemsContext[$goodsItemIdx]->actualAmount - $amount];

        $this->goodsItemsContext[$goodsItemIdx]->isPromoted[$bondType] = true;
        $this->goodsItemsContext[$goodsItemIdx]->actualAmount = $amount;

        $this->goodsItemsContext[$goodsItemIdx]->promotionExclusiveList = array_merge($this->goodsItemsContext[$goodsItemIdx]->promotionExclusiveList, $promotion->exclusiveList);

        return true;
    }

//    public static function estimatePlanSave($plan, Context $context){
//        $totalSaved = 0;
//        foreach ($plan as $promotionKey=>$goodsitems){
//            $cacheKeySameSku = $promotionKey.'-'.$context->getGoodsSameSkuKeyRaw($goodsitems);
//
//            //already checked whether it's existing, goodsItemsContext
//            foreach ($context->workableCache[$cacheKeySameSku] as $idx=>$goodsItem){
//                foreach ($goodsItem->promotionRecords as $promotionRecord){
//                    $totalSaved += $promotionRecord['saved'];
//                }
//            }
//        }
//
//        return $totalSaved;
//    }
}