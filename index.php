<?php

include_once("mysql.php");

try {
    $MyDatabase = new MohammadAzad\Mysql('127.0.0.1','root','','azad');
    $MyDatabase->table = "user_data";

    $MyDatabase->Create(
        [
            'name'=>'user_id',
            'auto_increment'=>true,
            'primary_key'=>true,
            'type'=>'int'
        ],
        [
            'name'=>'first_name',
            'type'=>'VARCHAR',
            'size'=>'128',
            'not_null'=>true,
        ],
        [
            'name'=>'last_name',
            'type'=>'VARCHAR',
            'size'=>'128',
            'not_null'=>true,
        ],
    );
    
    if(!$MyDatabase->ColumnExists("age") and !$MyDatabase->ColumnExists("zipcode")) {
        $MyDatabase->CreateColumn(
            [
                'name'=>'age',
                'type'=>'INT'
            ],
            [
                'name'=>'zipcode',
                'type'=>'INT',
            ],
        );
    }
    

    $MyDatabase->InsertData([
        'first_name'=>'ehsan',
        'last_name'=>'soltani',
        'age'=>18,
        'zipcode'=>12356,
    ]);
    $MyDatabase->InsertData([
        'first_name'=>'fatemeh',
        'last_name'=>'yazdani',
        'age'=>20,
        'zipcode'=>31332,
    ]);
    $MyDatabase->MultiInsertData(
        ['first_name','last_name','age','zipcode'],
        [
            ['reza','fazeli',22,21214],
            ['mohsen','kamali',30,13253],
            ['ghasem','akbari',44,53423],
            ['mohammad','azad',20,84563]
        ]);


    $user = $MyDatabase->Find("first_name='mohammad' AND last_name='azad'");
    $MyDatabase->Update(['last_name'=>'Azad'],$user);

} catch (MohammadAzad\MySqlMethodErrors $Error) {
    print ($Error->Message());
    /*
        $Error->string;
        $Error->guide;
        $Error->method;
        $Error->code;
    */
} catch (MohammadAzad\MySqlQueryErrors $Error) {
    print ($Error->Message());
    /*
        $Error->string;
        $Error->query;
        $Error->method;
        $Error->code;
    */
}

print ("Write by Mohammad Azad")

?>