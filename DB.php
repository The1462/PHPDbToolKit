<?php

    /**
     * Developed By The1462
     */

/** for normal
$__MySQL['default']['hostname'] = 'localhost';
$__MySQL['default']['username'] = 'root';
$__MySQL['default']['password'] = '';
$__MySQL['default']['database'] = 'sinirliyim';
$__MySQL['default']['char_set'] = 'utf8';
$__MySQL['default']['dbcollat'] = 'utf8_general_ci';
*/


/** For CodeIgniter */




class DB
{
    private static $__MySQL=NULL;
    private static $__usageType="CI";
    
    
    public function __construct() {
        switch (self::$__usageType)
        {
            case "CI":
                if(self::$__MySQL==NULL)
                {
                    include(APPPATH.'/config/database.php');
                    // Database data
                    self::$__MySQL=$db;
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

    protected function connect($connection=NULL)
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
    
    
    protected function security($data)
    {
        
        return $data;
    }


    static function get($table, $queryOrID=NULL, $connection="default")
    {
        if($queryOrID==NULL)
        {
            die("You Didn't Write Table id or any query! I will kil you!!!!");
        }
        
        if(is_numeric($queryOrID))
        {
            $query  = "SELECT * FROM ".self::security($table)." WHERE id='".self::security($queryOrID)."'";
        }
        else
        {
            $query  = "SELECT * FROM ".self::security($table)." WHERE ".self::security($queryOrID);
        }
        
        
        $_Connect   = self::connect($connection);
        
        $data   = mysqli_fetch_object(mysqli_query($_Connect, $query));
        
        mysqli_close($_Connect);
        
        
        if($data!=NULL)
        {
            $__dataInformation = (object) array(
                'connection'    => $connection,
                'table'         => $table,
                'id'            => $data->id,
                'record_type'   => "old"
            );

            $data->__dataInformation    = $__dataInformation;
        }
        else
        {
            $data   = false;
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
                }
                else if($singleData->__dataInformation->record_type=="old" && isset($singleData->__dataInformation->table))
                {
                    $_Query = self::updateQueryBuilder($singleData);
                }
                
                $_Connn                 = $_Query["connection"];
                $_QueryList["$_Connn"] .= $_Query["query"];
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
                $_Query = self::insertQueryBuilder($singleData);
                
                $_Connect   = self::connect($_Query["connection"]);
                
                mysqli_query($_Connect, $_Query["query"]);
                
                $_THEID = mysqli_insert_id($_Connect);
                
                mysqli_close($_Connect);
            }
            else if($data->__dataInformation->record_type=="old" && isset($data->__dataInformation->table))
            {
                $_Query = self::updateQueryBuilder($data);
                
                $_Connect   = self::connect($_Query["connection"]);
                
                mysqli_query($_Connect, $_Query["query"]);
                
                $_THEID = mysqli_insert_id($_Connect);
                
                mysqli_close($_Connect);
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
        $_String1   = "UPDATE ".$data->__dataInformation->table." SET ";
        $_String2   = NULL;

        if(isset($data->__dataInformation->id))
        {
            $_String3   = "WHERE id='".self::security($data->__dataInformation->id)."'";
        }
        else if(!isset($data->__dataInformation->id) && isset($data->__dataInformation->where))
        {
            $_String3   = " WHERE ".self::security($data->__dataInformation->where);
        }
        else
        {
            die("You Didn't Write Table id or any query! I will kil you!!!!");
        }


        unset($data->__dataInformation);

        $x=0;
        foreach ($data as $dataKey=>$dataValue)
        {
            if($x!=0)
            {
                $_String2   .= ", ";
            }

            $_String2       .= $dataKey."='".self::security($dataValue)."'";

            $x++;
        }

        if(empty($_String2!=""))
        {
            die("You Must Set Any Value");
        }
        else
        {
            $_queryString   = $_String1.$_String2.$_String3;

            $ResponseData["connection"] = $connection;
            $ResponseData["query"]      = $_queryString.";";

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
                'record_type'   => "old",
                'id'            => $queryOrID
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
    
    static function find($table, $query=NULL, $connection="default")
    {
        if($query==NULL)
        {
            $_Query  = "SELECT * FROM ".self::security($table);
        }
        else
        {
            $_Query  = "SELECT * FROM ".self::security($table)." WHERE ".self::security($query);
        }
        
        
        $_Connect   = self::connect($connection);
        
        $data   = mysqli_query($_Connect, $_Query);
        
        $x=0;
        
        if($data->num_rows>1)
        {
            $_MyRows = NULL;
            while($_Row = mysqli_fetch_array($data))
            {
               $_XaS       = (object)  $_Row;

                $__dataInformation = (object) array(
                    'connection'    => $connection,
                    'table'         => $table,
                    'record_type'   => "old",
                    'id'            => $_Row["id"]
                );

                $_XaS->__dataInformation    = $__dataInformation;

                $_MyRows[$x]= $_XaS;


               $x++;
            }
            
        }
        else if($data->num_rows==1)
        {
            $_XaS       = (object)  mysqli_fetch_array($data);

            $__dataInformation = (object) array(
                'connection'    => $connection,
                'table'         => $table,
                'record_type'   => "old",
                'id'            => $_XaS->id
            );

            $_XaS->__dataInformation    = $__dataInformation;
            
            $_MyRows= $_XaS;
            
            $x=1;
        }
        
        if($x==0)
        {
            $_MyRows    = false;
        }
        
        return $_MyRows;
    }
}