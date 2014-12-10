<?php

require_once 'settings.php';

/**
 * Functions related to getting, editing, creating, and statistical analysis of 
 * organiations. Organizations hold events. There is a one to many relation
 * with organizations to events. Similarly for organizations and
 * organizationattributes.
 * 
 * Organizations are stored in the organizations table with this schema
 * Field           | Type         | Null | Key | Default           | Extra
 * id              | int(11)      | NO   | PRI | NULL              | auto_increment
 * name            | varchar(127) | NO   | MUL | NULL              | 
 * email           | varchar(127) | NO   |     | NULL              | 
 * status          | tinyint(1)   | NO   |     | 1                 | 
 * timestamp       | timestamp    | NO   |     | CURRENT_TIMESTAMP | 
 * 
 * Organizations Attributes are stored in the organizationattributes table
 * with this schema
 * Field              | Type          | Null | Key | Default           | Extra
 * id                 | int(11)       | NO   | PRI | NULL              | auto_increment
 * organization_id    | int(11)       | NO   |     | NULL              | 
 * name               | varchar(128)  | NO   |     | NULL              | 
 * value              | varchar(8192) | NO   |     | NULL              | 
 * status             | tinyint(1)    | NO   |     | 1                 | 
 * timestamp          | timestamp     | NO   |     | CURRENT_TIMESTAMP | 
 */

function createOrganization($email, $name){
    validateOrganizationEmail($email);
    validateOrganizationName($name);
    $createOrganizationSQL = "INSERT INTO organizations VALUES('', '$name', '$email', 1, CURRENT_TIMESTAMP)";
    mysql_query($createOrganizationSQL) or die (mysql_error());
    return readOrganization(mysql_insert_id());
}

function readOrganization($organizationID){
    validateOrganizationID($organizationID);
    $selectOrganizationSQL = "
        SELECT *
        FROM organizations
        WHERE organizations.id = '$organizationID'";
    $selectOrganizationQuery = mysql_query($selectOrganizationSQL) or die(mysql_error());
    $organization = mysql_fetch_array($selectOrganizationQuery);
    if($organization){
        return $organization;
    } else {
        throw new Exception("organizationID refers to non-existent organization");
    }
}

function updateOrganization($email, $name, $organizationID){
    validateOrganizationEmail($email);
    validateOrganizationName($name);
    validateOrganizationID($organizationID);
    readOrganization($organizationID);
    $updateOrganizationSQL = "
        UPDATE organizations
        SET organizations.email = '$email', $name = '$name'
        WHERE organizations.id = '$organizationID'";
    mysql_query($updateOrganizationSQL) or die(mysql_error());
    return readOrganization($organizationID);
}

function deleteOrganization($organizationID){
    validateOrganizationID($organizationID);
    readOrganization($organizationID);
    $deleteOrganizationSQL = "
        UPDATE organizations
        SET organizations.status = '0'
        WHERE organizations.id = '$organizationID'";
    mysql_query($deleteOrganizationSQL) or die(mysql_error());
    return readOrganization($organizationID);
}


/**
 * Creates a new organization
 * 
 * @param string $email organization email address
 * @param string $name organization name
 * @return int organization ID
 * @throws Exception if $name is empty
 */ /*
function createOrganization($email, $name){
    if(empty($name)){
        throw new Exception("Organization name cannot be empty.");
    }
    $createOrganizationSQL = "INSERT INTO organizations VALUES('', '$name', '$email', 1, CURRENT_TIMESTAMP)";
    mysql_query($createOrganizationSQL) or die (mysql_error());
    return mysql_insert_id();
} */

/**
 * Method for editing the organization information and creating organizations
 * 
 * @param string $name Name of the organization
 * @param int $organizationID Organization ID
 * @return int Organization ID
 * @throws Exception if $organizationID is not a non-negative integer
 * @throws Exception if no organization exists under $organizationID
 * @throws Exception if $name is empty
 */
function editOrganization($name, $organizationID){
    //will add email later
    $email = "";
    if(!$name){
        throw new Exception("Name cannot be empty.");
    }
    if((!isInteger($organizationID) || $organizationID < 0) && $organizationID != ""){
        throw new Exception("Organization ID must be a non-negative integer");
    }
    if($organizationID == 0){
        return createOrganization($email, $name);
    } else {
        //edit existing organization
        $selectOrganizationSQL = "SELECT * FROM organizations WHERE id = '$organizationID'";
        $selectOrganizationQuery = mysql_query($selectOrganizationSQL) or die (mysql_error());
        if(!mysql_fetch_array($selectOrganizationQuery)){
            throw new Exception("No organization exists under id = $organizationID");
        }
        $updateOrganizationSQL = "UPDATE organizations
                SET name = '$name', email = '$email'
                WHERE id = '$organizationID'";
        mysql_query($updateOrganizationSQL) or die (mysql_error());
        return $organizationID;
    }
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

/**
 * Searches the database for organizations that match LIKE %name%
 * and returns them in an array
 * @param string $name name to search for
 * @return array with array of organizations $array[0]['id'] being the
 * organization's id number and $array[0]['name'] being the organization name.
 * $array[2] would give me the array of the 3rd organization that matched the
 * search result.
 */
function searchOrganizations($name){
    $sql = "SELECT * 
            FROM organizations 
            WHERE name LIKE '%$name%'
            AND status = '1'";
    $query = mysql_query($sql) or die (mysql_error());
    $organizations = array();
    while($organization = mysql_fetch_array($query)){
        array_push($organizations, array(
            "organizationResultID" => $organization['id'],
            "organizationResultName" => $organization['name']
        ));
    }
    return $organizations;
}