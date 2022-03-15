<?php
//宝塔Linux面板插件demo for PHP
//@author 阿良<287962566@qq.com>

//必需面向对象编程，类名必需为bt_main
//允许面板访问的方法必需是public方法
//通过_get函数获取get参数,通过_post函数获取post参数
//可在public方法中直接return来返回数据到前端，也可以任意地方使用echo输出数据后exit();
//可在./php_version.json中指定兼容的PHP版本，如：["56","71","72","73"]，没有./php_version.json文件时则默认兼容所有PHP版本，面板将选择 已安装的最新版本执行插件
//允许使用模板，请在./templates目录中放入对应方法名的模板，如：test.html，请参考插件开发文档中的【使用模板】章节
//支持直接响应静态文件，请在./static目录中放入静态文件，请参考插件开发文档中的【插件静态文件】章节
class bt_main{
	//不允许被面板访问的方法请不要设置为公有方法
    private static $db_path = "/www/server/panel/data/default.db";
    
    private static $file_path = '/www/server/panel/plugin/restrict';
    
    
    private static $web301key = 'web301_v1_';
    
    
    
	public function test(){
		//_post()会返回所有POST参数，要获取POST参数中的username参数，请使用 _post('username')
		//可通过_version()函数获取面板版本
		//可通过_post('client_ip') 来获取访客IP

		//常量说明：
		//PLU_PATH 插件所在目录
		//PLU_NAME 插件名称
		//PLU_FUN  当前被访问的方法名称
		return array(
		    _post('client_ip'),
			_get(),
			_post(),
			_version(),
			PLU_FUN,
			PLU_NAME,
			PLU_PATH
		);
	}

	//获取phpinfo
	public function phpinfo(){
	    
		return phpinfo();
	}
	
	//网站list
	public function webList()
	{
	    $sql = "SELECT id,pid,name FROM domain where id >0";
	    $db = new SQLite3(self::$db_path);
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
	
	
	/**
	 * 省份
	 * @return boolean|mixed
	 */
	public function provinces()
	{
	    $keys = 'provinces_list';
	    $redis = $this->_redis();
	    if($datas = $redis->get($keys)){
	        if(!is_null($datas))
	        {
	            return json_decode($datas,true);
	        }
	    };
	    include './config/config.php';
	    $plist = $this->curl_get("https://apis.map.qq.com/ws/district/v1/list?key={$tx_api}");
	    $res = json_decode($plist,true);
	    $provinces = isset($res['result'])?$res['result']['0']:[];
	    foreach ($provinces as $key => $item)
	    {
	        $data[$key]['id']= $item['id'];
	        $data[$key]['name']= $item['fullname']; 
	    }
	    $redis->setex($keys,3600*24*30,json_encode($data));
	    return $data;
	}
	
	
	
	function getCity()
	{
	    $redis = $this->_redis();
	    $pid = _post('pid');
	    $keys = 'city_list_'.$pid;
	    if($pid)
	    {
	        if($datas = $redis->get($keys)){
	            return json_decode($datas,true);
	        };
	        include './config/config.php';
	        $url ="https://apis.map.qq.com/ws/district/v1/getchildren?id={$pid}&key={$tx_api}";
	        $datas = $this->curl_get($url);
	        $result = json_decode($datas,true);
	        $res = isset($result['result'])?$result['result']['0']:[];
	        $data = [];
	        foreach ($res as $key => $item)
	        {
	            $data[$key]['id']= $item['id'];
	            $data[$key]['name']= $item['fullname'];
	        }
	        $redis->setex($keys,3600*24*30,json_encode($data));
	        return $data;
	    }  
	 }
	 
	 
	 function SetLimitInfo()
	 {
	     
	    if(_post()){
	        $redis = $this->_redis();
	        $website = _post('website');
	        $urlName_City = 'web_v1_'.$website;
	        $file_name = 'file_'.$website;
	        $cityName = 'web_name_v1_'.$website;
	        $urlInfo = ['provinces'=>_post('provinces'),'city'=>_post('city'),'file'=>_post('file')];
	        if(_post('city') == '00')
	        {
	            $list = substr(_post('provinces'),0,2);
	            $listName = _post('provincesname');
	        }else{
	            $list = _post('city');
	            $listName = _post('cityname');
	        }
	        if($weblist = $redis->get($urlName_City))
	        {
	            $webName = $redis->get($cityName);
	            $webName = json_decode($webName,true);
	            array_push($webName,$listName);
	            $redis->set($cityName,json_encode($webName));
	            $weblist = json_decode($weblist,true);
	            array_push($weblist,$list);
	            $redis->set($urlName_City,json_encode($weblist));
	        }else{
	            $redis->set($cityName,json_encode([$listName]));
	            $redis->set($urlName_City,json_encode([$list]));
	        }
	        if(!empty(_post('file')))
	        {
	            $redis->set($file_name,_post('file'));
	        }
	        return ['status'=>200,$this->weblist,'msg'=>'添加成功'];
	    };
	    return ;
	 }
	
	
	 private function check()
	 {
	     if (!$data = $this->checkTx())
	     {
	        return ['msg'=>'腾讯API错误']; 
	     }
	     if (!$this->checkRedis())
	     {
	         return ['msg'=>'redis扩展未安装'];
	     }
	     if(!$this->checkRedisContents())
	     {
	         return ['msg'=>'检查redis帐号密码!'];
	     }
	     return $data;
	 }
	 
	 
	 private function checkTx()
	 {
	     include './config/config.php';
	     $plist = $this->curl_get("https://apis.map.qq.com/ws/district/v1/list?key={$tx_api}");
	     $res = json_decode($plist,true);
	     if(isset($res['result']))
	     {
	         return $res['result'];
	     }
	     return false;
	 }
	 
	 
	 private function checkRedis()
	 {
	     $var = shell_exec('php -m');
	     if(strpos($var,'redis'))
	     {
	        return true;  
	     }
	     return false;
	 }
	 
	 
	 private function checkRedisContents()
	 {
	     include 'redis_config.php';
	     try {
	         $redis = new \Redis();
	         @$redis->connect($_redis_host,$_redis_port);
	         isset($auth) && !empty($auth)?$redis->auth($_redis_auth):'';
	     }catch (Exception $e)
	     {
	         return false;//['msg'=>'修改失败,检查帐号密码!'];
	     }
	     return true;
	 }
	 
	 
	private function checkRedisFile()
	 { 
	     $path_iso = ini_get_all()['extension_dir']['global_value'].DIRECTORY_SEPARATOR.'redis.so';
	     if(is_file($path_iso))
	     {
	         return true;
	     }
	     return false;
	 }
	 
	 
	
	public function indexcc(){
	    
	    
	    $d = $this->provinces();
	    var_dump($d);
	   exit();
	    $path_iso = ini_get_all()['extension_dir']['global_value'].DIRECTORY_SEPARATOR.'redis.so';
	    if(is_file($path_iso))
	    {
	        return true;
	    }
	    return false;
	    //$this->read(self::$file_path);
	    //return ini_get_all()['extension_dir']['global_value'];
	    //return $this->getCity();
	}
	
	
	
	
  private function read($dir)
  {
	    if(!is_dir($dir)) return false;   
	    $handle = opendir($dir);
	    if($handle){
	        while(($fl = readdir($handle)) !== false)
	        {
	            $temp = $dir.DIRECTORY_SEPARATOR.$fl;
	            if(strpos($temp, ".ini") && !is_null($temp))
	            {
	                return $temp;
	            }
	         }
	     }
	}
	
	protected static $_redis = '';
	
	
	
	private function _redis()
	{
	    include 'redis_config.php';
	    if(!self::$_redis){
	        self::$_redis = new \Redis();
	        self::$_redis->connect($_redis_host,$_redis_port);
	        isset($_redis_auth) && !empty($_redis_auth)?self::$_redis->auth($_redis_auth):'';
	    }
    	return self::$_redis;
	}

	public function setRedis()
   {
       if(_post())
       {
          $host = _post('host');
          $port = _post('port');
          $auth =  _post('auth');
          try {
              self::$_redis = new \Redis();
              @self::$_redis->connect($host,$port);
              isset($auth) && !empty($auth)?self::$_redis->auth($auth):'';
          }catch (Exception $e)
          {
              return ['msg'=>'修改失败,检查帐号密码!'];
          }
          $this->luaTpl($host, $port,$auth);
          $this->phpTpl($host, $port,$auth);
          
          include 'redis_config.php';
          
          return ['host'=>$_redis_host,'port'=>$_redis_port,'auth'=>$_redis_auth,'msg'=>'修改成功'];
          
       } 
   }

   
   
   public function phpTpl($host, $port,$auth = '')
   {
       $phpTpl =  <<<'ModelTpl'
<?php
$_redis_host = '::host';
$_redis_port = '::port';
$_redis_auth = '::auth';
ModelTpl;
       $phpTpl = str_replace(
           array('::host', '::port','::auth'),
           array($host, $port,$auth),
           $phpTpl);
       file_put_contents('redis_config.php', $phpTpl);
   }
   
   
   function luaTpl($host, $port,$auth = '')
   {
       $luaTpl =  <<<'ModelTpl'
module = {}
module.host = "::host"
module.port = "::port"
module.auth = "::auth"
return module
ModelTpl;
       $luaTpl = str_replace(
           array('::host', '::port','::auth'),
           array($host, $port,$auth),
           $luaTpl);
       file_put_contents('redisConf.lua', $luaTpl); 
   }
   
   
   function get301()
   {
       
       if(_post())
       {
           $redis = $this->_redis();
           $web301 = _post('web301');
           $key = self::$web301key.$web301;
           if($url = $redis->get($key))
           {
               return ['msg'=>'','url'=>$url]; 
               
           }
           return ['msg'=>'未设置301','url'=>'']; 
       }
   }
   
   
   function del301()
   {
       if(_post())
       {
           $redis = $this->_redis();
           $web301 = _post('web301');
           $key = self::$web301key.$web301;
           if($redis->del($key))
           {
               return ['msg'=>'刪除成功','url'=>''];
           }
           return ['msg'=>'刪除失败','url'=>$redis->get($key)?$redis->del($key):''];
       }
       
       
   }
   
   function set301()
   {
       if(_post())
       {
           $redis = $this->_redis();
           $web301 = _post('web301');
           $url= _post('url');
           $key = self::$web301key.$web301;
           if($redis->set($key,$url))
           {
               return ['msg'=>'设置成功','url'=>$url];
           }
           return ['msg'=>'设置失败','url'=>''];
       }
       
       
   }
   
   
   
   
   
   
   
   
   public function getRedis()
   {
       include 'redis_config.php';
       
       return ['host'=>$_redis_host,'port'=>$_redis_port,'auth'=>$_redis_auth];
       
   }
   
   public function getweball()
   {
       $redis = $this->_redis();
       if(_post('weburl') == '0')
       {    $idx = '0';
            $name = '全部';  
       }else{
           $name = _post('weburl');
           $idx = _post('weburl');
       }
       $cityName = 'web_name_v1_'._post('weburl');
       $list = $redis->get($cityName);
       $list = json_decode($list,true);
       return ['name'=>$name,'list'=>implode(",", $list),'idx'=>$idx];
   }

   
   public function delweb()
   { 
       
       if(_post())
       {
           $redis = $this->_redis();
           $website =  _post('web_idx');
           $cityName = 'web_name_v1_'.$website;
           $urlName_City = 'web_v1_'.$website;
           $file_name = 'file_'.$website;
           $redis->del($cityName);
           $redis->del($urlName_City);
           $redis->del($file_name);
           return ['status'=>1,'msg'=>'成功'];
       }
   }
   
   
   public function setconfig()
   {
       if(_post())
       {
           
           if($this->phpconfigTpl(_post()) && $this->phpLuaTpl(_post()))
           {
               if (!$data = $this->checkTx())
               {
                   return ['msg'=>'腾讯API错误'];
               }
               if (!$this->checkRedis())
               {
                   return ['msg'=>'redis扩展未安装'];
               }
               if(!$this->checkRedisContents())
               {
                   return ['msg'=>'检查redis帐号密码!'];
               }
               //shell_exec("service nginx restart");
               return ['msg'=>'修改成功','status'=>'200','txapi'=>_post()['txapi'],'webstatus'=>_post('webstatus')];
           } 
           return ['msg'=>'修改失败'];
           
       }
   }
   
   
   public function getconfig()
   {
       include './config/config.php';
       return ['txapi'=>$tx_api,'webstatus'=>$web_status];
   }
   
   
   
   private function phpconfigTpl($post)
   {
       $phpTpl =  <<<'ModelTpl'
<?php
$tx_api = '::txapi';
$web_status = ::webstatus;
ModelTpl;
       $phpTpl = str_replace(
           array('::txapi', '::webstatus'),
           array($post['txapi'], intval($post['webstatus'])),
           $phpTpl);
      return file_put_contents('./config/config.php', $phpTpl);
   }
   
   private function phpLuaTpl($post)
   {
       $phpTpl =  <<<'ModelTpl'
module = {}
module.txapi = "::txapi"
module.status = ::webstatus
return module
ModelTpl;
       $phpTpl = str_replace(
           array('::txapi', '::webstatus'),
           array($post['txapi'],intval($post['webstatus'])),
           $phpTpl);
       return file_put_contents('./config/config.lua', $phpTpl);
   }
   
   
   
   function curl_get($url){
       
       $header = array(
           'Accept: application/json',
       );
       $curl = curl_init();
       curl_setopt($curl, CURLOPT_URL, $url);
       curl_setopt($curl, CURLOPT_HEADER, 0);
       // 超时设置,以秒为单位
       curl_setopt($curl, CURLOPT_TIMEOUT, 30);
       // 超时设置，以毫秒为单位
       //curl_setopt($curl, CURLOPT_TIMEOUT_MS, 500);
       curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
       curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
       curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
       curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
       $data = curl_exec($curl);
       curl_close($curl);
       return $data;
   }
   
}


?>