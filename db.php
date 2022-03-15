<?php
class db extends SQLite3
{
    function __construct()
    {
       include 'config.php';
       $this->open($db_path);
       
    }  
    
}