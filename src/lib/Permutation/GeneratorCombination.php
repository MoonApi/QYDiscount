<?php


namespace QyDiscount\lib\Permutation;


use Exception;
use Traversable;

/**
 */
class GeneratorCombination implements \IteratorAggregate
{
    private $generatorsSource = [];
    private $keys = [];
    private $itemCheckCallback = null;  //to check the generated combination
    private $nextGeneratorMinStartCallback = null;  //to skip generator when its goodsitem cnt < min start
    private $combinationGoodsItemEstimatedCallback = null;  //to skip generator when the combination of goodsitem cnt is not looks good
    private $notWorkableCacheCallback = null; //to quick whether promotion is not workable
    private $context = null;

    public function __construct(array $generatorsSource,
                                $context,
                                $itemCheckCallback=null,
                                $nextGeneratorMinStartCallback=null,
                                $combinationGoodsItemEstimatedCallback=null,
                                $notWorkableCacheCallback=null)
    {
        $this->generatorsSource = $generatorsSource;
        $this->context = $context;
        $this->keys = array_keys($generatorsSource);
        $this->itemCheckCallback = $itemCheckCallback;
        $this->nextGeneratorMinStartCallback = $nextGeneratorMinStartCallback;
        $this->combinationGoodsItemEstimatedCallback = $combinationGoodsItemEstimatedCallback;
        $this->notWorkableCacheCallback = $notWorkableCacheCallback;
    }
    /**
     * @inheritDoc
     */
    public function getIterator(): Traversable
    {
        if(count($this->generatorsSource) == 0) yield [];

        $generators = [];

        for($i=0;$i<count($this->generatorsSource);$i++){

            $estimateMinStart = $this->getEstimateMinStart($generators, $i);

            if($estimateMinStart === false){
                if($i == 0){
                    yield [];
                    return;
                }
                else{
                    $generators[$i] = promotion_combination($this->generatorsSource[$this->keys[$i]]);
                    $generators[$i]->initManualIterator();
                    if($this->context) $this->context->calcResult->increaseRunCnt('initManualIterator1');
                }
            }
            else{
                $generators[$i] = promotion_combination($this->generatorsSource[$this->keys[$i]], $estimateMinStart);
                $generators[$i]->initManualIterator();
                if($this->context) $this->context->calcResult->increaseRunCnt('initManualIterator2');
            }
        }

        $itemCheckCallback = $this->itemCheckCallback;
        $combinationGoodsItemEstimatedCallback = $this->combinationGoodsItemEstimatedCallback;
        $notWorkableCacheCallback = $this->notWorkableCacheCallback;

        $combination = [];
        for ($i = 0; $i < count($generators); $i++) {
            if(!$generators[$i]->valid()) {
                $combination = [];
                break;
            }

            $invalidRound = false;

            while (true){
                if(!$generators[$i]->valid()) break;

                $combination[$this->keys[$i]] = $generators[$i]->current();

                if($generators[$i]->isWorkable())
                    break;

                if($notWorkableCacheCallback && is_callable($notWorkableCacheCallback)){
                    if(!$notWorkableCacheCallback($this->keys[$i], $combination[$this->keys[$i]])){
                        unset($combination[$this->keys[$i]]);

                        $generators[$i]->skipCurrent();

                        $this->moveToNext($generators, $i);

                        if(!$generators[0]->valid()){
                            $invalidRound = true;
                            break;
                        }
                        continue;
                    }
                    else{
                        $generators[$i]->maskWorkable();
                        break;
                    }
                }
                else{
                    break;
                }
            }

//            print("i: ".$i.', '.json_encode($combination)."\n");
//            if(isset($combination['c_7']) && $combination['c_7'] == [2,3,4,5]){
//                print("i: ".$i."\n");
//                die('fffff');
//            }

            if($invalidRound){
                $combination = [];
                break;
            }

            if(!$generators[$i]->valid())
                $i--;

            if($i == count($generators)-1){
                if($combinationGoodsItemEstimatedCallback && is_callable($combinationGoodsItemEstimatedCallback)){
                    if(!$combinationGoodsItemEstimatedCallback($generators)){
                        $curCnt = count($generators[$i]->current());
                        if($curCnt + 1 <= count($this->generatorsSource[$this->keys[$i]])){
                            $generators[$i] = promotion_combination($this->generatorsSource[$this->keys[$i]],$curCnt+1);
                            $generators[$i]->initManualIterator();
                            if($this->context) $this->context->calcResult->increaseRunCnt('initManualIterator3');
                            $i--;
                        }
                        else if($i > 0){
                            $curCnt = count($generators[$i-1]->current());
                            $generators[$i-1] = promotion_combination($this->generatorsSource[$this->keys[$i-1]],$curCnt+1);
                            $generators[$i-1]->initManualIterator();
                            $this->resetRight($generators, $i-1);
                            if($this->context) $this->context->calcResult->increaseRunCnt('initManualIterator8');

                            if(!$generators[0]->valid()){
                                $combination = [];
                                break;
                            }

                            $i -= 2;
                            if($i<-1) $i=-1;
                        }
                        else{
                            break;
                        }

                        continue;
                    }
                }

                if($itemCheckCallback && is_callable($itemCheckCallback)) {
                    $invalidRound = false;
                    $firstInvalid = -1;

                    for ($j = 0; $j < count($combination); $j++) {
                        if(!$itemCheckCallback($this->keys[$j], $combination[$this->keys[$j]])){
                            $invalidRound = true;

                            if($firstInvalid == -1)
                                $firstInvalid = $j;

                            $generators[$j]->skipCurrent();
                            $this->moveToNext($generators, $j, false);
                        }
                    }

                    if($invalidRound){
                        //reset $firstInvalid
                        $i = $firstInvalid - 1;
                        continue;
                    }
                }

                $this->moveToNext($generators, $i);

                if(!$generators[0]->valid()){
                    break;
                }

                $i = -1;

                yield $combination;

                $combination = [];
            }
        }
        yield $combination;
    }

    private function moveToNext(&$generators, $cur, $resetAfter=true){
        for ($j = $cur; $j>=0; $j--){
            $generators[$j]->next();

            if($generators[$j]->valid() && $generators[$j]->current()){
                break;
            }
            else if($j != 0){
                if($generators[$j]->pos == 0) continue;

                $estimateMinStart = $this->getEstimateMinStart($generators, $j);

                if($estimateMinStart === false){
                    $generators[$j] = promotion_combination($this->generatorsSource[$this->keys[$j]]);
                    $generators[$j]->initManualIterator();
                    if($this->context) $this->context->calcResult->increaseRunCnt('initManualIterator4');
                    continue;
                }

                $generators[$j] = promotion_combination($this->generatorsSource[$this->keys[$j]],$estimateMinStart);
                $generators[$j]->initManualIterator();
                if($this->context) $this->context->calcResult->increaseRunCnt('initManualIterator5');

                if($resetAfter){
                    $validCombination = $this->resetRight($generators, $j);

                    if(!$validCombination){
                        continue;
                    }
                }
            }
            else{
            }
        }

        return true;
    }

    private function resetRight(&$generators, $j){
        $validCombination = true;

        for($k=$j+1;$k<count($generators);$k++){
            if($generators[$k]->pos == 0) continue;

            $estimateMinStart = $this->getEstimateMinStart($generators, $j);
            if($estimateMinStart === false){
                $validCombination = false;
                $generators[$k] = promotion_combination($this->generatorsSource[$this->keys[$k]]);
                $generators[$k]->initManualIterator();
                if($this->context) $this->context->calcResult->increaseRunCnt('initManualIterator6');
                break;
            }

            $generators[$k] = promotion_combination($this->generatorsSource[$this->keys[$k]],$estimateMinStart);
            $generators[$k]->initManualIterator();
            if($this->context) $this->context->calcResult->increaseRunCnt('initManualIterator7');
        }

        return $validCombination;
    }

    private function getEstimateMinStart(&$generators, $cur){
        $nextGeneratorMinStartCallback = $this->nextGeneratorMinStartCallback;

        $estimateMinStart = ($nextGeneratorMinStartCallback&&is_callable($nextGeneratorMinStartCallback))?$nextGeneratorMinStartCallback($generators, $this->generatorsSource, $cur):1;
        
        return $estimateMinStart;
    }
}