<?php

/**
 *
 * @author Mustafa Zeytin <the1462@gmail.com>
 * @link htpp://www.mustafazeytin.com
 *
 * Company Oggree!
 * @link http://www.oggree.com
 *
 */



/** for normal
$__MySQL['default']['hostname'] = 'localhost';
$__MySQL['default']['username'] = 'root';
$__MySQL['default']['password'] = '';
$__MySQL['default']['database'] = '';
$__MySQL['default']['char_set'] = 'utf8';
$__MySQL['default']['dbcollat'] = 'utf8_general_ci';
 */


class DB
{
    private static $__MySQL=NULL;
    private static $__usageType="Omega";


    public function __construct() {
        switch (self::$__usageType)
        {
            case "CI":
                if(self::$__MySQL==NULL)
                {
                    include(APPPATH.'/config/database.php');
                    // Database data
                    if(isset($db))
                    {
                        self::$__MySQL=$db;
                    }
                }
                break;


            case "Omega":
                if(self::$__MySQL==NULL)
                {

                    include(APP_PATH.'/config/database.php');
                    // Database data
                    if(isset($db))
                    {
                        self::$__MySQL=$db;
                    }
                }
                break;

            case "normal":
                if(self::$__MySQL==NULL)
                {
                    global $__MySQL;

                    self::$__MySQL=$__MySQL;
                }
                break;

            default :
                if(self::$__MySQL==NULL)
                {
                    global $__MySQL;

                    self::$__MySQL=$__MySQL;
                }
                break;
        }

    }

    protected static function connect($connection=NULL)
    {
        if($connection==NULL)
        {
            $connection = "default";
        }

        $_Connect   = mysqli_connect(self::$__MySQL[$connection]['hostname'], self::$__MySQL[$connection]['username'], self::$__MySQL[$connection]['password'], self::$__MySQL[$connection]['database']);

        mysqli_set_charset($_Connect, self::$__MySQL[$connection]['char_set']);

        mysqli_query($_Connect, "SET collation_connection = ". self::$__MySQL[$connection]['dbcollat']);

        return $_Connect;
    }


    protected static function security($data)
    {

        return $data;
    }


    static function get($table, $queryOrID=NULL, $connection="default")
    {
        if($queryOrID==NULL)
        {
            die("You Didn't Write Table id or any query! I will kil you!!!!");
        }

        //getting table primary key


        if(is_numeric($queryOrID))
        {
            $query  = "SET @field_name	= (SELECT column_name FROM information_schema.statistics WHERE table_schema='".self::$__MySQL[$connection]['database']."' AND table_name='".self::security($table)."' AND index_name='PRIMARY' ORDER BY seq_in_index);
                        SET @q= CONCAT('SELECT *, \'', @field_name, '\' as __primary_key FROM `user` WHERE ', @field_name, '=".self::security($queryOrID)."');
                        prepare query from @q;
                        execute query;";
        }
        else
        {
            $query      = "SHOW INDEX FROM ".self::security($table)." WHERE key_name='PRIMARY' and Seq_in_index='1';";
            $query     .= "SELECT * FROM ".self::security($table)." WHERE ".self::security($queryOrID).";";
        }

        $_Connect   = self::connect($connection);


        if ($_Connect->multi_query($query))
        {
            if($_Connect->more_results())
            {
                do {
                    /* store first result set */
                    if ($result = $_Connect->store_result()) {
                        $tempSet = [];
                        while ($row = $result->fetch_object()) {
                            $tempSet[]  = $row;
                        }
                        $data[] = $tempSet;
                        unset($tempSet);
                        $result->free();
                    }

                } while ($_Connect->more_results() && $_Connect->next_result());
            }
            else
            {
                return false;
            }
        }
        else
        {
            print("<pre>");
            print_r($_Connect->error);
            print_r($_Connect->error_list);
            print("<pre>");
            return false;
        }

        mysqli_close($_Connect);



        if(is_numeric($queryOrID))
        {
            if(!empty($data[0][0]))
            {
                if(isset($data[0][0]->__primary_key))
                {
                    $primary_key    = $data[0][0]->__primary_key;

                    $__dataInformation = (object) array(
                        'connection'    => $connection,
                        'table'         => $table,
                        'primary_key'   => $data[0][0]->__primary_key,
                        'primary_value' => $data[0][0]->$primary_key,
                        'record_type'   => "old"
                    );

                    unset($data[0][0]->__primary_key);
                }
                else
                {
                    $__dataInformation = (object) array(
                        'connection'            => $connection,
                        'table'                 => $table,
                        'record_type'           => "old"
                    );
                }

                $data   = $data[0][0];
                $data->__dataInformation    = $__dataInformation;
            }
            else
            {
                $data   = false;
            }
        }
        else
        {
            if(!empty($data[1]))
            {
                if(isset($data[0][0]->Column_name))
                {
                    $primary_key    = $data[0][0]->Column_name;

                    $__dataInformation = (object) array(
                        'connection'    => $connection,
                        'table'         => $table,
                        'primary_key'   => $data[0][0]->Column_name,
                        'primary_value' => $data[1][0]->$primary_key,
                        'record_type'   => "old"
                    );
                }
                else
                {
                    $__dataInformation = (object) array(
                        'connection'            => $connection,
                        'table'                 => $table,
                        'record_type'           => "old"
                    );
                }

                $data   = $data[1][0];
                $data->__dataInformation    = $__dataInformation;
            }
            else
            {
                $data   = false;
            }
        }



        return $data;
    }

    static function create($tableName, $connection="default")
    {
        $__dataInformation = (object) array(
            'connection'    => $connection,
            'table'         => $tableName,
            'record_type'   => "new"
        );

        $data = (object) NULL;

        $data->__dataInformation    = $__dataInformation;

        return $data;
    }

    static function save($data)
    {
        error_reporting(1);

        if(is_array($data))
        {
            $_QueryList = NULL;
            foreach ($data as $singleData)
            {
                if($singleData->__dataInformation->record_type=="new")
                {
                    $_Query = self::insertQueryBuilder($singleData);

                    $_Connn                 = $_Query["connection"];
                    $_QueryList["$_Connn"] .= $_Query["query"];
                }
                else if($singleData->__dataInformation->record_type=="old" && isset($singleData->__dataInformation->table))
                {
                    $_Query = self::updateQueryBuilder($singleData);

                    $_Connn                 = $_Query["connection"];
                    $_QueryList["$_Connn"] .= $_Query["query"];
                }
                else if($singleData->__dataInformation->record_type=="blind" && isset($singleData->__dataInformation->table))
                {
                    $_Query = self::updateQueryBuilder($singleData);

                    $_Connn                 = $_Query["connection"];
                    $_QueryList["$_Connn"] .= $_Query["query"];
                }
            }


            foreach ($_QueryList as $_TheConnection=>$_TheQuery)
            {
                $_Connect   = self::connect($_TheConnection);

                mysqli_multi_query($_Connect, $_TheQuery);

                mysqli_close($_Connect);
            }

            $_THEID = true;

            error_reporting(1);
        }
        else
        {
            if($data->__dataInformation->record_type=="new")
            {
                $_Query = self::insertQueryBuilder($data);

                $_Connect   = self::connect($_Query["connection"]);

                mysqli_query($_Connect, $_Query["query"]);

                $_THEID = mysqli_insert_id($_Connect);

                mysqli_close($_Connect);
            }
            else if($data->__dataInformation->record_type=="old" && isset($data->__dataInformation->table))
            {
                $_Query = self::updateQueryBuilder($data);

                $_Connect   = self::connect($_Query["connection"]);

                if(mysqli_query($_Connect, $_Query["query"]))
                {
                    $_THEID = mysqli_insert_id($_Connect);

                    if($_THEID=="0")
                    {
                        $_THEID =  true;
                    }
                }

                mysqli_close($_Connect);
            }
            else if($data->__dataInformation->record_type=="blind" && isset($data->__dataInformation->table))
            {
                $_Query = self::updateQueryBuilder($data);

                $_Connect   = self::connect($_Query["connection"]);

                if ($_Connect->multi_query($_Query["query"])) {
                    return true;
                }
                else
                {
                    return false;
                }
            }
        }

        return $_THEID;
    }

    private function insertQueryBuilder($data)
    {
        $connection = $data->__dataInformation->connection;

        $_String1   = "INSERT INTO ".$data->__dataInformation->table." ";
        $_String2   = NULL;
        $_String3   = NULL;

        unset($data->__dataInformation);

        $x=0;
        foreach ($data as $dataKey=>$dataValue)
        {
            if(!empty($dataValue))
            {
                if($x!=0)
                {
                    $_String2   .= ", ";
                    $_String3   .= ", ";
                }

                $_String2       .= $dataKey;
                $_String3       .= "'".self::security($dataValue)."'";

                $x++;
            }
        }

        if(empty($_String2!=""))
        {
            die("You Must Set Any Value");
        }
        else
        {
            $_String2   = "(".$_String2.") ";
            $_String3   = "VALUES (".$_String3.")";

            $_queryString   = $_String1.$_String2.$_String3;

            $ResponseData["connection"] = $connection;
            $ResponseData["query"]      = $_queryString.";";

            return $ResponseData;
        }
    }


    private function updateQueryBuilder($data)
    {
        $ResponseData["connection"] = $data->__dataInformation->connection;
        $_String1   = "UPDATE ".$data->__dataInformation->table." SET ";
        $_String2   = NULL;

        if(isset($data->__dataInformation->primary_key))
        {
            $_String3   = " WHERE `".$data->__dataInformation->primary_key."`='".self::security($data->__dataInformation->primary_value)."'";
        }
        else if(!isset($data->__dataInformation->primary_key) && isset($data->__dataInformation->where))
        {
            $_String3   = " WHERE ".self::security($data->__dataInformation->where);
        }
        else if($data->__dataInformation->record_type=="blind")
        {

        }
        else
        {
            die("You Didn't Write Table id or any query! I will kil you!!!!");
        }


        if($data->__dataInformation->record_type=="blind")
        {
            $dataInfBackup  = clone $data->__dataInformation;
        }

        unset($data->__dataInformation);

        $x=0;
        foreach ($data as $dataKey=>$dataValue)
        {
            $_String2[] = $dataKey."='".self::security($dataValue)."'";
        }

        $_String2   = implode(", ", $_String2);

        if($_String2==NULL)
        {
            die("You Must Set Any Value");
        }
        else if($_String2!=NULL && !isset($dataInfBackup))
        {
            $_queryString   = $_String1.$_String2.$_String3;

            $ResponseData["query"]      = $_queryString.";";

            return $ResponseData;
        }
        else
        {
            $_String1   = "SET @field_name	= (SELECT column_name FROM information_schema.statistics WHERE table_schema='".self::$__MySQL[$dataInfBackup->connection]['database']."' AND table_name='".$dataInfBackup->table."' AND index_name='PRIMARY' ORDER BY seq_in_index);";
            $_String1  .= "SET @q= CONCAT('UPDATE ".$dataInfBackup->table." SET ', ";

            $_String2   = addslashes($_String2);
            $_String3   = ", ' WHERE ', @field_name, '=".$dataInfBackup->primary_value."');";
            $_String3  .= "prepare query from @q; EXECUTE query;";

            $ResponseData["query"]  = $_String1."'".$_String2."'".$_String3;

            return $ResponseData;
        }
    }


    static function update($table, $queryOrID=NULL, $connection="default")
    {
        if($queryOrID==NULL)
        {
            die("You Didn't Write Table id or any query! I will kil you!!!!");
        }

        if(is_numeric($queryOrID))
        {
            $__dataInformation = (object) array(
                'connection'    => $connection,
                'table'         => $table,
                'record_type'   => "blind",
                'primary_value' => $queryOrID
            );
        }
        else
        {
            $__dataInformation = (object) array(
                'connection'    => $connection,
                'table'         => $table,
                'record_type'   => "old",
                'where'         => $queryOrID
            );
        }

        $data = (object) NULL;

        $data->__dataInformation    = $__dataInformation;

        return $data;
    }

    static function unique($data, $keys, $status=false)
    {
        $unique = (object) array();
        if(is_array($keys))
        {

        }
        return $data;
    }

    static function exec($query, $connection="default")
    {
        $_Connect   = self::connect($connection);

        $data   = mysqli_query($_Connect, $query);

        if($data)
        {
            return $data;
        }
        else
        {
            return false;
        }
    }

    static function find($table, $query=NULL, $connection="default")
    {
        $_Query       = "SHOW INDEX FROM ".self::security($table)." WHERE key_name='PRIMARY' and Seq_in_index='1';";

        if($query==NULL)
        {
            $_Query .= "SELECT * FROM ".self::security($table);
        }
        else
        {
            $_Query .= "SELECT * FROM ".self::security($table)." WHERE ".self::security($query);
        }


        $_Connect   = self::connect($connection);



        if ($_Connect->multi_query($_Query))
        {
            if($_Connect->more_results())
            {
                do {
                    /* store first result set */
                    if ($result = $_Connect->store_result()) {
                        $tempSet = [];
                        while ($row = $result->fetch_object()) {
                            $tempSet[]  = $row;
                        }
                        $data[] = $tempSet;
                        unset($tempSet);
                        $result->free();
                    }

                } while ($_Connect->more_results() && $_Connect->next_result());
            }
            else
            {
                return false;
            }
        }
        else
        {
            print("<pre>");
            print_r($_Connect->error);
            print_r($_Connect->error_list);
            print("<pre>");
            return false;
        }

        mysqli_close($_Connect);

        if(isset($data[0][0]->Column_name))
        {
            $primary_key    = $data[0][0]->Column_name;

            $_DataSize  = count($data[1]);

            if($_DataSize==1)
            {
                $_XaS       = $data[1][0];

                $__dataInformation = (object) array(
                    'connection'    => $connection,
                    'table'         => $table,
                    'record_type'   => "old",
                    'primary_key'   => $primary_key,
                    'primary_value' => $_XaS->$primary_key,
                );

                $_XaS->__dataInformation    = $__dataInformation;

                return $_XaS;
            }
            else if($_DataSize>1)
            {
                $_MyRows = NULL;
                for($x=0; $x<$_DataSize; $x++)
                {
                    $_XaS       = $data[1][$x];

                    $__dataInformation = (object) array(
                        'connection'    => $connection,
                        'table'         => $table,
                        'record_type'   => "old",
                        'primary_key'   => $primary_key,
                        'primary_value' => $_XaS->$primary_key
                    );

                    $_XaS->__dataInformation    = $__dataInformation;

                    $_MyRows[$x]= $_XaS;
                }

                return $_MyRows;
            }
            else
            {
                return false;
            }
        }
        else
        {
            return $data[1];
        }

        return $data;
    }
}