<?php


use QyDiscount\lib\Context;
use QyDiscount\lib\GoodsItem;
use QyDiscount\lib\PromotionFactory;

class SpeedTest extends \PHPUnit\Framework\TestCase
{
    private function getData($n){
        $carts = [
            ['cart_id'=>1,'name'=>'商品A','app'=>'QYShop','module'=>'goods','row_id'=>1,'spu'=>['shop_id'=>1],'category_ids'=>[1],'price'=>235,'cnt'=>2,'attrs'=>[]],
            ['cart_id'=>2,'name'=>'商品B','app'=>'QYShop','module'=>'goods','row_id'=>2,'spu'=>['shop_id'=>1],'category_ids'=>[1],'price'=>218,'cnt'=>1,'attrs'=>[]],
            ['cart_id'=>3,'name'=>'商品C','app'=>'QYShop','module'=>'goods','row_id'=>3,'spu'=>['shop_id'=>2],'category_ids'=>[1],'price'=>799,'cnt'=>1,'attrs'=>[]],
            ['cart_id'=>4,'name'=>'商品D','app'=>'QYShop','module'=>'goods','row_id'=>4,'spu'=>['shop_id'=>2],'category_ids'=>[1],'price'=>559,'cnt'=>2,'attrs'=>[]],
            ['cart_id'=>5,'name'=>'商品E','app'=>'QYShop','module'=>'goods','row_id'=>5,'spu'=>['shop_id'=>2],'category_ids'=>[1],'price'=>479,'cnt'=>1,'attrs'=>[]],
            ['cart_id'=>6,'name'=>'商品F','app'=>'QYShop','module'=>'goods','row_id'=>6,'spu'=>['shop_id'=>3],'category_ids'=>[1],'price'=>100,'cnt'=>1,'attrs'=>[]],
            ['cart_id'=>7,'name'=>'商品G','app'=>'QYShop','module'=>'goods','row_id'=>7,'spu'=>['shop_id'=>3],'category_ids'=>[1],'price'=>200,'cnt'=>1,'attrs'=>[]],
            ['cart_id'=>8,'name'=>'商品H','app'=>'QYShop','module'=>'goods','row_id'=>8,'spu'=>['shop_id'=>3],'category_ids'=>[1],'price'=>300,'cnt'=>1,'attrs'=>[]],
        ];

        $cnt = $n;

        for($i=0;$i<$cnt;$i++){
            for($j=0;$j<3;$j++){
                $cartItem = json_decode(json_encode($carts[5+$i*3+$j]), true);
                $cartItem['cart_id'] = 8+$i*3+$j;
                $cartItem['name'] = $cartItem['name'].$i;
                $cartItem['spu']['shop_id'] = 4+$i;
                $cartItem['row_id'] = $cartItem['cart_id'];
                $carts[] =  $cartItem;
            }
        }

        $goodsItems = GoodsItem::cartToItems($carts);

        $promotionList = [
            ['id'=>3,'claim_id'=>3,'name'=>'满100减5元','app'=>'QYShop','module'=>'YXTool','row_id'=>1,'bond_type'=>'shop','activity'=>['shop_id'=>1],'type'=>'less_when', 'type_when'=>'amount', 'amount_when'=>100, 'value'=>5, 'exclusive_list'=>['shop'], 'dtls'=>[['app'=>'QYShop','module'=>'goods','row_id'=>1], ['app'=>'QYShop','module'=>'goods','row_id'=>2]]],
            ['id'=>4,'claim_id'=>4,'name'=>'满299减10元','app'=>'QYShop','module'=>'YXTool','row_id'=>1,'bond_type'=>'shop','activity'=>['shop_id'=>1],'type'=>'less_when', 'type_when'=>'amount', 'amount_when'=>299, 'value'=>10, 'exclusive_list'=>['shop'], 'dtls'=>[['app'=>'QYShop','module'=>'goods','row_id'=>1], ['app'=>'QYShop','module'=>'goods','row_id'=>2]]],
            ['id'=>5,'claim_id'=>5,'name'=>'满499减20元','app'=>'QYShop','module'=>'YXTool','row_id'=>1,'bond_type'=>'shop','activity'=>['shop_id'=>1],'type'=>'less_when', 'type_when'=>'amount', 'amount_when'=>499, 'value'=>20, 'exclusive_list'=>['shop'], 'dtls'=>[['app'=>'QYShop','module'=>'goods','row_id'=>1], ['app'=>'QYShop','module'=>'goods','row_id'=>2]]],
            ['id'=>6,'claim_id'=>6,'name'=>'满999减50元','app'=>'QYShop','module'=>'YXTool','row_id'=>1,'bond_type'=>'shop','activity'=>['shop_id'=>1],'type'=>'less_when', 'type_when'=>'amount', 'amount_when'=>999, 'value'=>50, 'exclusive_list'=>['shop'], 'dtls'=>[['app'=>'QYShop','module'=>'goods','row_id'=>1], ['app'=>'QYShop','module'=>'goods','row_id'=>2]]],
            ['id'=>7,'claim_id'=>7,'name'=>'每300减30','app'=>'QYShop','module'=>'YXTool','row_id'=>1,'bond_type'=>'shops','type'=>'less_loop', 'type_when'=>'amount', 'amount_when'=>300, 'value'=>30, 'exclusive_list'=>[], 'dtls'=>[['app'=>'QYShop','module'=>'goods','row_id'=>1], ['app'=>'QYShop','module'=>'goods','row_id'=>2], ['app'=>'QYShop','module'=>'goods','row_id'=>3], ['app'=>'QYShop','module'=>'goods','row_id'=>4]]],
            ['id'=>8,'claim_id'=>8,'name'=>'满300减10服饰券','app'=>'QYShop','module'=>'YXTool','row_id'=>1,'bond_type'=>'order','type'=>'less_when', 'type_when'=>'amount', 'amount_when'=>300, 'value'=>10, 'exclusive_list'=>[], 'dtls'=>[['app'=>'QYShop','module'=>'goods','row_id'=>1], ['app'=>'QYShop','module'=>'goods','row_id'=>3], ['app'=>'QYShop','module'=>'goods','row_id'=>4]]],
            ['id'=>11,'claim_id'=>11,'name'=>'满21减20元','app'=>'QYShop','module'=>'YXTool','row_id'=>1,'bond_type'=>'shop','activity'=>['shop_id'=>2],'type'=>'less_when', 'type_when'=>'amount', 'amount_when'=>21, 'value'=>0, 'exclusive_list'=>['shop'], 'dtls'=>[['app'=>'QYShop','module'=>'goods','row_id'=>3], ['app'=>'QYShop','module'=>'goods','row_id'=>4], ['app'=>'QYShop','module'=>'goods','row_id'=>5]]],
            ['id'=>12,'claim_id'=>12,'name'=>'满1000减50元','app'=>'QYShop','module'=>'YXTool','row_id'=>1,'bond_type'=>'shop','activity'=>['shop_id'=>2],'type'=>'less_when', 'type_when'=>'amount', 'amount_when'=>1000, 'value'=>50, 'exclusive_list'=>['shop'], 'dtls'=>[['app'=>'QYShop','module'=>'goods','row_id'=>3], ['app'=>'QYShop','module'=>'goods','row_id'=>4], ['app'=>'QYShop','module'=>'goods','row_id'=>5]]],
            ['id'=>13,'claim_id'=>13,'name'=>'满2000减100元','app'=>'QYShop','module'=>'YXTool','row_id'=>1,'bond_type'=>'shop','activity'=>['shop_id'=>2],'type'=>'less_when', 'type_when'=>'amount', 'amount_when'=>2000, 'value'=>100, 'exclusive_list'=>['shop'], 'dtls'=>[['app'=>'QYShop','module'=>'goods','row_id'=>3], ['app'=>'QYShop','module'=>'goods','row_id'=>4], ['app'=>'QYShop','module'=>'goods','row_id'=>5]]],
            ['id'=>14,'claim_id'=>14,'name'=>'满3000减150元','app'=>'QYShop','module'=>'YXTool','row_id'=>1,'bond_type'=>'shop','activity'=>['shop_id'=>2],'type'=>'less_when', 'type_when'=>'amount', 'amount_when'=>3000, 'value'=>150, 'exclusive_list'=>['shop'], 'dtls'=>[['app'=>'QYShop','module'=>'goods','row_id'=>3], ['app'=>'QYShop','module'=>'goods','row_id'=>4], ['app'=>'QYShop','module'=>'goods','row_id'=>5]]],
            ['id'=>15,'claim_id'=>15,'name'=>'满5000减350元','app'=>'QYShop','module'=>'YXTool','row_id'=>1,'bond_type'=>'shop','activity'=>['shop_id'=>2],'type'=>'less_when', 'type_when'=>'amount', 'amount_when'=>5000, 'value'=>350, 'exclusive_list'=>['shop'], 'dtls'=>[['app'=>'QYShop','module'=>'goods','row_id'=>3], ['app'=>'QYShop','module'=>'goods','row_id'=>4], ['app'=>'QYShop','module'=>'goods','row_id'=>5]]],
            ['id'=>16,'claim_id'=>16,'name'=>'每600减110','app'=>'QYShop','module'=>'YXTool','row_id'=>1,'bond_type'=>'shops','type'=>'less_loop', 'type_when'=>'amount', 'amount_when'=>600, 'value'=>110, 'exclusive_list'=>[], 'dtls'=>[['app'=>'QYShop','module'=>'goods','row_id'=>3]]],
            ['id'=>10,'claim_id'=>10,'name'=>'满300减60元','app'=>'QYShop','module'=>'YXTool','row_id'=>1,'bond_type'=>'subtotal','type'=>'less_when', 'type_when'=>'amount', 'amount_when'=>300, 'value'=>60, 'exclusive_list'=>[], 'dtls'=>[['app'=>'QYShop','module'=>'goods','row_id'=>4]]],
            ['id'=>17,'claim_id'=>17,'name'=>'满300减30元','app'=>'QYShop','module'=>'YXTool','row_id'=>1,'bond_type'=>'subtotal','type'=>'less_when', 'type_when'=>'amount', 'amount_when'=>300, 'value'=>30, 'exclusive_list'=>[], 'dtls'=>[['app'=>'QYShop','module'=>'goods','row_id'=>5]]],
            ['id'=>18,'claim_id'=>18,'name'=>'满300减50元','app'=>'QYShop','module'=>'YXTool','row_id'=>1,'bond_type'=>'shop','type'=>'less_when', 'type_when'=>'amount', 'amount_when'=>300, 'value'=>50, 'exclusive_list'=>[], 'dtls'=>[['app'=>'QYShop','module'=>'goods','row_id'=>6], ['app'=>'QYShop','module'=>'goods','row_id'=>7]]],
        ];

        for($i=0;$i<$cnt;$i++){
            $promotion = json_decode(json_encode($promotionList[14]), true);
            $promotion['id'] = 19+$i;
            $promotion['claim_id'] = $promotion['id'];
            $promotion['dtls'][0]['row_id'] = 8+$i*3;
            $promotion['dtls'][1]['row_id'] = 8+$i*3+1;
            $promotionList[] = $promotion;
        }

        $promotions = PromotionFactory::create($promotionList);

        return [$goodsItems, $promotions];
    }

    public function testSpeed1(){
        list($goodsItems, $promotions) = $this->getData(8);

        $context = new Context($goodsItems, $promotions, 'debug', __FUNCTION__);

        $goodsItemKeys = array_keys($goodsItems);
        for($i=0;$i<100000;$i++){
            $context->getGoodsSameSkuKeyRaw($goodsItemKeys);
        }

        $this->assertTrue(true);
    }
}