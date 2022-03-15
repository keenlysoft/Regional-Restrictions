local arg = ngx.req.get_uri_args()
local url = ngx.encode_args(arg)
if url ~= '' then
	weburl = ngx.var.uri..'?'..url;
else
	weburl = ngx.var.uri
end
--ngx.print(weburl)