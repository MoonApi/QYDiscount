<?php

namespace QyDiscount\lib\Permutation;

use Countable;
use Generator;
use IteratorAggregate;
use Traversable;

class PromotionCombination implements IteratorAggregate
{
    public $set;
    public $min;

    public $pos = -1;   //without invalid
    public $offset = -1;    //actual pos
    public $currentWorkableOffset = -1;
    public $iterator;

    public function __construct(array $set, int $min = 1)
    {
        $this->set = $set;
        $this->min = $min;
    }

    public function getIterator(): Traversable
    {
        if(count($this->set) == 0) yield [];

        for ($i = $this->min; $i <= count($this->set); $i++) {
            foreach ($this->permute($this->set, $i) as $combination) {
                yield $combination;
            }
        }
    }

    private function permute(array $dataset, $length): Generator
    {
        $originalLength = count($dataset);
        $remainingLength = $originalLength - $length + 1;

        for ($i = 0; $i < $remainingLength; ++$i) {
            $current = $dataset[$i];

            if (1 === $length) {
                yield [$current];
            } else {
                $remaining = array_slice($dataset, $i + 1);

                foreach ($this->permute($remaining, $length - 1) as $permutation) {
                    array_unshift($permutation, $current);

                    yield $permutation;
                }
            }
        }
    }

    public function asArray(): array
    {
        return iterator_to_array($this);
    }

    public function initManualIterator(){
        $this->iterator = $this->getIterator();
        $this->pos = 0;
        $this->offset = 0;
        $this->currentWorkableOffset = -1;
    }

    public function current(){
        return $this->iterator->current();
    }

    public function next(){
        $this->pos++;
        $this->offset++;
        return $this->iterator->next();
    }

    public function valid(){
        return $this->iterator->valid();
    }

    public function skipCurrent(){
        $this->pos--;
    }

    public function maskWorkable(){
        $this->currentWorkableOffset = $this->offset;
    }

    public function isWorkable(){
        return $this->currentWorkableOffset == $this->offset;
    }
}