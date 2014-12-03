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

/**
 * Parses post arguments by running post values through
 * mysql_real_escape_string() and then returning
 * them in an associate array in the same key+value
 * pair as they were before.
 * 
 * @return array
 */
function parse_post_arguments(){
    $args = array();
    unset($_POST['purpose']);
    $keys = array_keys($_POST);
    foreach ($keys as $key){
        if($key == "costs"){
            if(!empty($_POST[$key])){
                $args[$key] = json_decode($_POST[$key], true);
                for($i = 0; $i < count($args[$key]); $i++){
                    $costKeys = array_keys($args[$key][$i]);
                    foreach($costKeys as $costKey){
                        $args[$key][$i][$costKey] = mysql_real_escape_string($args[$key][$i][$costKey]);
                    }
                }
            } else {
                $args[$key] = $_POST[$key];
            }
        } else {
            $args[$key] = mysql_real_escape_string($_POST[$key]);
        }
    }
    sort($keys, SORT_STRING);
    $newargs = array();
    for($i = 0; $i < count($keys); $i++){
        $newargs[$i] = $args[$keys[$i]];
    }
    return $newargs;
}