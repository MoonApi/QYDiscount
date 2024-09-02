<?php


namespace QyDiscount\lib;


use QyDiscount\lib\Permutation\GeneratorCombination;
use QyDiscount\lib\PlanContext;
use function QyDiscount\lib\Permutation\promotion_combination;

/**
 * Class Promotion
 * @package QyDiscount\lib
 */
Abstract class Promotion
{
    public $key;
    public $id;
    public $claimId;
    public $app;
    public $module;
    public $rowId;
    public $name;
    public $rules;
    public $bondType;
    public $exclusiveList;
    public $dtls;
    public $type;
    public $typeWhen;
    public $amountWhen;
    public $cntWhen;
    public $value;
    public $ladderRule;

    /** @var GoodsItem[] $goodsItemsAll */
    public array $goodsItemsAll = [];
    public array $goodsItemsAllCnt = [];

    /** @var GoodsItem[] $goodsItems */
    public array $goodsItems = [];

    public $goodsAmount = 0;
    public $savedAmount = 0;

    public $missCode = 0;
    public $message;

    public $maxSave = 0;    //with all goodsitems, for solution check
    public $minSave = 0;    //remove all exclusive goodsitems, for plan check
    public $isMaxSaveSatisfacted = false;
    public $localSave = 0;

    public Context $context;
    public $setPromotedCallback = null;
    public array $goodsItemsContext;
    public $otherStageExclusiveGoodsItems = [];
    public $promotionFullFilledCache = [];

    public $isRequiredByStrage = false;

    public $goodsCntMaxSave = [];

    public function __construct($promotion) {
        $this->key = $promotion['key'];
        $this->id = $promotion['id'];
        $this->claimId = $promotion['claim_id'];
        $this->app = $promotion['app'];
        $this->module = $promotion['module'];
        $this->rowId = $promotion['row_id'];
        $this->bondType = $promotion['bond_type'];
        $this->rules = $promotion['rules']??[];
        $this->name = $promotion['name'];
        $this->exclusiveList = $promotion['exclusive_list']??[];
        $this->type = $promotion['type'];   //less, off, less_when, off_when, less_loop, less_ladder, fixed_price, top_n
        $this->typeWhen = $promotion['type_when'];
        $this->amountWhen = $promotion['amount_when']??'';
        $this->cntWhen = $promotion['cnt_when']??null;
        $this->value = $promotion['value']??null;
        $this->ladderRule = $promotion['ladder_rule']??null;

        if(!$this->exclusiveList)
            $this->exclusiveList = [];

        if($this->ladderRule && count($this->ladderRule) > 0){
            $sort = array_column($this->ladderRule,'threshold');
            array_multisort($sort,SORT_DESC,$this->ladderRule);
        }

        $this->dtls = $promotion['dtls']??[];
    }

    public function setGoodsItems($goodsItems){
        $this->goodsItems = $goodsItems;
        $this->message = '';
        $this->missCode = 0;
        $this->goodsAmount = 0;
    }

    public function init(Context &$context){
        $this->context = $context;

        if($this->bondType == 'goods' || $this->bondType == 'shop' || $this->bondType == 'shops' || $this->bondType == 'order' || $this->bondType == 'subtotal'){
            $goodsItems = [];

            foreach ($context->goodsItems as $g){
                foreach($this->dtls as $detail){
                    if($detail['app'] == $g->app && $detail['module'] == $g->module && $detail['row_id'] == $g->rowId){
                        $goodsItems[] = $g->idx;
                        break;
                    }
                }
            }

            $this->goodsItemsAll = $goodsItems;
            $this->goodsItemsAllCnt = array_count_values($goodsItems);
        }
    }

    public function preAnalyse($promotions){
        usort($this->goodsItemsAll, function($a, $b){
            if($this->context->goodsItems[$a]->amount > $this->context->goodsItems[$b]->amount)
                return -1;
            else if($this->context->goodsItems[$a]->amount < $this->context->goodsItems[$b]->amount)
                return 1;
            else
                return 0;
        });

        $this->otherStageExclusiveGoodsItems = [];

        for($i=0;$i<=count($this->goodsItemsAll);$i++){
            $this->goodsCntMaxSave[$i] = ['amount'=>0, 'goodsitems'=>[]];
        }

        foreach ($this->goodsItemsAll as $idx){
            foreach ($this->context->goodsPromotionExclusiveList[$idx] as $key=>$item){
                if($promotions[$key]->bondType != $this->bondType && in_array($this->bondType, $promotions[$key]->exclusiveList)){
                    $this->otherStageExclusiveGoodsItems[] = $idx;
                    break;
                }
            }
        }

        //max save
        $this->isMaxSaveSatisfacted = false;
        $this->goodsItemsContext = [];
        foreach ($this->context->goodsItems as $key=>$goodsItem){
            $this->goodsItemsContext[$key] = new GoodsItemContext($key, $goodsItem->amount);
        }
        $this->setPromotedCallback = array($this, 'localMaxSaveSetPromoted');
        $this->setGoodsItems($this->goodsItemsAll);

        if($this->hasMeet()){
            $this->promote();

            if(bccomp($this->maxSave, 0, 2) > 0)
                $this->isMaxSaveSatisfacted = true;
        }

        //min save
        $minSaveGoodsItems = [];
        foreach ($this->goodsItemsAll as $idx){
            if(!in_array($idx, $this->otherStageExclusiveGoodsItems)){
                $minSaveGoodsItems[] = $idx;
            }
        }
        if(count($minSaveGoodsItems) == count($this->goodsItemsAll)){
            $this->minSave = $this->maxSave;
        }
        else if(count($minSaveGoodsItems) == 0){
            $this->minSave = 0;
        }
        else{
            $this->goodsItemsContext = [];
            foreach ($this->context->goodsItems as $key=>$goodsItem){
                if(!in_array($key, $minSaveGoodsItems)) continue;

                $this->goodsItemsContext[$key] = new GoodsItemContext($key, $goodsItem->amount);
            }

            $this->setPromotedCallback = array($this, 'localMinSaveSetPromoted');

            if(($this->typeWhen == 'cnt' || $this->typeWhen == 'amount') && in_array($this->type, ['less_when', 'off_when']) ){
                $minSaveGoodsItems = [];
                $sortedMinSaveGoodsItems = array_values($this->goodsItemsContext);
                $sort = array_column($sortedMinSaveGoodsItems,'actualAmount');
                array_multisort($sort,SORT_ASC,$sortedMinSaveGoodsItems);

                if($this->typeWhen == 'cnt' && $this->cntWhen > 0){
                    $minSaveGoodsItems = array_column(array_slice($sortedMinSaveGoodsItems, 0, $this->cntWhen), 'idx');
                }
                else if($this->typeWhen == 'amount' && bccomp($this->amountWhen, 0, 2) > 0){
                    $amount = 0;
                    foreach ($sortedMinSaveGoodsItems as $item){
                        if(bccomp($item->actualAmount + $amount, $this->amountWhen, 0) < 0 || count($minSaveGoodsItems) == 0){
                            $amount += $item->actualAmount;
                            $minSaveGoodsItems[] = $item->idx;
                        }
                    }
                }

            }

            $this->setGoodsItems($minSaveGoodsItems);

            if($this->hasMeet()){
                $this->isMinSaveSatisfacted = true;

                $this->promote();
            }
        }

        //combination
        $this->setPromotedCallback = array($this, 'localSetPromoted');
        //$generator = $this->context->getGoodsCombination($this->goodsItemsAll);
        $goodsItemCnt = 1;
        while ($goodsItemCnt <= count($this->goodsItemsAll)){
            $generator = promotion_combination($this->goodsItemsAll, $goodsItemCnt);

            foreach ($generator as $combination){
                $this->context->calcResult->increaseRunCnt('promotion-combination');

                $combinationCnt = count($combination);
                if($combinationCnt > $goodsItemCnt)
                    break;

                $this->localSave = 0;
                $this->goodsItemsContext = [];
                foreach ($this->context->goodsItems as $key=>$goodsItem){
                    $this->goodsItemsContext[$key] = new GoodsItemContext($key, $goodsItem->amount);
                }

                $cacheKey = $this->key.'-'.$this->context->getGoodsSameSkuKeyRaw($combination);
                $this->setGoodsItems($combination);

                if($this->hasMeet()){
                    $this->promote();

                    if(bccomp($this->goodsCntMaxSave[$combinationCnt]['amount'], $this->localSave, 2) < 0){
                        $this->goodsCntMaxSave[$combinationCnt]['amount'] = $this->localSave;
                        //$this->goodsCntMaxSave[$combinationCnt]['goodsitems'] = [$combination];
                    }
                    else if(bccomp($this->goodsCntMaxSave[$combinationCnt]['amount'], $this->localSave, 2) == 0){
                        //$this->goodsCntMaxSave[$combinationCnt]['goodsitems'][] = [];
                    }

                    //$this->context->workableCache[$cacheKey] = $this->goodsItemsContext;

                    $goodsItemSameSku = $this->context->getGoodsSameSku($combination);
                    $planFlip = array_count_values($goodsItemSameSku);

                    //for PostArrangeCheckRule to re-arrange same full filled plan to contains goodsitem as more as possible
                    foreach ($this->promotionFullFilledCache as $fullFilledItem){
                        if(empty(array_diff_key($fullFilledItem, $planFlip))){
                            $foundDiff = false;
                            foreach ($fullFilledItem as $k=>$v){
                                if($v != $planFlip[$k]){
                                    $foundDiff = true;
                                    break;
                                }
                            }
                            if(!$foundDiff){
                                $planStr = $this->key.'-'.join("-", array_keys($fullFilledItem));
                                if(!isset($this->context->sameFullFilledPlan[$planStr]))
                                    $this->context->sameFullFilledPlan[$planStr] = [];

                                if(!isset($this->context->sameFullFilledPlanKey[$planStr."-".join('-', $combination)])){
                                    $this->context->sameFullFilledPlan[$planStr][] = $combination;
                                    $this->context->sameFullFilledPlanKey[$planStr."-".join('-', $combination)] = 0;
                                }

                                if(!isset($this->context->sameFullFilledPlanL2SMap[$this->key])) $this->context->sameFullFilledPlanL2SMap[$this->key] = [];
                                $this->context->sameFullFilledPlanL2SMap[$this->key][$cacheKey] = $planStr;
                            }
                        }
                    }

                    if($this->isFullFilled()){
                        $this->promotionFullFilledCache[$cacheKey] = $planFlip;
                    }

                    break;
                }
            }
            $goodsItemCnt++;
        }


        //is not exclusive, should apply
        if($this->isMaxSaveSatisfacted){
            $isExclusive = false;
            foreach ($this->goodsItemsAll as $p){
                if(count($this->context->goodsPromotionExclusiveList[$p]) > 0){
                    foreach ($this->context->goodsPromotionExclusiveList[$p] as $key=>$promotionList){
                        if($this->context->promotions[$key]->bondType != $this->bondType){
                            if(in_array($this->key, $promotionList)){
                                $isExclusive = true;
                                break;
                            }
                        }
                    }
                }
                if($isExclusive)
                    break;
            }
            if(!$isExclusive){
                $foundSharedGoodsItem = false;
                foreach ($this->goodsItemsAll as $p){
                    foreach ($this->context->promotions as $promotionKey=>$promotion){
                        if($promotionKey == $this->key) continue;

                        if(in_array($p, $promotion->goodsItemsAll)){
                            $foundSharedGoodsItem = true;
                            break;
                        }
                    }
                    if($foundSharedGoodsItem)
                        break;
                }
                if(!$foundSharedGoodsItem)
                    $this->isRequiredByStrage = true;
            }
        }

        $this->setGoodsItems([]);
    }

    public function localMaxSaveSetPromoted($bondType, $goodsItemIdx, Promotion $promotion, $amount){
        $this->maxSave += $this->goodsItemsContext[$goodsItemIdx]->actualAmount - $amount;
        $this->goodsItemsContext[$goodsItemIdx]->actualAmount = $amount;
    }

    public function localMinSaveSetPromoted($bondType, $goodsItemIdx, Promotion $promotion, $amount){
        if($this->type == "less_loop")
            $this->minSave = $this->goodsItemsContext[$goodsItemIdx]->actualAmount - $amount;
        else
            $this->minSave += $this->goodsItemsContext[$goodsItemIdx]->actualAmount - $amount;

        $this->goodsItemsContext[$goodsItemIdx]->actualAmount = $amount;
    }

    public function localSetPromoted($bondType, $goodsItemIdx, Promotion $promotion, $amount){
        $this->localSave += $this->goodsItemsContext[$goodsItemIdx]->actualAmount - $amount;

        $this->goodsItemsContext[$goodsItemIdx]->promotionRecords[] = [
            'bond_type'=>$bondType,
            'promotion_claim_id'=>$promotion->claimId,
            'promotion_key'=>$promotion->key,
            'from'=>$this->goodsItemsContext[$goodsItemIdx]->actualAmount,
            'to'=>$amount,
            'saved'=>$this->goodsItemsContext[$goodsItemIdx]->actualAmount - $amount];

        $this->goodsItemsContext[$goodsItemIdx]->actualAmount = $amount;
    }

    public function apply(PlanContext &$planContext){
        if(!$this->isMaxSaveSatisfacted)
            return false;

        $this->goodsItemsContext = $planContext->goodsItemsContext;
        $this->setPromotedCallback = array($planContext, 'setPromoted');

        //已被其它优惠并与当前类型冲突
        foreach ($this->goodsItems as $key=>$g){
            if($planContext->isPromoted($this->bondType, $g)){
                $this->message = 'isPromoted '.$g. ', '.$this->bondType;
                return false;
            }
        }

        if(!$this->hasMeet()){
            $planContext->workable = false;
            return false;
        }

        $this->promote();

        return true;
    }

    protected function getGoodsAmount(){
        if($this->goodsAmount) return $this->goodsAmount;
        $totalAmount = 0;
        foreach ($this->goodsItems as $key){
            $goodsItem = $this->context->goodsItems[$key];

            $totalAmount += $goodsItem->amount;
        }

        return $totalAmount;
    }

    public function isFullFilled(){
        if($this->bondType == "goods")
            return false;

        if($this->type == 'less' || $this->type == 'off'){
            return true;
        }
        else if($this->type == 'less_when' || $this->type == 'less_loop' || $this->type == 'off_when'){
            if($this->typeWhen == 'cnt' && in_array($this->type, ['less_when', 'off_when']) && $this->cntWhen && $this->cntWhen <= count($this->goodsItems)){
                return true;
            }
            else if($this->typeWhen == 'amount' && in_array($this->type, ['less_when', 'off_when']) && $this->amountWhen && $this->amountWhen <= $this->getGoodsAmount()){
                return true;
            }
            else if($this->type == 'less_loop'){
                $rule = $this->getRule("amount");
                if($rule && $rule['amount_to'] <= $this->getGoodsAmount()){
                    return true;
                }
            }
        }
        else if($this->type == 'less_ladder'){
            if(count($this->ladderRule) == 0){
                return false;
            }

            if($this->typeWhen == 'cnt' && $this->ladderRule[0]['threshold'] <= count($this->goodsItems)){
                return true;
            }
            else if($this->typeWhen == 'amount' && bccomp($this->ladderRule[0]['threshold'], $this->getGoodsAmount(), 2) <=0){
                return true;
            }
        }

        return false;
    }

    protected function getRule($ruleType){
        if(!is_array($this->rules)) return null;

        foreach ($this->rules as $rule){
            if($rule['type'] == $ruleType){
                return $rule;
            }
        }
        return null;
    }



    public function hasMeet()
    {
        if($this->type == 'less' || $this->type == 'off'){
            return true;
        }

        if($this->type == 'less_when' || $this->type == 'less_loop' || $this->type == 'off_when'){
            if($this->typeWhen == 'cnt' && (!$this->cntWhen || $this->cntWhen > count($this->goodsItems))){
                $this->message = 'cnt miss: '. $this->cntWhen .'>'. count($this->goodsItems).', '.$this->typeWhen;
                $this->missCode = 1;
                return false;
            }
            else if($this->typeWhen == 'amount' && (!$this->amountWhen || bccomp($this->amountWhen, $this->getGoodsAmount(), 2) == 1)){
                $this->message = 'amount miss';
                $this->missCode = 2;
                return  false;
            }
        }
        else if($this->type == 'less_ladder'){
            if(count($this->ladderRule) == 0){
                $this->message = 'ladderRule empty';
                $this->missCode = 3;
                return false;
            }

            if($this->typeWhen == 'cnt' && $this->ladderRule[count($this->ladderRule)-1]['threshold'] > count($this->goodsItems)){
                $this->message = 'ladder cnt miss';
                $this->missCode = 4;
                return false;
            }
            else if($this->typeWhen == 'amount' && bccomp($this->ladderRule[count($this->ladderRule)-1]['threshold'], $this->getGoodsAmount(), 2) == 1){
                $this->message = 'ladder amount miss';
                $this->missCode = 5;
                return false;
            }
        }

        return  true;
    }

    protected function promote()
    {
        $totalAmount = $this->getGoodsAmount();

        $promotionValue = $this->value;

        if($this->type == 'less_loop'){
            $rule = $this->getRule("amount");
            $maxAmount = $totalAmount;
            if($rule && $rule['amount_to'] <= $totalAmount){
                $maxAmount = $rule['amount_to'];
            }

            $promotionValue = floor($maxAmount / $this->amountWhen) * $this->value;
        }
        else if($this->type == 'less_ladder'){
            $promotionValue = 0;

            foreach ($this->ladderRule as $r){
                if(bccomp($r['threshold'], $totalAmount, 2) <=0){
                    $promotionValue = $r['value'];
                    break;
                }
            }
        }

        $promotionValueBalance = $promotionValue;

        $keys = array_keys($this->goodsItems);
        //假设此时商品与优惠券已进行最优匹配，那么当前所有商品平等享受优惠
        for ($i=0; $i < count($keys); $i++){
            $key = $this->goodsItems[$keys[$i]];
            $goodsItem = $this->goodsItemsContext[$key];

            $amount = $goodsItem->actualAmount;

            if($this->type == 'less' || $this->type == 'less_when' || $this->type == 'less_loop' || $this->type == 'less_ladder'){
                if($i != count($keys)-1)
                    $curSplit = round($amount/$totalAmount * $promotionValue,2);
                else
                    $curSplit = $promotionValueBalance;

                $amount -= $curSplit;

                $promotionValueBalance -= $curSplit;

                if($amount <0) $amount = 0;
            }
            else if($this->type == 'off' || $this->type == 'off_when'){
                $amount = $amount * $this->value / 10;
            }
            else{
                $this->message = 'type invalid';
                return  false;
            }

            if($this->setPromotedCallback){
                call_user_func($this->setPromotedCallback,$this->bondType, $key, $this, $amount);
            }
        }

        return true;
    }

    public function getMemoryInfo(){
        $info = [];
        foreach ($this as $k=>$v){
            if(is_array($v) && count($v, COUNT_RECURSIVE)>30){
                $info[$k] = count($v).", ".count($v, COUNT_RECURSIVE);
            }
        }
        return $info;
    }
}

