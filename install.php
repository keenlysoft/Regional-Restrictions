<?php
$ngx_file = '/www/server/nginx/conf/nginx.conf';
$fileInfo = file_get_contents($ngx_file);
$phpTpl =  <<<'ModelTpl'
    include       mime.types;
    #restict 插件
    include /www/server/panel/plugin/restrict/restict.conf;
    #restict 插件-END
    
ModelTpl;
$data = str_ireplace('include       mime.types;', $phpTpl, $fileInfo);
$user = "user  www www;";
$userinfo = "user  root root;";
$data = str_ireplace($user, $userinfo, $data);
file_put_contents($ngx_file, $data);
## ini_get_all()['extension_dir']['global_value']/redis.so
$redisTPL = <<<'ModelTpl'
[redis]
extension = ::path
ModelTpl;
$file_path = '/www/server/panel/plugin/restrict';
$iniFile = read($file_path);
if($iniFile){
    $path_iso = ini_get_all()['extension_dir']['global_value'].DIRECTORY_SEPARATOR.'redis.so';
    if(is_file($path_iso))
    {
        $data = str_ireplace('::path', $path_iso, $redisTPL);
        file_put_contents($iniFile, $redisTPL,FILE_APPEND);
    }else
    {
        file_put_contents($iniFile, ";redis not is then install",FILE_APPEND);
        $data = str_ireplace('::path', $path_iso, $redisTPL);
        file_put_contents($iniFile, $redisTPL,FILE_APPEND);
    }
}
function read($dir)
{
    if(!is_dir($dir)) return false;
    $handle = opendir($dir);
    if($handle){
        while(($fl = readdir($handle)) !== false)
        {
            $temp = $dir.DIRECTORY_SEPARATOR.$fl;
            if(strpos($temp, ".ini") && !is_null($temp) && $fl != 'php_cli_72.ini')
            {
                return $temp;
            }else{
                return false;
            }
        }
    }
}




exit;
$file = '/www/server/panel/class/panelSite.py';

$file_path = '/www/server/panel/plugin/restrict';

$fileInfo = file_get_contents($file);
$phpTpl =  <<<'ModelTpl'
#PHP-INFO-END

    #restict 插件
    include /www/server/panel/plugin/restrict/restict.conf;
    #restict 插件-END

ModelTpl;
$data = str_ireplace('#PHP-INFO-END', $phpTpl, $fileInfo);
file_put_contents($file, $data);
## ini_get_all()['extension_dir']['global_value']/redis.so
$redisTPL = <<<'ModelTpl'
[redis]
extension = ::path
ModelTpl;

$iniFile = read($file_path);

if($iniFile){
    $path_iso = ini_get_all()['extension_dir']['global_value'].DIRECTORY_SEPARATOR.'redis.so';
    if(is_file($path_iso))
    {
        $data = str_ireplace('::path', $path_iso, $redisTPL);
        file_put_contents($iniFile, $redisTPL,FILE_APPEND);
    }else
    {
        file_put_contents($iniFile, ";redis not is then install",FILE_APPEND);
        $data = str_ireplace('::path', $path_iso, $redisTPL);
        file_put_contents($iniFile, $redisTPL,FILE_APPEND);
    }
}
ngx_replace();







function webconfig()
	{
	    $db_path = "/www/server/panel/data/default.db";
	    $sql = "SELECT id,name FROM sites where id >0";
	    $db = new SQLite3($db_path);
	    $d = $db->query($sql);
	    $i = 0;
	    while ($res = $d->fetchArray())
	    {
	        $data[$i]['id'] = $res['id'];
	        $data[$i]['name'] = $res['name'];
	        $i++;
	    }
	    return $data;
	}
	
	function ngx_replace()
	{
	    $conf = webconfig();
        $config = "/www/server/panel/vhost/nginx/";
        foreach ($conf as $value) {
            $files = $config.$value['name'].'.conf';
            ngx_replace_file($files);
        }
        	    
	}
	
	function un_ngx_replace()
	{
	    $conf = webconfig();
        $config = "/www/server/panel/vhost/nginx/";
        foreach ($conf as $value) {
            $files = $config.$value['name'].'.conf';
            un_ngx_replace_file($files);
        }
        	    
	}
	
	function ngx_replace_file($file)
    {
        $fileInfo = file_get_contents($file);
$phpTpl =  <<<'ModelTpl'
#PHP-INFO-END

    #restict 插件
    include /www/server/panel/plugin/restrict/restict.conf;
    #restict 插件-END

ModelTpl;
        $data = str_ireplace('#PHP-INFO-END', $phpTpl, $fileInfo);
        file_put_contents($file, $data);
    }


function un_ngx_replace_file($file)
    {
        $fileInfo = file_get_contents($file);
        $phpTpl =  'include /www/server/panel/plugin/restrict/restict.conf;';
        $data = str_ireplace($phpTpl,'#include /www/server/panel/plugin/restrict/restict.conf;', $fileInfo);
        file_put_contents($file, $data);
    }



