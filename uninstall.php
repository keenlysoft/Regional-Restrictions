<?php
$ngx_file = '/www/server/nginx/conf/nginx.conf';
$fileInfo = file_get_contents($ngx_file);
$data = str_ireplace("include /www/server/panel/plugin/restrict/restict.conf;", '#include /www/server/panel/plugin/restrict/restict.conf;', $fileInfo);
file_put_contents($ngx_file, $data);
exit();

$file = '/www/server/panel/class/panelSite.py';
$fileInfo = file_get_contents($file);
$phpTpl =  <<<'ModelTpl'
#PHP-INFO-END

    #restict 插件
    include /www/server/panel/plugin/restrict/restict.conf;
    #restict 插件-END
    
ModelTpl;
un_ngx_replace();

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
$phpTpl =  <<<'ModelTpl'
    #restict 插件
    include /www/server/panel/plugin/restrict/restict.conf;
    #restict 插件-END
ModelTpl;
        $data = str_ireplace($phpTpl,'', $fileInfo);
        file_put_contents($file, $data);
    }



