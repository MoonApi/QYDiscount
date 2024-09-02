<?php

namespace QyDiscount\lib;

use QyDiscount\lib\PromotionImp\BundlePromotion;
use QyDiscount\lib\PromotionImp\GoodsPromotion;
use QyDiscount\lib\PromotionImp\OrderPromotion;
use QyDiscount\lib\PromotionImp\ShopPromotion;
use QyDiscount\lib\PromotionImp\ShopsPromotion;

class PromotionFactory
{
    public static function create($promotionList){
        $promotions = [];

        foreach ($promotionList as $p){
            $p['key'] = 'c_'.$p['claim_id'];
            if($p['bond_type'] == 'order'){
                $promotions[$p['key']] = new OrderPromotion($p);
            }
            else if($p['bond_type'] == 'shop'){
                $promotions[$p['key']] = new ShopPromotion($p);
            }
            else if($p['bond_type'] == 'shops'){
                $promotions[$p['key']] = new ShopsPromotion($p);
            }
            if($p['bond_type'] == 'goods'){
                $promotions[$p['key']] = new GoodsPromotion($p);
            }
            if($p['bond_type'] == 'subtotal'){
                $promotions[$p['key']] = new BundlePromotion($p);
            }
        }

        return $promotions;
    }
}