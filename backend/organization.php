<?php

require_once 'settings.php';


/**
 * Method for editing the organization information
 * 
 * @param array $args Array with parameters name, email, and organizationID (when applicable)
 * @return String json_encode String
 */
function editOrganization($args){
    $name = isset($args['name']) ? $args['name'] : "";
    $email = isset($args['email']) ? $args['email'] : "";
    $organizationID = isset($args['organizationID']) ? $args['organizationID'] : "";
    $jsonarray['organizationID'] = $args['organizationID'];
    $jsonarray['neworganization'] = false;
    if(!$name){
        $jsonarray['error'] = 'Please enter a name';
        return json_encode($jsonarray);
    }
    if(!$organizationID){
        //create new organization
        $sql = "INSERT INTO organizations VALUES('', '$name', '$email', 1, CURRENT_TIMESTAMP)";
        $query = mysql_query($sql) or die (returnSQLErrorInJSON($sql));
        $jsonarray['success'] = "You created a new organization!";
        $jsonarray['organizationID'] = mysql_insert_id();
        $jsonarray['neworganization'] = true;
        return json_encode($jsonarray);
    }
    $sql = "SELECT * FROM organizations WHERE id = '$organizationID'";
    $query = mysql_query($sql) or die (returnSQLErrorInJSON($sql));
    if(!mysql_fetch_array($query)){
        $jsonarray['error'] = "No organization exists under id = $organizationID";
        return json_encode($jsonarray);
    }
    $sql = "UPDATE organizations
            SET name = '$name', email = '$email'
            WHERE id = '$organizationID'";
    $query = mysql_query($sql) or die (returnSQLErrorInJSON($sql));
    $jsonarray['success'] = "Successfully saved changes.";
    return json_encode($jsonarray);
}

/**
 * Gets the organization name provided the ID.
 * @param int $organizationID organization ID number
 * @return String name of the event
 */
function getOrganizationName($organizationID){
    if(!isInteger($organizationID) || $organizationID < 1){
        throw new Exception("Organization ID must be a positive integer");
    }
    $sql = "SELECT name
            FROM organizations
            WHERE id = '$organizationID'
            AND organizations.on = '1'";
    $query = mysql_query($sql) or die (returnSQLError($sql));
    $result = mysql_fetch_array($query);
    if($result){
        return $result['name'];
    }
    return '';
}