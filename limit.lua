package.path = "/www/server/panel/plugin/restrict/?.lua;"
local redis = require "redis"
local tools = require "tools"
local status, err = tools.checking();
local web,err = tools.web301();