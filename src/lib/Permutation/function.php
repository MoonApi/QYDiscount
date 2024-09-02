<?php
namespace QyDiscount\lib\Permutation;

function cartesian_product(array $set){
    return new \QyDiscount\lib\Permutation\CartesianProduct($set);
}

function promotion_combination(array $set, int $min = 1){
    return new \QyDiscount\lib\Permutation\PromotionCombination($set, $min);
}