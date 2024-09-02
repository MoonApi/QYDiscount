<?php


namespace QyDiscount\lib;


class CalcResult
{
    public $finalPrice;
    public ?SolutionContext $finalSolution = null;
    public $finalSolutionSame = [];
    public $solutionContextList = [];
    public $solutionData = [];
    public $solutions = [];

    public $curPrice;
    public $status = false;
    public $planCalculatorTotalCnt = 0;
    public $solutionCalculatorTotalCnt = 0;
    public $planCalculatorValidCnt = 0;
    public $solutionCalculatorValidCnt = 0;

    public $checkRuleCnt = [];
    public $runCnt = [];    //for debug

    public Context $context;
    public $lastLogTime = 0;

    public $timer = [];

    public $startTime = 0;

    public function __construct(&$context) {
        $this->context = $context;
        $this->status = false;
        $this->startTime = date("Y-m-d H:i:s");
    }

    public function init($curPrice, ){
        $this->curPrice = $curPrice;
        $this->finalPrice = $curPrice;
    }

    public function addLog($code, $title, $content, $data=''){
        if($this->lastLogTime == 0) $this->lastLogTime = time();
        $logData = ['code'=>$code, 'title'=>$title, 'content'=>$content, 'time'=>time(), 'timepast'=>time()-$this->lastLogTime];

        $this->context->addLog("calcResult", json_encode($logData, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
        if($data)
            $this->context->addLog($title, $data, "data");
    }

    public function getInfo(){
        $finalSolutionSame = [];

        $solutionData = [];
        $solutions = [];

        $finalSolution = $this->getSolution($this->finalSolution);

        foreach($this->finalSolutionSame as $solutionContext){
            $finalSolutionSame[] = $this->getSolution($solutionContext);
        }

        foreach ($this->solutionData as $key=>$planContextCacheIdxList){
            $solutionData[$key] = $this->context->getPlanDefineList($planContextCacheIdxList);
        }

        foreach ($this->solutions as $solution){
            $solutions[] = $this->context->getPlanDefineList($solution);
        }

        $timerInfo = [];
        foreach ($this->timer as $key=>$value){
            $tmp = [];
            foreach ($value as $k=>$v){
                if(!in_array($k, ['start', 'elapsed', 'end'])){
                    $tmp[$k] = $k=='total'?round($v, 3):$v;
                }
            }
            $timerInfo[$key]=$tmp;
        }

        $info = [
            'name'=>$this->context->runName,
            'start_time'=>$this->startTime,
            'end_time'=>date("Y-m-d H:i:s"),
            'curPrice'=>$this->curPrice,
            'finalPrice'=>$this->finalPrice,
            'goodsItemCnt'=>count($this->context->goodsItems),
            'promotionCnt'=>count($this->context->promotions),
            'status'=>$this->status,
            'planCalculatorTotalCnt'=>$this->planCalculatorTotalCnt,
            'planCalculatorValidCnt'=>$this->planCalculatorValidCnt,
            'solutionCalculatorTotalCnt'=>$this->solutionCalculatorTotalCnt,
            'solutionCalculatorValidCnt'=>$this->solutionCalculatorValidCnt,
            'memoryInfo'=>$this->context->getMemoryInfo(),
            'finalSolution'=>$finalSolution,
            'finalSolutionSame'=>$finalSolutionSame,
            'solutionData'=>$solutionData,
            'solutions'=>$solutions,
            'solutionContextList'=>[]];

        if($this->context->getConfig('timer')){
            $extralInfo = [];
            $extralInfo['timer'] = $timerInfo;
            $extralInfo['checkRuleCnt'] = $this->checkRuleCnt;
            $extralInfo['runCnt'] = $this->runCnt;

            $info = array_merge(array_splice($info, 0, 12), $extralInfo, $info);
        }

        if($this->context->getConfig('showPromotionTracing'))
            $info['promotionTracing'] = $this->context->promotionTracing;

        foreach ($this->solutionContextList as $item){
            $planDefineList = $this->context->getPlanDefineList($item->solution);
            $info['solutionContextList'][] = ['solution'=>$item->solution, 'planDefineList'=>$planDefineList, 'amount'=>$item->amount, 'actualAmount'=>$item->actualAmount, 'totalSaved'=>$item->totalSaved, 'workable'=>$item->workable];
        }
        return $info;
    }

    private function getSolution($solutionContext){
        if(!$solutionContext)
            return [];
        $planDefineList = $this->context->getPlanDefineList($solutionContext->solution);

        $orderSaved = 0;
        $goodsInfo = [];
        $cartInfo = [];

        foreach ($solutionContext->goodsItems as $goodsItem){
            if(!isset($cartInfo[$goodsItem->spu['shop_id']]))
                $cartInfo[$goodsItem->spu['shop_id']] = ['shop_id'=>$goodsItem->spu['shop_id'], 'saved'=>0, 'goodsitems'=>[]];

            $skuKey = $goodsItem->app."_".$goodsItem->module."_".$goodsItem->rowId;
            if(!isset($cartInfo[$goodsItem->spu['shop_id']]['goodsitems'][$skuKey])){
                $cartInfo[$goodsItem->spu['shop_id']]['goodsitems'][$skuKey] = [
                    'cartId'=>$goodsItem->cartId,
                    'app'=>$goodsItem->app,
                    'module'=>$goodsItem->module,
                    'rowid'=>$goodsItem->rowId,
                    'name'=>$goodsItem->name,
                    'cnt'=>1,
                    'amount'=>$goodsItem->amount,
                    'actualAmount'=>$solutionContext->goodsItemsContext[$goodsItem->idx]->actualAmount
                ];

                $saved = $goodsItem->amount - $solutionContext->goodsItemsContext[$goodsItem->idx]->actualAmount;
                $cartInfo[$goodsItem->spu['shop_id']]['saved'] += $saved;
                $orderSaved += $saved;
            }
            else{
                $cartInfo[$goodsItem->spu['shop_id']]['goodsitems'][$skuKey]['cnt'] += 1;

                $saved = $goodsItem->amount - $solutionContext->goodsItemsContext[$goodsItem->idx]->actualAmount;
                $cartInfo[$goodsItem->spu['shop_id']]['saved'] += $saved;
                $orderSaved += $saved;
            }

            $promotionRecord = $solutionContext->goodsItemsContext[$goodsItem->idx]->promotionRecords;

            foreach ($promotionRecord as $k=>$v){
                $promotionRecord[$k]['from'] = round($promotionRecord[$k]['from'], 2);
                $promotionRecord[$k]['to'] = round($promotionRecord[$k]['to'], 2);
                $promotionRecord[$k]['saved'] = round($promotionRecord[$k]['saved'], 2);
            }


            $goodsInfo[] = [
                'idx'=>$goodsItem->idx,
                'app'=>$goodsItem->app,
                'module'=>$goodsItem->module,
                'rowid'=>$goodsItem->rowId,
                'name'=>$goodsItem->name,
                'amount'=>$goodsItem->amount,
                'actualAmount'=>$solutionContext->goodsItemsContext[$goodsItem->idx]->actualAmount,
                'promotionRecord'=>$promotionRecord
            ];
        }

        foreach ($cartInfo as $k=>$item){
            $cartInfo[$k]['goodsitems'] = array_values($item['goodsitems']);
            $cartInfo[$k]['saved'] = round($item['saved'], 2);
        }

        return ['planDefineList'=>$planDefineList,
            'amount'=>$solutionContext->amount,
            'actualAmount'=>$solutionContext->actualAmount,
            'totalSaved'=>$solutionContext->totalSaved,
            'workable'=>$solutionContext->workable,
            'promotionSaved'=>$solutionContext->promotionSaved,
            'orderSaved'=>round($orderSaved, 2),
            'cartInfo'=>$cartInfo,
            'goodsInfo'=>$goodsInfo];
    }

    public function startTimer($name, $statusList=[]){
        if(!$this->context->getConfig('timer')) return;
        if(!isset($this->timer[$name])) {
            $this->timer[$name] = ['start'=>0, 'elapsed'=>0, 'end'=>0, 'total'=>0, 'cnt'=>0];
            foreach ($statusList as $s){
                $this->timer[$name]['cnt_'.$s] = 0;
            }
        }

        $this->timer[$name]['start'] = microtime(true);
        $this->timer[$name]['elapsed'] = 0;
        $this->timer[$name]['end'] = 0;
    }

    public function pauseTimer($name, $status=null){
        if(!$this->context->getConfig('timer')) return;
        $this->timer[$name]['end'] = microtime(true);
        $elapsed = $this->timer[$name]['end'] - $this->timer[$name]['start'];
        $this->timer[$name]['elapsed'] = $elapsed;
        $this->timer[$name]['total'] += $this->timer[$name]['elapsed'];

        $this->timer[$name]['cnt']++;
        if($status){
            $this->timer[$name]['cnt_'.$status]++;
        }
    }

    public function initCheckRuleCounter($checkRules){
        foreach ($checkRules as $rules){
            foreach ($rules as $rule){
                $this->checkRuleCnt[$rule->getName()] = ['in'=>0, 'out'=>0, 'blocked'=>0];
            }
        }
    }

    public function increaseCheckRuleCounter($name, $in, $out){
        $this->checkRuleCnt[$name]['in'] += $in;
        $this->checkRuleCnt[$name]['out'] += $out;
        $this->checkRuleCnt[$name]['blocked'] += $in - $out;
    }

    public function increaseRunCnt($name, $cnt=1){
        if(!isset($this->runCnt[$name]))
            $this->runCnt[$name] = 0;

        $this->runCnt[$name] += $cnt;
    }
}