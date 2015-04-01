# PHPDbToolKit

it's just alpha mysql database handler

#Settings
  
  Codeigniter:
  
    Copy "Application/Library" Folder
    Set Line 25: $__usageType="CI";
    DB settings standart in ci:
    
    Application/config/database.php
    
    
  Single:
  
    Copy where you want
    delete line 7 and 14
    Set Line 25: $__usageType="normal";
    Set $__MySQL values
    include file
    
    
#Usage:
  
  Get Single Line:
  
    Calling by ID:
    DB::get("table_name", 2);

    Calling by Query
    DB::get("table_name", "name LIKE '%as%' and city='2'");

    if you want get on second db
    DB::get("table_name", "id_or_query", "other_db_set");
    
  Finding Lines:
  
    DB::find("table_name", "city='new york'");
    
    finding on other db
    DB::find("table_name", "city='new york'", "other_db_set");
    
  Creating Record:
  
    Step 1: Select Your Table
    $newRecord  = DB::create("table_name");
    
    Step 2: Set Row Values
    $newRecord  -> name     = "John";
    $newRecord  -> lastname = "Doe";
    
    Step 3: Save
    $save = DB::save($newRecord); //return insert id
    
  Update Record:
  
    Option 1(Loading Your Record):
    
    Step 1: Get Your Record
    $record = DB::get("table_name);
    
    Step 2: Edit Row/s
    $record -> name = "Jhonny";
    $record -> lastname = "Does";
    
    Step 3: Save
    DB::save($record);
    
    
    Option 2(Just Update):
    
    Step 1: Build Query Settings
    $record = DB::update("table_name", "id_or_query", "optional_db_set");
    
    Step 2: Set Row/s
    $record -> name = "Doctor";
    
    Step 3: Save
    DB::save($record);
    
  Multi Save:
  
    Sample 1:
    $recordOne  = DB::get("users", "username='The1462' and password='12345'");
    $recordOne  -> login_time = time();
    
    $recordTwo  = DB::create("user_logs");
    $recordTwo  -> user_id      = $recordOne->id;
    $recordTwo  -> loggin_time  = time();
    
    
    $records  = array($recordOne, $recordTwo);
    $save     = DB::save($records);
  
    Sample 2:
    $recordOne  = DB::update("users", 2);
    $recordOne  -> status = 0;
    
    $recordTwo  = DB::update("users", 3);
    $recordTwo  -> status = 0;
    
    $recordThree  = DB::update("users", 28);
    $recordThree  -> status = 1;
    
    $records  = array($recordThree, $recordOne, $recordTwo);
    $save     = DB::save($records);
    
