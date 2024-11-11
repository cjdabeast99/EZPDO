# EZPDO
PHP PDO made easy by one simple class.


# Usage Examples

## Grabbing a single record as an array
$uid = 1;

$db = new EZPDO("accounts");
$sql = "SELECT * FROM users WHERE uid = :id;
$bind = array(":id"=>$uid);
$record = $db->PDOArr($sql,$bind);

print_r($record);

/* Returns:
[uid] => 1,
[username] => "cgarnett",
...
*/

## Grabing multiple records from a table
$db = new EZPDO("accounts");
$sql = "SELECT * FROM users;
// You can still define an array for conditionals as shown in PDOArr, in this case I will not use one.
$records = $db->PDOArr($sql);

foreach($records as $record){
    echo "[$record[uid]]: $record[username]";
}

/* Returns:
[1]: cgarnett
[2]: jdoe
...
*/

## Inserting into a table
//* This has been simplified for the insert statement.
$db = new EZPDO("accounts");
$ins = array(
    "uid"=>5,
    "username"=>"newuser",
    ...
);
$db->PDOIns("users",$ins);

### After inserting an ID to quickly grab the new inserted records PRI Key you can use PDOLID();
$lid = $db->PDOLID();

//* Var "$lid" would return "5" in this case.

## Deleting/Updating
//* Something similar to the insert function could easily be developed in the future but for all other DML besides INSERT this is how it would be accomplished
$db = new EZPDO("accounts");
$sql = "UPDATE accounts.users SET username = ? WHERE uid = ?;
$bind = array("suser",2);
$db->PDOExec($sql,$bind);
