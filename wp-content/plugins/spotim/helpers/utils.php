<?php

abstract class UtilsHelper {
    public function died() {
        die(
            var_dump(
                func_get_args()
            )
        );
    }

    public function vard() {
        var_dump(
            func_get_args()
        );
    }

    public function isAssoc($array) {
        return is_array($array) && array_diff_key($array, array_keys(array_keys($array)));
    }

    public function echoOutput($content, $echo) {
        if ($echo) {
            echo $content;
        }

        return $content;
    }
}