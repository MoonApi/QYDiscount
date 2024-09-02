<?php


namespace QyDiscount\lib;


class GoodsItem
{
    public $idx;
    public $app;
    public $module;
    public $rowId;
    public $cartId;
    public $categoryIds;
    public $amount;
    public $name;
    public $attrs;
    public $spu;

    /*public $isPromoted = [];
    public $promotionRecords = [];
    public $promotionExclusiveList = [];

    public $actualAmount = 0;*/

    public function __construct($idx, $cart) {
        $this->idx = $idx;
        $this->app = $cart['app'];
        $this->module = $cart['module'];
        $this->rowId = $cart['row_id'];
        $this->cartId = $cart['cart_id'];
        $this->categoryIds = $cart['category_ids'];
        $this->amount = $cart['price'];
        $this->name = $cart['name'];
        $this->attrs = $cart['attrs'];
        $this->spu = $cart['spu'];
    }

    public static function cartToItems($carts){
        $goodsItems = [];

        $idx = 0;

        foreach ($carts as $cart){
            for($i=0;$i<$cart['cnt'];$i++){
                $index = $idx++;
                $goodsItems[$index] = new GoodsItem($index, $cart);
            }
        }

        return $goodsItems;
    }
}