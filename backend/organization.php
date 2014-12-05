<?php

require_once 'settings.php';


/**
 * Method for editing the organization information and creating organizations
 * 
 * @param string $name Name of the organization
 * @param int $organizationID Organization ID
 * @return array $array['success'] with string saying editing was successful,
 * otherwise $array['success'] with string saying event creation was successful
 * with correlated $array['organizationID'] and $array['neworganization'] = true.
 * @throws Exception if $organizationID is not a non-negative integer
 * @throws Exception if no organization exists under $organizationID
 * @throws Exception if $name is empty
 */
function editOrganization($name, $organizationID){
    $name = isset($name) ? $name : "";
    //will add email later
    $email = "";
    if((!isInteger($organizationID) || $organizationID < 0)){
        throw new Exception("Organization ID must be a non-negative integer");
    }
    $array['organizationID'] = $organizationID;
    $array['neworganization'] = false;
    if(!$name){
        throw new Exception("Name cannot be empty.");
    }
    if(!$organizationID){
        //create new organization
        $sql = "INSERT INTO organizations VALUES('', '$name', '$email', 1, CURRENT_TIMESTAMP)";
        $query = mysql_query($sql) or die (mysql_error());
        $array['success'] = "You created a new organization!";
        $array['organizationID'] = mysql_insert_id();
        $array['neworganization'] = true;
        return $array;
    }
    //edit existing organization
    $sql = "SELECT * FROM organizations WHERE id = '$organizationID'";
    $query = mysql_query($sql) or die (mysql_error());
    if(!mysql_fetch_array($query)){
        throw new Exception("No organization exists under id = $organizationID");
    }
    $sql = "UPDATE organizations
            SET name = '$name', email = '$email'
            WHERE id = '$organizationID'";
    $query = mysql_query($sql) or die (mysql_error());
    $array['success'] = "Successfully saved changes.";
    return $array;
}

/**
 * Gets the organization name provided the ID.
 * @param int $organizationID Organization ID
 * @return string name of the event
 * @throws Exception if $organizationID is not a positive integer
 */
function getOrganizationName($organizationID){
    if(!isInteger($organizationID) || $organizationID < 1){
        throw new Exception("Organization ID must be a positive integer");
    }
    $sql = "SELECT name
            FROM organizations
            WHERE id = '$organizationID'
            AND organizations.status = '1'";
    $query = mysql_query($sql) or die (mysql_error());
    $result = mysql_fetch_array($query);
    if($result){
        return $result['name'];
    }
    return '';
}

/**
 * Infers the organization ID based off the event ID.
 * @param int $eventID Event ID
 * @return int
 * @throws Exception if $eventID is not a positive integer
 * @throws Exception if $eventID refers to a non-existent event
 */
function inferOrganizationID($eventID){
    if(!isInteger($eventID) || $eventID < 1){
        throw new Exception("Event ID in inferOrganizationID must be a positive integer.");
    }
    $sql = "SELECT * FROM events WHERE id = '$eventID'";
    $query = mysql_query($sql) or die (mysql_error());
    $result = mysql_fetch_array($query);
    if($result){
        return $result['organization_id'];
    } else {
        throw new Exception("Invalid event ID given to inferOrganizationID.");
    }
}

/**
 * Returns true/false based on whether a value of
 * (id, $organizationID, 'Free Entrances Feature On', 'true', '1', TIMESTAMP)
 * is in organizationattributes.
 * @param int $organizationID Organization ID
 * @return boolean
 * @throws Exception if $organizationID is not a positive integer
 */
function isFreeEntranceEnabled($organizationID){
    if(!isInteger($organizationID) || $organizationID < 1){
        throw new Exception("organizationID in isFreeEntranceEnabled must be a positive integer");
    }
    $sql = "SELECT *
            FROM organizationattributes
            WHERE organization_id = '$organizationID'
            AND name = 'Free Entrances Feature On'";
    $query = mysql_query($sql) or die (mysql_error());
    $result = mysql_fetch_array($query);
    if(!empty($result) && $result['status'] == "1" && $result['value'] == "true"){
        return true;
    }
    return false;
}

