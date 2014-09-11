<?php

/**
 * Returns whether the object is an integer or not
 * is_int doesn't work on strings. This does.
 * @param mixed $input
 * @return boolean
 */
function isInteger($input){
    return(ctype_digit(strval($input)));
}
