<?php

function find_nearby()
{
    global $conn;
    global $request;

    $miles = '3959';
    $kms = '6371';
    $limit = '20';

    /*if(!validate_fields($request, array('obj_id')))	{
    die(bad_request());
    }*/

    $radius = (isset($_REQUEST['radius']))?$request->clean($_REQUEST['radius']):'25';

    if(isset($_REQUEST['obj_id']))
    {
	$obj_id = $request->data['obj_id'];
	$user_location = get_obj_location($obj_id);
	$where_str = " AND obj_id != $obj_id ";
	$cache_str = "nearby-list-$obj_id-$radius-{$request->output}";
    }
    else
    {
	$user_location = array('latitude' => $request->clean($_REQUEST['latitude']), 'longitude' => $request->clean($_REQUEST['longitude']));
	$cache_str = "nearby-list-{$user_location['latitude']}-{$user_location['longitude']}"; 
	$where_str = "";
    }

    $nearby_list = Cache::getMem()->get($cache_str);

    if(empty($nearby_list)) {   

    $sql = "SELECT obj_id, latitude, longitude, last_update, 
        ( $miles * acos( cos( radians( {$user_location['latitude']} ) ) * cos( radians( latitude ) ) * cos( radians( longitude  ) - radians( {$user_location['longitude']}) ) + sin( radians({$user_location['latitude']} ) ) * sin( radians( latitude ) ) ) ) AS distance 
        FROM geo HAVING distance < $radius $where_str ORDER BY distance LIMIT 0 , $limit";
    $res = mysql_query($sql, $conn) or die(mysql_error());
    
    $meta = array('code' => '1', 'count' => mysql_num_rows($res));    
    if(isset($_REQUEST['obj_id']))
        $meta['obj_id'] = $request->data['obj_id']; 

    $output = array();
    $objects = array(); 
    while($row = mysql_fetch_array($res))
    {
        $object = array('obj_id' => $row['obj_id'], 'latitude' => $row['latitude'], 'longitude' => $row['longitude'], 'distance' => $row['distance'], 'last_update' => $row['last_update']);
        $user = get_user_profile($row['obj_id'], false);
        $object = array_merge($object, array('user' => $user));

        array_push($objects, $object);
    }
	$output = array_merge($output, $meta);
	$output = array_merge($output, array('objects' => $objects));

	Cache::getMem()->set($cache_str, $output, 0, 10);
	echo format_output($request->output, $output);
    }
    else
	echo $nearby_list;
}

function post()
{
    global $conn;
    global $request;

    if(!validate_fields($request, array('obj_id', 'latitude', 'longitude')))	{
	die(bad_request());
    }

    $sql = "INSERT INTO geo (client_id, obj_id, hash_key, latitude, longitude, last_update) 
    VALUES ('{$_SESSION['app_id']}', 
    '{$request->data['obj_id']}', 
    UNHEX(MD5('{$_SESSION['app_id']}:{$request->data['obj_id']}')), 
    '{$request->data['latitude']}', 
    '{$request->data['longitude']}', UTC_TIMESTAMP() )";

    $res = mysql_query($sql, $conn) or die(mysql_error());

    if($res)
	echo successful_request();
    else
	echo bad_request();
}

function get()
{
    global $conn;
    global $request;

    if(!validate_fields($request, array('obj_id'))) {
	die(bad_request());
    }
    
    $row = Cache::getMem()->get("get-obj-row-{$_SESSION['app_id']}-{$request->data['obj_id']}");

    if(empty($row))
    {
	//TODO: add where check for last_update field
	$sql = "SELECT obj_id, latitude, longitude, last_update FROM geo WHERE hash_key = UNHEX(MD5('{$_SESSION['app_id']}:{$request->data['obj_id']}'))";
	$res = mysql_query($sql, $conn) or die(mysql_error());
	$row = mysql_fetch_array($res);

	Cache::getMem()->set("get-obj-row-{$_SESSION['app_id']}-{$request->data['obj_id']}", $row, 0, 15);
    }
    
    $temp = array('obj_id' => $row['obj_id'], 'latitude' => $row['latitude'], 'longitude' => $row['longitude'], 'last_update' => $row['last_update']);

    return format_output($request->output, $temp);
}

function delete()
{
    global $conn;
    global $request;

    if(!validate_fields($request, array('obj_id'))) {
	die(bad_request());
    }

    // Use this when queing mechanism is in place
    ///$sql = "UPDATE geo SET status=0 WHERE hash_key=UNHEX(MD5('{$_SESSION['app_id']}:{$request->data['obj_id']}')) LIMIT 1";
    $sql = "DELETE FROM geo WHERE hash_key=UNHEX(MD5('{$_SESSION['app_id']}:{$request->data['obj_id']}')) LIMIT 1";
    $res = mysql_query($sql, $conn) or die(mysql_error());

    if($res)
	echo successful_request();
    else
	echo bad_request();
}

function get_obj_location($id)
{
    global $conn;
    $location = Cache::getMem()->get("obj-location-{$_SESSION['app_id']}-$id");

    if(empty($location))
    {
        //TODO: add where check for last_update field
        $sql = "SELECT latitude, longitude FROM geo WHERE hash_key = UNHEX(MD5('{$_SESSION['app_id']}:$id'))";
        $res = mysql_query($sql, $conn) or die(mysql_error());
        $location = mysql_fetch_array($res);

        Cache::getMem()->set("obj-location-{$_SESSION['app_id']}-$id", $location, 0, 15);
    }
    
    return $location;
}

