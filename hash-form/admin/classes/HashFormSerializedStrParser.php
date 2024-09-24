<?php

defined('ABSPATH') || die();

class HashFormSerializedStrParser {

    private static $instance;

    public static function get() {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {

    }

    public function parse($string) {
        return $this->do_parse(new HashFormStrReader($string));
    }

    private function do_parse($string) {
        $val = null;

        // May be : or ; as a terminator, depending on what the data type is.
        $type = substr($string->read(2), 0, 1);

        switch ($type) {
            case 'a':
                // Associative array: a:length:{[index][value]...}
                $count = (int) $string->read_until(':');

                // Eat the opening "{" of the array.
                $string->read(1);

                $val = array();
                for ($i = 0; $i < $count; $i++) {
                    $array_key = $this->do_parse($string);
                    $array_value = $this->do_parse($string);

                    if (!is_array($array_key)) {
                        $val[$array_key] = $array_value;
                    }
                }

                // Eat "}" terminating the array.
                $string->read(1);
                break;

            case 's':
                $len = (int) $string->read_until(':');
                $val = $string->read($len + 2);

                // Eat the separator.
                $string->read(1);
                break;

            case 'i':
                $val = (int) $string->read_until(';');
                break;

            case 'd':
                $val = (float) $string->read_until(';');
                break;

            case 'b':
                // Boolean is 0 or 1.
                $bool = $string->read(2);
                $val = substr($bool, 0, 1) == '1';
                break;

            default:
                // Includes case 'N' and case 'O'.
                // Treat a serialized object or anything unexpected as Null.
                $val = null;
                break;
        }//end switch

        return $val;
    }

}
