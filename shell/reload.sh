#!/bin/bash
shell_dir=$(cd "$(dirname "$0")"; pwd)
cd $shell_dir
cd ..
current_dir=`pwd`
echo '1'> $current_dir/service/config/reload
chmod 777 $current_dir/service/config/reload
