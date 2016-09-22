<?php

use Tygh\Registry;

class Payfort_Fort_Util
{

    public static function getRegistry($key)
    {
        return Registry::get($key);
    }

}

?>