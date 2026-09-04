<?php

defined('ABSPATH') || die();

class HashFormStrReader {

    private $pos = 0;
    private $max = 0;
    private $string;

    public function __construct($string) {
        $this->string = $string;
        $this->max = strlen($this->string) - 1;
    }

    public function read_until($char, $discard_char = true) {
        $value = '';
        while (null !== ($one = $this->read_one())) {
            if ($one !== $char || !$discard_char) {
                $value .= $one;
            }
            if ($one === $char) {
                break;
            }
        }
        return $value;
    }

    public function read($count) {
        $value = '';

        while ($count > 0 && !is_null($one = $this->read_one())) {
            $value .= $one;
            --$count;
        }
        return $this->strip_quotes($value);
    }

    private function read_one() {
        if ($this->pos <= $this->max) {
            $value = $this->string[$this->pos];
            $this->pos += 1;
        } else {
            $value = null;
        }
        return $value;
    }

    private function strip_quotes($string) {
        // Only remove exactly one quote from the start and the end and then only if there is one at each end.
        if (strlen($string) < 2 || substr($string, 0, 1) !== '"' || substr($string, -1, 1) !== '"') {
            // Too short, or does not start or end with a quote.
            return $string;
        }
        // Return the middle of the string, from the second character to the second-but-last.
        return substr($string, 1, -1);
    }

}
