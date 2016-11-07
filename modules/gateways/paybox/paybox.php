<?php

class PayBox {

    public static function pg_sig($script, $params, $secret_key) 
    {
        ksort($params);
        return md5($script.';'.implode(';', array_values($params)).';'.$secret_key);
    }

}
