<?php


use QyDiscount\lib\Permutation;
use PHPUnit\Framework\TestCase;
use QyDiscount\lib\Permutation\GeneratorCombination;
use function QyDiscount\lib\Permutation\cartesian_product;
use function QyDiscount\lib\Permutation\promotion_combination;

class PermutationTest extends TestCase
{

    public function testGetPermutations()
    {
        $items = ['A', 'B', 'C', 'D'];

        $permutation = new \QyDiscount\lib\Permutation();

        $result = $permutation->getPermutations($items);

        $this->assertTrue(count($result) == 64);
    }

    public function testGetPermutations2()
    {
        $items = ['p_1'=>[['1','2','3'],['a','b'],['7','8']], 'p_2'=>[['c','d'],['cc','dd']],'p_3'=>[['m','n'],['o', 'p']],'p_4'=>[['t','r'],['u', 'v']]];

        $items = [[['1','2','3'],['a','b'],['7','8']], [['c','d'],['cc','dd']],[['m','n'],['o', 'p']]];
        $permutation = new \QyDiscount\lib\Permutation();

        $result = $permutation->getPermutations($items);

        $this->assertTrue(count($result) == 64);
    }

    public function testCombinations()
    {
        $items = [[['1','2','3'],['a','b'],['7','8']], [['c','d'],['cc','dd']],[['m','n'],['o', 'p']]];

        $permutation = new \QyDiscount\lib\Permutation();

        $result = $permutation->combinations($items);

        $this->assertTrue(count($result) == 12);
    }

    public function testCombinationWithKey()
    {
        $items = ['p_1'=>[['1','2','3'],['a','b'],['7','8']], 'p_2'=>[['c','d'],['cc','dd']],'p_3'=>[['m','n'],['o', 'p']],'p_4'=>[['t','r'],['u', 'v']]];

        $permutation = new \QyDiscount\lib\Permutation();

        $result = $permutation->combinationWithKey($items);
        #print(json_encode($result));

        $this->assertTrue(count($result) == 24);
    }

    public function testCombinationWithKey2()
    {
        $items = ['p_1'=>[['1','2','3'],['a','b'],['7','8']], 'p_2'=>[['c','d'],['cc','dd']],'p_3'=>[['m','n'],['o', 'p']],'p_4'=>[['t','r'],['u', 'v']]];

        $permutation = new \QyDiscount\lib\Permutation();

        $result = $permutation->combinationWithKey($items, []);
        #print(json_encode($result));

        $this->assertTrue(count($result) == 108);
    }

    public function testCombinationWithKey3()
    {
        $items = ['p_1'=>[['1','2','3'],['a','b'],['7','8']], 'p_2'=>[['c','d'],['cc','dd']]];

        $permutation = new \QyDiscount\lib\Permutation();

        $result = $permutation->combinationWithKey($items);
        //print(json_encode($result));

        $this->assertTrue(count($result) == 6);
    }

    public function testCombinationWithKey4()
    {
        $items = ['p_1'=>[['1','2','3'],['a','b'],['7','8']]];

        $permutation = new \QyDiscount\lib\Permutation();

        $result = $permutation->combinationWithKey($items);
        //print(json_encode($result));

        $this->assertTrue(count($result) == 3);
    }

    public function testGetCombinations()
    {
        $items = ['A', 'B', 'C', 'D'];

        $permutation = new \QyDiscount\lib\Permutation();

        $result = $permutation->getCombinations($items);
        //print(json_encode($result));

        $this->assertTrue(count($result) == 15);
    }

    public function testGetCombinations2()
    {
        $items = ['A', 'B', 'C', 'D'];

        $combinations = promotion_combination($items);
        print(json_encode($combinations->asArray()));

        $this->assertTrue(count($combinations->asArray()) == 15);
    }

    public function testGetCombinations3()
    {
        $items = ["c_3","c_4","c_5","c_11","c_12","c_13","c_18","c_19","c_20","c_21","c_22","c_23"];

        $combinations = promotion_combination($items);

        $this->assertTrue(count($combinations->asArray()) == 4095);
    }

    public function testGetCombinations4()
    {
        $items = ['A', 'B', 'C', 'D'];

        $combinations = promotion_combination($items, 3);
        print(json_encode($combinations->asArray()));

        $this->assertTrue(count($combinations->asArray()) == 5);
    }

    public function testGeneratorCombination1()
    {
        $items = ["c_3"=>[0,1,2],"c_4"=>[3,4]];

        $promotionPlanGenerators = [];
        foreach ($items as $promotionKey=>$goodsItemsAll){
            $promotionPlanGenerators[$promotionKey] = $goodsItemsAll;//promotion_combination($stageContext->promotions[$promotionKey]->goodsItemsAll)->getIterator();
        }

        $GeneratorCombination = new GeneratorCombination($promotionPlanGenerators, null, function($promotionKey, $goodsitems){
            return true;
        });

        $combinations = [];

        foreach ($GeneratorCombination as $curCombination){
            if(count($curCombination) == 0)
                break;
            $combinations[] = $curCombination;
        }

        $this->assertTrue(count($combinations) == 21);
    }

    public function testGeneratorCombination2()
    {
        $items = ["c_3"=>[0,1,2],"c_4"=>[3,4]];

        $promotionPlanGenerators = [];
        foreach ($items as $promotionKey=>$goodsItemsAll){
            $promotionPlanGenerators[$promotionKey] = $goodsItemsAll;//promotion_combination($stageContext->promotions[$promotionKey]->goodsItemsAll)->getIterator();
        }

        $GeneratorCombination = new GeneratorCombination($promotionPlanGenerators, null, function($promotionKey, $goodsitems){
            return false;
        });

        $combinations = [];

        foreach ($GeneratorCombination as $curCombination){
            if(count($curCombination) == 0)
                break;
            $combinations[] = $curCombination;
        }

        $this->assertTrue(count($combinations) == 0);
    }

    public function testGeneratorCombination3()
    {
        $items = ["c_5"=>[0,1,2],"c_3"=>[3,4,5,6]];

        $promotionPlanGenerators = [];
        foreach ($items as $promotionKey=>$goodsItemsAll){
            $promotionPlanGenerators[$promotionKey] = $goodsItemsAll;//promotion_combination($stageContext->promotions[$promotionKey]->goodsItemsAll)->getIterator();
        }

        $GeneratorCombination = new GeneratorCombination($promotionPlanGenerators, null, function($promotionKey, $goodsitems){
            return true;
        }, function($generators, $cur){
            //for($i=0;$i<$cur;$i++)
            //    print('cur: '.$cur.', '.$i.', '.json_encode($generators[$i]->current()).', '."\n");
            if($cur == 0)
                return 2;
            return  1;
        });

        $combinations = [];

        foreach ($GeneratorCombination as $curCombination){
            if(count($curCombination) == 0)
                break;
            $combinations[] = $curCombination;
        }

        $this->assertTrue(count($combinations) == 60);
    }

    public function testGeneratorCombination4()
    {
        $items = ["c_5"=>[0,1,2],"c_3"=>[3,4,5,6]];

        $promotionPlanGenerators = [];
        foreach ($items as $promotionKey=>$goodsItemsAll){
            $promotionPlanGenerators[$promotionKey] = $goodsItemsAll;//promotion_combination($stageContext->promotions[$promotionKey]->goodsItemsAll)->getIterator();
        }

        $GeneratorCombination = new GeneratorCombination($promotionPlanGenerators, null, function($promotionKey, $goodsitems){
            return true;
        }, function($generators, $cur){
            return false;
        });

        $combinations = [];

        foreach ($GeneratorCombination as $curCombination){
            if(count($curCombination) == 0)
                break;
            $combinations[] = $curCombination;
        }

        print('$combinations: '.json_encode($combinations)."\n");

        $this->assertTrue(count($combinations) == 0);
    }

    public function testGeneratorCombination5()
    {
        $items = ["c_5"=>[0,1,2],"c_3"=>[3,4,5,6]];

        $promotionPlanGenerators = [];
        foreach ($items as $promotionKey=>$goodsItemsAll){
            $promotionPlanGenerators[$promotionKey] = $goodsItemsAll;//promotion_combination($stageContext->promotions[$promotionKey]->goodsItemsAll)->getIterator();
        }

        $GeneratorCombination = new GeneratorCombination($promotionPlanGenerators, null, function($promotionKey, $goodsitems){
            return true;
        }, function($generators, $cur){
            $plan = [];

            for($i=0;$i<$cur;$i++){
                $plan[$i] = $generators[$i]->current();
                if(count($plan[$i]) < 2){
                    return false;
                }
            }
            return 1;
        },function($generators){
            $plan = [];

            for($i=0;$i<count($generators);$i++){
                $plan[$i] = $generators[$i]->current();
                if(count($plan[$i]) < 3){
                    return false;
                }
            }

            return true;
        });

        $combinations = [];

        foreach ($GeneratorCombination as $curCombination){
            if(count($curCombination) == 0)
                break;
            $combinations[] = $curCombination;
        }

        $this->assertTrue(count($combinations) == 5);
    }

    public function testCombinationWithKey5()
    {
        $items = json_decode('{"goods":[{"c_1":[0]},{"c_1":[1]},{"c_1":[2]},{"c_1":[0,1]},{"c_1":[0,2]},{"c_1":[1,2]},{"c_1":[0,1,2]}],"shop":[{"c_2":[0,1]},{"c_2":[0,2]},{"c_2":[1,2]},{"c_2":[0,1,2]},{"c_3":[0]},{"c_3":[1]},{"c_3":[2]},{"c_3":[0,1]},{"c_3":[0,2]},{"c_3":[1,2]},{"c_3":[0,1,2]},{"c_2":[0,1],"c_3":[2]},{"c_2":[0,2],"c_3":[1]},{"c_2":[1,2],"c_3":[0]}]}', true);

        $permutation = new \QyDiscount\lib\Permutation();

        $result = $permutation->combinationWithKey($items);
        //print(json_encode($result));

        $this->assertTrue(count($result) == 98);
    }

    public function testGetCartesian1()
    {
        $items = json_decode('{"goods":[{"c_1":[0]},{"c_1":[1]},{"c_1":[2]},{"c_1":[0,1]},{"c_1":[0,2]},{"c_1":[1,2]},{"c_1":[0,1,2]}],"shop":[{"c_2":[0,1]},{"c_2":[0,2]},{"c_2":[1,2]},{"c_2":[0,1,2]},{"c_3":[0]},{"c_3":[1]},{"c_3":[2]},{"c_3":[0,1]},{"c_3":[0,2]},{"c_3":[1,2]},{"c_3":[0,1,2]},{"c_2":[0,1],"c_3":[2]},{"c_2":[0,2],"c_3":[1]},{"c_2":[1,2],"c_3":[0]}]}', true);

        $permutation = new \QyDiscount\lib\Permutation();

        $result = $permutation->getCartesian($items);
        print(json_encode($result));

        $this->assertTrue(count($result) == 98);
    }

    public function testGetCartesian2()
    {
        $items = json_decode('{"c_2":[[0],[1],[2],[3]], "c_3":[[0],[1]]}', true);

        $permutation = new \QyDiscount\lib\Permutation();

        $result = $permutation->getCartesian($items);
        //print(json_encode($result));

        $this->assertTrue(count($result) == 8);
    }

    public function testCartesianProduct1()
    {
        $items = json_decode('{"goods":[{"c_1":[0]},{"c_1":[1]},{"c_1":[2]},{"c_1":[0,1]},{"c_1":[0,2]},{"c_1":[1,2]},{"c_1":[0,1,2]}],"shop":[{"c_2":[0,1]},{"c_2":[0,2]},{"c_2":[1,2]},{"c_2":[0,1,2]},{"c_3":[0]},{"c_3":[1]},{"c_3":[2]},{"c_3":[0,1]},{"c_3":[0,2]},{"c_3":[1,2]},{"c_3":[0,1,2]},{"c_2":[0,1],"c_3":[2]},{"c_2":[0,2],"c_3":[1]},{"c_2":[1,2],"c_3":[0]}]}', true);

        $result = cartesian_product($items)->asArray();

        $this->assertTrue(count($result) == 98);
    }

    public function testCartesianProduct2()
    {
        $items = json_decode('{"c_2":[[0],[1],[2],[3]], "c_3":[[0],[1]]}', true);

        $result = cartesian_product($items)->asArray();

        print(json_encode($result)."\n");

        $this->assertTrue(count($result) == 8);
    }

    public function testCartesianProduct3()
    {
        $content = file_get_contents('test/data/testCartesianProduct3.log');
        $items = json_decode($content, true);

//        $cache = [];
//        foreach (cartesian_product($items) as $combination) {
//            if(count($cache) > 100){
//                file_put_contents('test/log/b.log', json_encode($cache)."\n", FILE_APPEND);
//                $cache = [];
//            }
//            $cache [] = $combination;
//        }
//        if(count($cache) > 0){
//            file_put_contents('test/log/b.log', json_encode($cache)."\n", FILE_APPEND);
//            $cache = [];
//        }

        $cnt = 0;
        foreach (cartesian_product($items) as $combination) {
            $cnt++;
        }

        $this->assertTrue($cnt == 4190209);
    }

    public function testCartesianProduct4()
    {
        $content = file_get_contents('test/data/testCartesianProduct4.log');
        $items = json_decode($content, true);

        $cnt = count(cartesian_product($items));

        $this->assertTrue($cnt == 8577357823);
    }

    public function testCartesianProduct5()
    {
        $items = json_decode('{"goods":[{"c_1":[0]},{"c_1":[1]}], "shops":[{"c_3":[1]}]}', true);

        $result = cartesian_product($items)->asArray();

        print(json_encode($result)."\n");

        $this->assertTrue(count($result) == 2);
    }

    public function testCartesianProduct6()
    {
        $items = json_decode('{"shop":[0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16], "order":[15,16]}', true);

        $result = cartesian_product($items)->asArray();

        print(json_encode($result)."\n");

        $this->assertTrue(count($result) == 34);
    }

    public function testCartesianProduct7()
    {
        $items = ['A', 'B', 'C', 'D'];

        $result = cartesian_product($items)->asArray();

        print(json_encode($result)."\n");

        $this->assertTrue(count($result) == 34);
    }
}
