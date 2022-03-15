local redisins = {}
local redis = require "resty.redis"
local config = require "redisConf"
-- 连接redis 实例
function redisins.int()
	local red = redis:new()
	red:set_timeout(1000) -- 1 sec
	local ok, err = red:connect(config.host, config.port)
	--ngx.say('redis--is--ok')
	if not ok then
	    ngx.say("Redis connection failed: ", err)
	   	return nil
	end
	
	if config.auth then
		local res, err = red:auth(config.auth)
	end
	return red
end


return redisins