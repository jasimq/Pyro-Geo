<?php

function bad_request($msg=null, $data=null) {
    $output = array("code" => "0");
    if($msg)
        $output['msg'] = $msg;

    if($data)
        $output = array_merge($output, $data);

    return json_encode($output);
}

function successful_request($msg=null, $data=null) {
    $output = array("code" => "1");
    if($msg)
        $output['msg'] = $msg;

    if($data)
        $output = array_merge($output, $data);
    return json_encode($output);
}

function validate_fields($request, $fields) {
    foreach($fields AS $x => $field)    {
        if(!isset($request->data[$field]) OR trim($request->data[$field]) == '' )
        {   
            logger("FIELD MISSING: $field", 2);
            return false;
        }
    }
    return true;
}

