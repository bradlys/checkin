<?php

/**
 * Returns whether the object is an integer or not
 * @param mixed $input
 * @return boolean
 */
function isInteger($input){
    return(ctype_digit(strval($input)));
}
