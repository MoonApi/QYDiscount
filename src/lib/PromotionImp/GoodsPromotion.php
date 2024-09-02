<?php


namespace QyDiscount\lib\PromotionImp;


use QyDiscount\lib\StageContext;
use QyDiscount\lib\PlanContext;

class GoodsPromotion extends \QyDiscount\lib\Promotion
{
    protected function promote()
    {
        foreach ($this->goodsItems as $key){
            $goodsItem = $this->goodsItemsContext[$key];

            $amount = $goodsItem->actualAmount;

            if($this->type == 'less'){
                $amount -= $this->value;

                if($amount <0) $amount = 0;
            }
            else if($this->type == 'off'){
                $amount = $amount * $this->value;

                if($amount <0) $amount = 0;
            }
            else{
                return  false;
            }

            if($this->setPromotedCallback)
                call_user_func($this->setPromotedCallback,$this->bondType, $key, $this, $amount);
        }

        return true;
    }
}