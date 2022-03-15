#!/bin/bash
PATH=/www/server/panel/pyenv/bin:/bin:/sbin:/usr/bin:/usr/sbin:/usr/local/bin:/usr/local/sbin:~/bin
export PATH

#配置插件安装目录
install_path=/www/server/panel/plugin/restrict

#安装
Install()
{
	
	echo '正在安装...'
	#==================================================================
	#依赖安装开始
	php $install_path/install.php
	sudo dhclient
	service nginx restart
	#jit
	#wget --no-check-certificate https://github.com/openresty/luajit2/archive/v2.1-20201008.tar.gz
	#tar -zvxf v2.1-20201008.tar.gz
	#rm -f v2.1-20201008.tar.gz
	#mv luajit2-2.1-20201008 $install_path
	#cd $install_path/luajit2-2.1-20201008
	#make && make install PREFIX=$install_path/luajit
	#export LUAJIT_LIB=$install_path/luajit/lib
	#export LUAJIT_INC=$install_path/luajit/include/luajit-2.1
	#cd $install_path
	#rm -fr luajit2-2.1-20201008
	#依赖安装结束
	#==================================================================

	echo '================================================'
	echo '安装完成'
}

#卸载
Uninstall()
{
	php $install_path/uninstall.php
	rm -rf $install_path
}

#操作判断
if [ "${1}" == 'install' ];then
	Install
elif [ "${1}" == 'uninstall' ];then
	Uninstall
else
	echo 'Error!';
fi
