<?php
use PHPUnit\Framework\TestCase;
use QyDiscount\Discount;
use QyDiscount\lib\GoodsItem;
use QyDiscount\lib\PromotionFactory;

class DiscountTest extends TestCase
{
    public function testDiscount1(){
        $discount = new Discount();

        $carts = [
            ['cart_id'=>1,'name'=>'商品A','app'=>'QYShop','module'=>'goods','row_id'=>1,'spu'=>['shop_id'=>1],'category_ids'=>[1],'price'=>120,'cnt'=>1,'attrs'=>[]],
            ['cart_id'=>2,'name'=>'商品B','app'=>'QYShop','module'=>'goods','row_id'=>2,'spu'=>['shop_id'=>1],'category_ids'=>[1],'price'=>180,'cnt'=>1,'attrs'=>[]],
        ];

        $goodsItems = GoodsItem::cartToItems($carts);

        $promotionList = [
            ['id'=>1,'claim_id'=>1,'name'=>'跨店满300减30','app'=>'QYShop','module'=>'goods','row_id'=>1,'bond_type'=>'order','type'=>'less_when', 'type_when'=>'amount', 'amount_when'=>300, 'value'=>30],
            ['id'=>2,'claim_id'=>2,'name'=>'满100减10','app'=>'QYShop','module'=>'YXTool','row_id'=>1,'bond_type'=>'shop','type'=>'less_when', 'type_when'=>'amount', 'amount_when'=>100, 'value'=>10, 'exclusive_list'=>['shop'], 'dtls'=>[['app'=>'QYShop','module'=>'goods','row_id'=>1],['app'=>'QYShop','module'=>'goods','row_id'=>2]]],
            ['id'=>3,'claim_id'=>3,'name'=>'满300减40','app'=>'QYShop','module'=>'YXTool','row_id'=>1,'bond_type'=>'shop','type'=>'less_when', 'type_when'=>'amount', 'amount_when'=>300, 'value'=>40, 'exclusive_list'=>['shop'], 'dtls'=>[['app'=>'QYShop','module'=>'goods','row_id'=>1],['app'=>'QYShop','module'=>'goods','row_id'=>2]]]
        ];

        $promotions = PromotionFactory::create($promotionList);

        $calcResult = $discount->calc($goodsItems, $promotions, 'debug', __FUNCTION__);

        $this->assertTrue($calcResult->finalPrice == 260);
    }

    public function testDiscount2(){
        $discount = new Discount();

        $carts = [
            ['cart_id'=>1,'name'=>'商品A','app'=>'QYShop','module'=>'goods','row_id'=>1,'spu'=>['shop_id'=>1],'category_ids'=>[1],'price'=>130,'cnt'=>1,'attrs'=>[]],
            ['cart_id'=>2,'name'=>'商品B','app'=>'QYShop','module'=>'goods','row_id'=>2,'spu'=>['shop_id'=>1],'category_ids'=>[1],'price'=>120,'cnt'=>1,'attrs'=>[]],
            ['cart_id'=>3,'name'=>'商品C','app'=>'QYShop','module'=>'goods','row_id'=>3,'spu'=>['shop_id'=>1],'category_ids'=>[1],'price'=>110,'cnt'=>1,'attrs'=>[]],
        ];

        $goodsItems = GoodsItem::cartToItems($carts);

        $promotionList = [
            ['id'=>2,'claim_id'=>2,'name'=>'满2件7折','app'=>'QYShop','module'=>'YXTool','row_id'=>1,'bond_type'=>'shop','type'=>'off_when', 'type_when'=>'cnt', 'cnt_when'=>2, 'value'=>7, 'exclusive_list'=>['shop'], 'dtls'=>[['app'=>'QYShop','module'=>'goods','row_id'=>1],['app'=>'QYShop','module'=>'goods','row_id'=>2]]],
            ['id'=>3,'claim_id'=>3,'name'=>'满100减40','app'=>'QYShop','module'=>'YXTool','row_id'=>1,'bond_type'=>'shop','type'=>'less_when', 'type_when'=>'amount', 'amount_when'=>100, 'value'=>40, 'exclusive_list'=>['shop'], 'dtls'=>[['app'=>'QYShop','module'=>'goods','row_id'=>1],['app'=>'QYShop','module'=>'goods','row_id'=>2]]]
        ];

        $promotions = PromotionFactory::create($promotionList);

        $calcResult = $discount->calc($goodsItems, $promotions, 'debug', __FUNCTION__);

        $this->assertTrue($calcResult->finalPrice == (120+130)*0.7+110);
    }

    public function testDiscount3(){
        $discount = new Discount();

        $carts = [
            ['cart_id'=>1,'name'=>'商品A','app'=>'QYShop','module'=>'goods','row_id'=>1,'spu'=>['shop_id'=>1],'category_ids'=>[1],'price'=>130,'cnt'=>1,'attrs'=>[]],
            ['cart_id'=>2,'name'=>'商品B','app'=>'QYShop','module'=>'goods','row_id'=>2,'spu'=>['shop_id'=>1],'category_ids'=>[1],'price'=>120,'cnt'=>1,'attrs'=>[]],
            ['cart_id'=>3,'name'=>'商品C','app'=>'QYShop','module'=>'goods','row_id'=>3,'spu'=>['shop_id'=>1],'category_ids'=>[1],'price'=>110,'cnt'=>1,'attrs'=>[]],
        ];

        $goodsItems = GoodsItem::cartToItems($carts);

        $promotionList = [
            ['id'=>2,'claim_id'=>2,'name'=>'满2件7折','app'=>'QYShop','module'=>'YXTool','row_id'=>1,'bond_type'=>'shop','type'=>'off_when', 'type_when'=>'cnt', 'cnt_when'=>2, 'value'=>7, 'exclusive_list'=>['shop'], 'dtls'=>[['app'=>'QYShop','module'=>'goods','row_id'=>1],['app'=>'QYShop','module'=>'goods','row_id'=>2],['app'=>'QYShop','module'=>'goods','row_id'=>3]]],
            ['id'=>3,'claim_id'=>3,'name'=>'满100减40','app'=>'QYShop','module'=>'YXTool','row_id'=>1,'bond_type'=>'shop','type'=>'less_when', 'type_when'=>'amount', 'amount_when'=>100, 'value'=>40, 'exclusive_list'=>['shop'], 'dtls'=>[['app'=>'QYShop','module'=>'goods','row_id'=>1],['app'=>'QYShop','module'=>'goods','row_id'=>2],['app'=>'QYShop','module'=>'goods','row_id'=>3]]]
        ];

        $promotions = PromotionFactory::create($promotionList);

        $calcResult = $discount->calc($goodsItems, $promotions, 'debug', __FUNCTION__);

        $this->assertTrue($calcResult->finalPrice == (130+120)*0.7+(110-40));
    }

    public function testDiscount4(){
        $discount = new Discount();

        $carts = [
            ['cart_id'=>1,'name'=>'商品A','app'=>'QYShop','module'=>'goods','row_id'=>1,'spu'=>['shop_id'=>1],'category_ids'=>[1],'price'=>130,'cnt'=>1,'attrs'=>[]],
            ['cart_id'=>2,'name'=>'商品B','app'=>'QYShop','module'=>'goods','row_id'=>2,'spu'=>['shop_id'=>1],'category_ids'=>[1],'price'=>120,'cnt'=>1,'attrs'=>[]],
            ['cart_id'=>3,'name'=>'商品C','app'=>'QYShop','module'=>'goods','row_id'=>3,'spu'=>['shop_id'=>1],'category_ids'=>[1],'price'=>110,'cnt'=>1,'attrs'=>[]],
        ];

        $goodsItems = GoodsItem::cartToItems($carts);

        $promotionList = [
            ['id'=>1,'claim_id'=>1,'name'=>'立减10元','app'=>'QYShop','module'=>'YXTool','row_id'=>1,'bond_type'=>'goods','type'=>'less', 'type_when'=>'amount', 'value'=>10, 'exclusive_list'=>['goods'], 'dtls'=>[['app'=>'QYShop','module'=>'goods','row_id'=>1],['app'=>'QYShop','module'=>'goods','row_id'=>2]]],
            ['id'=>2,'claim_id'=>2,'name'=>'满2件7折','app'=>'QYShop','module'=>'YXTool','row_id'=>1,'bond_type'=>'shop','type'=>'off_when', 'type_when'=>'cnt', 'cnt_when'=>2, 'value'=>7, 'exclusive_list'=>['shop'], 'dtls'=>[['app'=>'QYShop','module'=>'goods','row_id'=>1],['app'=>'QYShop','module'=>'goods','row_id'=>2],['app'=>'QYShop','module'=>'goods','row_id'=>3]]],
            ['id'=>3,'claim_id'=>3,'name'=>'满100减40','app'=>'QYShop','module'=>'YXTool','row_id'=>1,'bond_type'=>'shop','type'=>'less_when', 'type_when'=>'amount', 'amount_when'=>100, 'value'=>40, 'exclusive_list'=>['shop'], 'dtls'=>[['app'=>'QYShop','module'=>'goods','row_id'=>1],['app'=>'QYShop','module'=>'goods','row_id'=>2],['app'=>'QYShop','module'=>'goods','row_id'=>3]]],
        ];

        $promotions = PromotionFactory::create($promotionList);

        $calcResult = $discount->calc($goodsItems, $promotions, 'debug', __FUNCTION__);

        //print(json_encode($calcResult->getInfo())."\n");

        $this->assertTrue($calcResult->finalPrice == (130+120)*0.7+(110-40)-10-10);
    }

    public function testDiscount5(){
        $discount = new Discount();

        //订单中含有同一家店铺A、B、C三件商品，售价均为69元，且均包邮。
        //商品A无法使用任何优惠券，商品B可以使用满60减3元的商品券，商品C可以使用满50减3元的商品券。
        //那么，商品B、C满足满减条件分别优惠3元，最终店铺订单实付金额为201元。


        $carts = [
            ['cart_id'=>1,'name'=>'商品A','app'=>'QYShop','module'=>'goods','row_id'=>1,'spu'=>['shop_id'=>1],'category_ids'=>[1],'price'=>69,'cnt'=>1,'attrs'=>[]],
            ['cart_id'=>2,'name'=>'商品B','app'=>'QYShop','module'=>'goods','row_id'=>2,'spu'=>['shop_id'=>1],'category_ids'=>[1],'price'=>69,'cnt'=>1,'attrs'=>[]],
            ['cart_id'=>3,'name'=>'商品C','app'=>'QYShop','module'=>'goods','row_id'=>3,'spu'=>['shop_id'=>1],'category_ids'=>[1],'price'=>69,'cnt'=>1,'attrs'=>[]],
        ];

        $goodsItems = GoodsItem::cartToItems($carts);

        $promotionList = [
            ['id'=>2,'claim_id'=>2,'name'=>'满60减3元','app'=>'QYShop','module'=>'YXTool','row_id'=>1,'bond_type'=>'shop','type'=>'less_when', 'type_when'=>'amount', 'amount_when'=>60, 'value'=>3, 'exclusive_list'=>['shop'], 'dtls'=>[['app'=>'QYShop','module'=>'goods','row_id'=>2]]],
            ['id'=>3,'claim_id'=>3,'name'=>'满50减3元','app'=>'QYShop','module'=>'YXTool','row_id'=>1,'bond_type'=>'shop','type'=>'less_when', 'type_when'=>'amount', 'amount_when'=>50, 'value'=>3, 'exclusive_list'=>['shop'], 'dtls'=>[['app'=>'QYShop','module'=>'goods','row_id'=>3]]]
        ];

        $promotions = PromotionFactory::create($promotionList);

        $calcResult = $discount->calc($goodsItems, $promotions, 'debug', __FUNCTION__);

        $this->assertTrue($calcResult->finalPrice == 201);
    }

    public function testDiscount6(){
        $discount = new Discount();

        //订单中含有同一家店铺A、B两件商品，且均包邮。
        //商品A售价115元（数量2）、商品B售价299元。
        //商品A或者商品B单独满足一定的金额均可使用店铺优惠券：满199减10元、满499减30元、满699减50元。
        //订单金额为：115 * 2 + 299 = 529 元，满足满499减30元店铺优惠券，可优惠30元。
        //套用上述的分摊格式：实付金额 = 230 – （230/529）*30 + 299 – （299/529）*30 = 216.96 + 282.04 = 499.00 元。

        $carts = [
            ['cart_id'=>1,'name'=>'商品A','app'=>'QYShop','module'=>'goods','row_id'=>1,'spu'=>['shop_id'=>1],'category_ids'=>[1],'price'=>115,'cnt'=>2,'attrs'=>[]],
            ['cart_id'=>2,'name'=>'商品B','app'=>'QYShop','module'=>'goods','row_id'=>2,'spu'=>['shop_id'=>1],'category_ids'=>[1],'price'=>299,'cnt'=>1,'attrs'=>[]],
        ];

        $goodsItems = GoodsItem::cartToItems($carts);

        $promotionList = [
            ['id'=>2,'claim_id'=>2,'name'=>'满199减10元','app'=>'QYShop','module'=>'YXTool','row_id'=>1,'bond_type'=>'shop','type'=>'less_when', 'type_when'=>'amount', 'amount_when'=>199, 'value'=>10, 'exclusive_list'=>['shop'], 'dtls'=>[['app'=>'QYShop','module'=>'goods','row_id'=>1], ['app'=>'QYShop','module'=>'goods','row_id'=>2]]],
            ['id'=>3,'claim_id'=>3,'name'=>'满499减30元','app'=>'QYShop','module'=>'YXTool','row_id'=>1,'bond_type'=>'shop','type'=>'less_when', 'type_when'=>'amount', 'amount_when'=>499, 'value'=>30, 'exclusive_list'=>['shop'], 'dtls'=>[['app'=>'QYShop','module'=>'goods','row_id'=>1], ['app'=>'QYShop','module'=>'goods','row_id'=>2]],
            ['id'=>4,'claim_id'=>4,'name'=>'满699减50元','app'=>'QYShop','module'=>'YXTool','row_id'=>1,'bond_type'=>'shop','type'=>'less_when', 'type_when'=>'amount', 'amount_when'=>699, 'value'=>50, 'exclusive_list'=>['shop'], 'dtls'=>[['app'=>'QYShop','module'=>'goods','row_id'=>1], ['app'=>'QYShop','module'=>'goods','row_id'=>2]]]]
        ];

        $promotions = PromotionFactory::create($promotionList);

        $calcResult = $discount->calc($goodsItems, $promotions, 'debug', __FUNCTION__);

        $this->assertTrue($calcResult->finalPrice == 499);
    }

    public function testDiscount7(){
        $discount = new Discount();

        //商品A售价559元、商品B售价600元、商品C售价198元、商品D售价1600元；
        //商品A、B、C、D均可使用满21减20元、满1000减50元、满2000减100元、满3000减15元0、满5000减350元的店铺优惠券；
        //商品A参加了满300减60元的店铺满减活动、每300减30的跨店活动、拥有一张满300减10服饰券（平台券）；
        //商品B、D参加了满600减30、满1500减130、满2000减200的店铺阶梯满减活动。
        //最终商品A、B、C、D加上各自的优惠金额，分别能优惠118.90、74.84、6.70、199.56；那么商品A、B、C、D实付金额分别为440.10元、525.16元、191.30元、1400.44元。

        $carts = [
            ['cart_id'=>1,'name'=>'商品A','app'=>'QYShop','module'=>'goods','row_id'=>1,'spu'=>['shop_id'=>1],'category_ids'=>[1],'price'=>559,'cnt'=>1,'attrs'=>[]],
            ['cart_id'=>2,'name'=>'商品B','app'=>'QYShop','module'=>'goods','row_id'=>2,'spu'=>['shop_id'=>1],'category_ids'=>[1],'price'=>600,'cnt'=>1,'attrs'=>[]],
            ['cart_id'=>3,'name'=>'商品C','app'=>'QYShop','module'=>'goods','row_id'=>3,'spu'=>['shop_id'=>1],'category_ids'=>[1],'price'=>198,'cnt'=>1,'attrs'=>[]],
            ['cart_id'=>4,'name'=>'商品D','app'=>'QYShop','module'=>'goods','row_id'=>4,'spu'=>['shop_id'=>1],'category_ids'=>[1],'price'=>1600,'cnt'=>1,'attrs'=>[]],
        ];

        $goodsItems = GoodsItem::cartToItems($carts);

        $promotionList = [
            ['id'=>2,'claim_id'=>2,'name'=>'满21减20元','app'=>'QYShop','module'=>'YXTool','row_id'=>1,'bond_type'=>'shop','type'=>'less_when', 'type_when'=>'amount', 'amount_when'=>21, 'value'=>0, 'exclusive_list'=>['shop'], 'dtls'=>[['app'=>'QYShop','module'=>'goods','row_id'=>1], ['app'=>'QYShop','module'=>'goods','row_id'=>2], ['app'=>'QYShop','module'=>'goods','row_id'=>3], ['app'=>'QYShop','module'=>'goods','row_id'=>4]]],
            ['id'=>3,'claim_id'=>3,'name'=>'满1000减50元','app'=>'QYShop','module'=>'YXTool','row_id'=>1,'bond_type'=>'shop','type'=>'less_when', 'type_when'=>'amount', 'amount_when'=>1000, 'value'=>50, 'exclusive_list'=>['shop'], 'dtls'=>[['app'=>'QYShop','module'=>'goods','row_id'=>1], ['app'=>'QYShop','module'=>'goods','row_id'=>2], ['app'=>'QYShop','module'=>'goods','row_id'=>3], ['app'=>'QYShop','module'=>'goods','row_id'=>4]]],
            ['id'=>4,'claim_id'=>4,'name'=>'满2000减100元','app'=>'QYShop','module'=>'YXTool','row_id'=>1,'bond_type'=>'shop','type'=>'less_when', 'type_when'=>'amount', 'amount_when'=>2000, 'value'=>100, 'exclusive_list'=>['shop'], 'dtls'=>[['app'=>'QYShop','module'=>'goods','row_id'=>1], ['app'=>'QYShop','module'=>'goods','row_id'=>2], ['app'=>'QYShop','module'=>'goods','row_id'=>3], ['app'=>'QYShop','module'=>'goods','row_id'=>4]]],
            ['id'=>5,'claim_id'=>5,'name'=>'满3000减150元','app'=>'QYShop','module'=>'YXTool','row_id'=>1,'bond_type'=>'shop','type'=>'less_when', 'type_when'=>'amount', 'amount_when'=>3000, 'value'=>150, 'exclusive_list'=>['shop'], 'dtls'=>[['app'=>'QYShop','module'=>'goods','row_id'=>1], ['app'=>'QYShop','module'=>'goods','row_id'=>2], ['app'=>'QYShop','module'=>'goods','row_id'=>3], ['app'=>'QYShop','module'=>'goods','row_id'=>4]]],
            ['id'=>6,'claim_id'=>6,'name'=>'满5000减350元','app'=>'QYShop','module'=>'YXTool','row_id'=>1,'bond_type'=>'shop','type'=>'less_when', 'type_when'=>'amount', 'amount_when'=>5000, 'value'=>350, 'exclusive_list'=>['shop'], 'dtls'=>[['app'=>'QYShop','module'=>'goods','row_id'=>1], ['app'=>'QYShop','module'=>'goods','row_id'=>2], ['app'=>'QYShop','module'=>'goods','row_id'=>3], ['app'=>'QYShop','module'=>'goods','row_id'=>4]]],
            ['id'=>10,'claim_id'=>10,'name'=>'满300减60元','app'=>'QYShop','module'=>'YXTool','row_id'=>1,'bond_type'=>'subtotal','type'=>'less_when', 'type_when'=>'amount', 'amount_when'=>300, 'value'=>60, 'exclusive_list'=>[], 'dtls'=>[['app'=>'QYShop','module'=>'goods','row_id'=>1]]],
            ['id'=>7,'claim_id'=>7,'name'=>'每300减30','app'=>'QYShop','module'=>'YXTool','row_id'=>1,'bond_type'=>'shops','type'=>'less_loop', 'type_when'=>'amount', 'amount_when'=>300, 'value'=>30, 'exclusive_list'=>[], 'dtls'=>[['app'=>'QYShop','module'=>'goods','row_id'=>1]]],
            ['id'=>8,'claim_id'=>8,'name'=>'满300减10服饰券','app'=>'QYShop','module'=>'YXTool','row_id'=>1,'bond_type'=>'order','type'=>'less_when', 'type_when'=>'amount', 'amount_when'=>300, 'value'=>10, 'exclusive_list'=>[], 'dtls'=>[['app'=>'QYShop','module'=>'goods','row_id'=>1]]],
            ['id'=>9,'claim_id'=>9,'name'=>'阶梯满减','app'=>'QYShop','module'=>'YXTool','row_id'=>1,'bond_type'=>'subtotal','type'=>'less_ladder', 'type_when'=>'amount', 'exclusive_list'=>[], 'ladder_rule'=>[['threshold'=>300, 'value'=>30], ['threshold'=>1500, 'value'=>130], ['threshold'=>2000, 'value'=>200]], 'dtls'=>[['app'=>'QYShop','module'=>'goods','row_id'=>2], ['app'=>'QYShop','module'=>'goods','row_id'=>4]]]
        ];

        $promotions = PromotionFactory::create($promotionList);

        $calcResult = $discount->calc($goodsItems, $promotions, 'debug', __FUNCTION__);

        $this->assertTrue($calcResult->finalPrice == 440.10+525.16+191.30+1400.44);
    }

    public function testDiscount8(){
        $discount = new Discount();

        //店铺甲：
        //商品A售价235元(数量2)、商品B售价218元；
        //商品A、商品B都可使用满100减5元、满299减10元、满499减20元、满999减50元店铺优惠券，同时都参加了跨店每满300减30的活动；
        //商品A还可以使用一张满300减10元的服饰券(平台券)；
        //店铺乙：
        //商品C售价799元、商品D售价559元(数量2)、商品E售价479元；
        //商品C、D、E均可使用满21减20、满1000减50、满2000减100、满3000减150、满5000减350的店铺优惠券；
        //商品C可以使用一张满300减10元的服饰券(平台券)、参加每满600减110元的店铺活动；
        //商品D可以使用一张满300减10元的服饰券(平台券)、参加满300减60元的店铺活动；
        //商品E参加满300减30元的店铺活动；
        //商品C、D同时都参加了跨店每满300减30的活动；

        $carts = [
            ['cart_id'=>1,'name'=>'商品A','app'=>'QYShop','module'=>'goods','row_id'=>1,'spu'=>['shop_id'=>1],'category_ids'=>[1],'price'=>235,'cnt'=>2,'attrs'=>[]],
            ['cart_id'=>2,'name'=>'商品B','app'=>'QYShop','module'=>'goods','row_id'=>2,'spu'=>['shop_id'=>1],'category_ids'=>[1],'price'=>218,'cnt'=>1,'attrs'=>[]],
            ['cart_id'=>3,'name'=>'商品C','app'=>'QYShop','module'=>'goods','row_id'=>3,'spu'=>['shop_id'=>2],'category_ids'=>[1],'price'=>799,'cnt'=>1,'attrs'=>[]],
            ['cart_id'=>4,'name'=>'商品D','app'=>'QYShop','module'=>'goods','row_id'=>4,'spu'=>['shop_id'=>2],'category_ids'=>[1],'price'=>559,'cnt'=>2,'attrs'=>[]],
            ['cart_id'=>5,'name'=>'商品E','app'=>'QYShop','module'=>'goods','row_id'=>5,'spu'=>['shop_id'=>2],'category_ids'=>[1],'price'=>479,'cnt'=>1,'attrs'=>[]],
        ];

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
        ];

        $promotions = PromotionFactory::create($promotionList);

        $calcResult = $discount->calc($goodsItems, $promotions, 'debug', __FUNCTION__);

        $this->assertTrue($calcResult->finalPrice == 2514);//205.52*2+191.58+578.69+429.01+451.82*2
    }

    public function testDiscount9(){
        $discount = new Discount();

        //店铺甲：
        //商品A售价235元(数量2)、商品B售价218元；
        //商品A、商品B都可使用满100减5元、满299减10元、满499减20元、满999减50元店铺优惠券，同时都参加了跨店每满300减30的活动；
        //商品A还可以使用一张满300减10元的服饰券(平台券)；
        //店铺乙：
        //商品C售价799元、商品D售价559元(数量2)、商品E售价479元；
        //商品C、D、E均可使用满21减20、满1000减50、满2000减100、满3000减150、满5000减350的店铺优惠券；
        //商品C可以使用一张满300减10元的服饰券(平台券)、参加每满600减110元的店铺活动；
        //商品D可以使用一张满300减10元的服饰券(平台券)、参加满300减60元的店铺活动；
        //商品E参加满300减30元的店铺活动；
        //商品C、D同时都参加了跨店每满300减30的活动；

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

        $cnt = 30;

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

        $calcResult = $discount->calc($goodsItems, $promotions, 'debug', __FUNCTION__);

        $this->assertTrue($calcResult->finalPrice == 2514+550*($cnt+1));//205.52*2+191.58+578.69+429.01+451.82*2
    }

    public function testDiscountA1(){
        $discount = new Discount();


        $carts = [
            ['cart_id'=>1,'name'=>'商品A','app'=>'QYShop','module'=>'goods','row_id'=>1,'spu'=>['shop_id'=>1],'category_ids'=>[1],'price'=>235,'cnt'=>1,'attrs'=>[]],
        ];

        $cnt = 18;

        for($i=0;$i<$cnt;$i++){
            $cartItem = json_decode(json_encode($carts[0]), true);
            $cartItem['cart_id'] = $i+2;
            $cartItem['name'] = $cartItem['name'].$cartItem['cart_id'];
            $cartItem['spu']['shop_id'] = 1;
            $cartItem['row_id'] = $cartItem['cart_id'];
            $carts[] =  $cartItem;
        }

        $goodsItems = GoodsItem::cartToItems($carts);

        $promotionList = [
            ['id'=>1,'claim_id'=>7,'name'=>'每300减30','app'=>'QYShop','module'=>'YXTool','row_id'=>1,'bond_type'=>'shop','type'=>'less_loop', 'type_when'=>'amount', 'amount_when'=>300, 'value'=>30, 'exclusive_list'=>['shop'], 'dtls'=>[['app'=>'QYShop','module'=>'goods','row_id'=>1]]],
            ['id'=>2,'claim_id'=>8,'name'=>'每300减31','app'=>'QYShop','module'=>'YXTool','row_id'=>2,'bond_type'=>'shop','type'=>'less_loop', 'type_when'=>'amount', 'amount_when'=>300, 'value'=>31, 'exclusive_list'=>['shop'], 'dtls'=>[['app'=>'QYShop','module'=>'goods','row_id'=>1]]],
        ];

        for($i=0;$i<$cnt;$i++){
            $promotionList[0]['dtls'][] = ['app'=>'QYShop','module'=>'goods','row_id'=>$i+2];
            $promotionList[1]['dtls'][] = ['app'=>'QYShop','module'=>'goods','row_id'=>$i+2];
        }

        $promotions = PromotionFactory::create($promotionList);

        $calcResult = $discount->calc($goodsItems, $promotions, 'debug', __FUNCTION__);

        $this->assertTrue($calcResult->finalPrice == 235*($cnt+1)-floor(235*($cnt+1)/300)*31);//205.52*2+191.58+578.69+429.01+451.82*2
    }
}