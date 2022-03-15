local tools = {}
local redis = require "redis"
local cjson = require "cjson"
local config = require "config.config"


function tools.check(list,code,webhtml)
	if not code then
		return
	end
	for k,v in ipairs(list) do
		if v == code then
			if webhtml then
				return ngx.redirect(webhtml)
			else
				ngx.exit(502)
			end
		end
	end
end

function tools.web301()
	local red = redis.int()
	local weburl, err = red:get('web301_v1_'..ngx.var.host);
	if (weburl == ngx.null or weburl == '') then
		return nil
	end
	local uri = tools.uri();
	if weburl then
		if uri then
			return ngx.redirect(weburl..uri,301)
		else
			return ngx.redirect(weburl,301)
		end
	end
end

function tools.uri()
	local arg = ngx.req.get_uri_args()
	local url = ngx.encode_args(arg)
	if url ~= '' then
		weburl = ngx.var.uri..'?'..url;
	else
		weburl = ngx.var.uri
	end
	return weburl
end



function tools.checking()
	local code,err = tools.getCityId();
	local weblist,err = tools.getWebAll();
	if weblist ~= ngx.null and weblist then
		local allurl,err = tools.getFile(0)
		local resallProvince,err = tools.provinceCheck(weblist,code,allurl) --全部省份
		local resallCity,err = tools.check(weblist,code,allurl) --全部城市
	end
	local resweb,err = tools.getWebUrl()
	if resweb ~= ngx.null and resweb then
		local url,err = tools.getFile(ngx.var.host)
		local web,err = tools.provinceCheck(resweb,code,url)
		local list,err = tools.check(resweb,code,url)
	end
end





function tools.getWebUrl()
	local red = redis.int()
	local webLimit, err = red:get('web_v1_'..ngx.var.host);
	if (webLimit == ngx.null or webLimit == '') then
		return nil
	end
	local webLimit = cjson.decode(webLimit);
	return webLimit
end




function tools.getWebAll()
	local red = redis.int()
	local webLimit, err = red:get('web_v1_0');
	if (webLimit == ngx.null or webLimit == '') then
		return nil
	end
	local data = cjson.decode(webLimit);
	return data
end




function tools.getFile(file)
	local red = redis.int()
	local result, err = red:get('file_'..file);
	if (result == ngx.null or result == '') then
		return nil
	end
	--local data = cjson.decode(result);
	return result
end




function tools.getCityId()
	local ip,err = tools.getIp()
	local rkey = 'tx_city_v2_'..ip
	local red = redis.int()
	local rinfo,err = red:get(rkey)
	if rinfo ~= ngx.null then
		return rinfo
	end
	--local res = ngx.location.capture("/check-city-lua")
	local res = tools.reGetInfo()
	local data = cjson.decode(res);
	if not data.result then
		return;
	end
	local code = data.result.ad_info.adcode
	red:setex(rkey,3600*12,code)
	return tostring(code) -- string.sub(clientIP,1,2)
end


function tools.getIp()
	local headers=ngx.req.get_headers()
	local clientIP = headers["x-forwarded-for"]
	if clientIP == nil or string.len(clientIP) == 0 or clientIP == "unknown" then
		clientIP = headers["Proxy-Client-IP"]
	end
	if clientIP == nil or string.len(clientIP) == 0 or clientIP == "unknown" then
		clientIP = headers["WL-Proxy-Client-IP"]
	end
	if clientIP == nil or string.len(clientIP) == 0 or clientIP == "unknown" then
		clientIP = ngx.var.remote_addr
	end
	-- 对于通过多个代理的情况，第一个IP为客户端真实IP,多个IP按照‘,‘分割
	if clientIP ~= nil and string.len(clientIP) >15  then
		local pos  = string.find(clientIP, ",", 1)
		clientIP = string.sub(clientIP,1,pos-1)
	end
	return clientIP
end




function tools.reGetInfo()
	res,err = tools.getCityInfo();
	if not res then
		res,err = tools.getCityInfo();
	end
	if not res then
		res,err = tools.getCityInfo();
	end
	return res;
end




function tools.getCityInfo()
	local url = 'http://apis.map.qq.com/ws/location/v1/ip?key='..config.txapi..'&ip='..ngx.var.remote_addr;
	local res,err = tools.Get(url);
	return res;
end


function tools.http_post_client(url, body, timeout)
	local zhttp = require("resty.http")
    local httpc = zhttp.new()
    timeout = timeout or 30000
    httpc:set_timeout(timeout)
    local res, err_ = httpc:request_uri(url, {
        method = "GET",
        body = body,
        headers = {
            ["Content-Type"] = "application/json; charset=UTF-8",
        }
    })
   if not res then
        return nil, err_
   else
      if res.status == 200 then
        return res.body, err_
      else
        return nil, err_
      end
    end
end

function tools.Get(url)
	local http = require "resty.http"
    local httpc = http.new()
    local res, err = httpc:request_uri(url, {
		method = "GET",
		body = "a=1&b=2",
		headers = {
		  ["Content-Type"] = "application/json;charset=utf-8",
		},
		keepalive_timeout = 60000,
		keepalive_pool = 10
    })
	if not res then
		return
	end
    return res.body;
end






--省份
function tools.provinceCheck(list,code,webhtml)
	if not code then
		return
	end
	local code,err = string.sub(code,1,2)
	return tools.check(list,code,webhtml)
end




return tools