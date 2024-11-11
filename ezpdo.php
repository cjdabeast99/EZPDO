<?php
class EZPDO{

    protected $instance      = NULL;
    protected $dsn           = "mysql:host={INSERT_HOST};dbname={INSERT_DB};";
    protected $db            = '{INSERT_DB}';
    protected $sql           = NULL;
    protected $sterr         = NULL;
    protected $dberr         = NULL;
    protected $params        = NULL;
    protected $db_user       = NULL;
    protected $db_pass       = NULL;
    protected $lastid        = NULL;
    protected $throwpdo_warning      = false;

    //* Construct our GETSPDO
    public function __construct(string $db, string $host = 'localhost', string $options = '', string $user = '', string $pass = '', bool $throwpdo_warning=false) {
        $this->db_user = $user;
        $this->db_pass = $pass;
        if($db == 'NA'){ $this->dsn = "mysql:host=$host;$options"; }
        else{ $this->dsn = "mysql:host=$host;dbname=$db;$options"; }
        $this->db = $db;
        $this->throwpdo_warning = $throwpdo_warning;
    }

    //* Used for any DML
    public function PDOExec($sql, $params){
        $dbh = $this->TestConn($sql);
        if($dbh == 0){return;}
        $this->sql = $sql;
        $this->params = $params;
        $sth = $dbh->prepare($sql);
        $this->dberr = $sth;
        if (!$sth) {
            $this->PDOError();
            return "{ Data Manipulation: Failed To Execute }";
        }else{
            $count = $sth->execute($params);
            $this->lastid = $dbh->lastInsertId();
            $this->sterr = $sth;
        }
        $this->PDOError();
        return (int)$count;
    }

    //* Used inserting into SQL.
    //? $table = name of table to insert into
    //? $params = array of column associated to values inserting into table.
    public function PDOIns($table, $params){
        $dbh = $this->TestConn("PDOIns($table)");
        if($dbh == 0){return;}

        $i = 0;
        foreach($params as $key => $val){
            $indxparams[$i] = $val;
            $columnstr .= "`$key`,";
            $colvars .= "?,";
            $i++;
        }
        $columnstr = substr_replace($columnstr ,"",-1);
        $colvars = substr_replace($colvars ,"",-1);

        $sql = "INSERT INTO $table ($columnstr) VALUES ($colvars)";
        $this->sql = $sql;
        $this->params = $indxparams;
        $sth = $dbh->prepare($sql);
        $this->dberr = $sth;
        if (!$sth) {
            $this->PDOError();
            return "{ Data Manipulation: Failed To Execute }";
        }else{
            $count = $sth->execute($indxparams);
            $this->lastid = $dbh->lastInsertId();
            $this->sterr = $sth;
        }
        $this->PDOError();
        return (int)$count;
    }

    //* Used to grab array as column of row (1st row if multiple rows exist)
    //? $sql = Sql Statements
    //? $binds = array of column associated to values inserting into table.
    //? $restrict = restrict the $binds to 'int', 'str'.
    public function PDOArr($sql, $binds = '', $restrict = ''){
        $dbh = $this->TestConn($sql);
        if($dbh == 0){return;}
        $this->sql = $sql;
        $this->params = $binds;
        $sth = $dbh->prepare($sql);
        $this->dberr = $sth;
        $i = 0;
        foreach($binds as $key => $bind){
            if($restrict[$i] != ''){
                $restParam = strtoupper($restrict[$i]);
                switch($restParam){
                    case "STR":
                        $sth->bindValue($key,$bind,PDO::PARAM_STR);
                        break;
                    case "INT":
                        $sth->bindValue($key,$bind,PDO::PARAM_INT);
                        break;
                    default:
                        $sth->bindValue($key,$bind);
                        break;        
                }
            }else{
                $sth->bindValue($key,$bind);
            }
            $i++;
        }
        $sth->execute();
        $this->lastid = $dbh->lastInsertId();
        $this->sterr = $sth;
        if (!$sth) {
            $this->PDOError();
            return array("Statement: Failed To Execute");
        }else{
            $res = $sth->fetch(PDO::FETCH_BOTH);
            if(!$res){
                $this->PDOError();
                return array("Statement: Failed To Fetch");
            }
            $this->PDOError();
            return $res;
        }
        $this->PDOError();
        return array("Statement: Returned NULL");
    }

    //* Used to grab multi-dimensional array of all rows
    //? $sql = Sql Statements
    //? $binds = array of column associated to values inserting into table.
    //? $restrict = restrict the $binds to 'int', 'str'.
    //? $fetch = the fetching type. 
    //? [DEFAULT] PDO::FETCH_ASSOC = row as an array indexed by column name, 
    //?           PDO::FETCH_BOTH  = row as an array indexed by both column name and number
    //?           PDO::FETCH_LAZY  = row as an anonymous object with column names as properties
    //?           PDO::FETCH_OBJ   = row as an anonymous object with column names as properties
    public function PDORes($sql, $binds = '', $restrict = ''){
        $dbh = $this->TestConn($sql);
        if($dbh == 0){return;}
        $this->sql = $sql;
        $this->params = $binds;
        $sth = $dbh->prepare($sql);
        $this->dberr = $sth;
        $i = 0;
        foreach($binds as $key => $bind){
            if($restrict[$i] != ''){
                $restParam = strtoupper($restrict[$i]);
                switch($restParam){
                    case "STR":
                        $sth->bindValue($key,$bind,PDO::PARAM_STR);
                        break;
                    case "INT":
                        $sth->bindValue($key,$bind,PDO::PARAM_INT);
                        break;
                    default:
                        $sth->bindValue($key,$bind);
                        break;        
                }
            }else{
                $sth->bindValue($key,$bind);
            }
            $i++;
        }
        $sth->execute();
        $this->lastid = $dbh->lastInsertId();
        $this->sterr = $sth;
        if (!$sth) {
            $this->PDOError();
            return array("Statement: Failed To Execute");
        }else{
            while($row = $sth->fetch(PDO::FETCH_ASSOC)){
                $res[] = $row;
            }
            $this->PDOError();
            return $res;
        }
    }




    //* PDOLID Gets the last insert ID ran in a specific dbh
    public function PDOLID(){
        if($this->lastid != NULL){ 
            return $this->lastid;
        }
    }

    //* Show SQL Schema
    public function PDOSchema($table=''){

        if($table == ''){ $show = "SHOW DATABASES"; }
        else{
                $olddsn = $this->dsn;
                $show = "SHOW TABLES";
                $tmpdsn = "mysql:host=localhost;dbname=$table;";
                $this->dsn = $tmpdsn;
        }


        $dbh = $this->TestConn("PDOSchema($table);");
        if($dbh == 0){return;}

        $ret = $dbh->prepare($show);
        $ret->execute();

        while( ( $dat = $ret->fetchColumn( 0 ) ) !== false )
        {
                $schema[] = $dat;
        }

        $this->dsn = $olddsn;

        return $schema;
    }

    //* PDOError Used to display error messages
    //? $go = set to 1 for testing (Will always display the return message.)
    //? $throw = The error to throw.
    public function PDOError($go = 0, $throw = ''){
        global $msg;
        if($throw != ''){
            $err = 1;
            $msg = $throw;
        }else{
            $dberr = $this->dberr;
            $sterr = $this->sterr;
            $sql = $this->sql;
            foreach($this->params as $key=>$val){ $params .= "<br>[$key]=>$val<br>"; }
            $msg = '';
            $err = 0;
            if(!$dberr){
                $dbDet = $dberr->errorInfo();
                $msg .= "\nDATABASE ERROR [".$dbDet[0]."] ".$dbDet[2];
                $err++;
            }
            $stDet = $sterr->errorInfo();
            if($stDet[2] != ''){
                $msg .= "<br>STATEMENT ERROR [".$stDet[0]."] ".$stDet[2];
                $msg .= "<br><br><b style='color:black;'> --- REFERENCE SQL STATEMENT --- </b><br><br>";
                $msg .= "<b style='color:blue;'>$sql</b>";
                $msg .= "<br><br><b style='color:purple;'>PARAMS: ($params)</b>";
                $err++;
            }
        }
        if($go > 0 || $err > 0){
            $msg .= "<br/><br/>";
            if($this->throwpdo_warning){
                throw new ErrorException($msg, 0, "E_WARNING", __FILE__, __LINE__);
            }else{
                throw new ErrorException($msg, 0, "E_ERROR", __FILE__, __LINE__);
            }
        }
    }

    private function TestConn($sql){
        try {
            $dbh = new PDO($this->dsn, $this->db_user, $this->db_pass);
            return $dbh;
        } catch(Exception $e){
            global $clear_pdo_err;
            $this->PDOError(0," Failed To Connect - Database/Table Communication Failed. DB [<b style='color:purple;'>$this->db</b>]<br>$this->dsn<br>Query: <b style='color:blue;'>$sql</b>");
            return 0;
        }
    }
}
?>
