#!/bin/bash

function menu ()
{
 cat << EOF
请输入操作数字
`echo "[1]生成公钥"`
`echo "[2]GIT同步(请先设置仓库公钥)"`
EOF
read -p "请输入对操作的数字：" num1
case $num1 in
 1)
  echo "生成公钥"
  menuRsa
  ;;
 2)
  echo "GIT同步"
  menuGit
  ;;
 3)
  clear
  menu
  ;;
 4)
  exit 0
esac
}

#生成公钥
function menuRsa() {
  ssh-keygen -t rsa -C "" -f ~/.ssh/id_rsa
  while read line
  do
    echo '=================pub start=================='
    echo $line
    echo '=================pub end=================='
  done < ~/.ssh/id_rsa.pub
}

url=""
branch="master"
dir=""
delete=1

function getUrl() {
  read -p "请输入仓库SSH地址：" str
  url=$str
  if [ "$url" = "" ]; then
    echo '输入错误，请重新输入'
    getUrl
  fi

  read -p "请输入仓库分支名：" str
  branch=$str
  if [ "$branch" = "" ]; then
    echo '输入错误，请重新输入'
    getUrl
  fi
}

function getDir() {
  read -p "请输入同步路径：" str
  dir=$str
  if [ "$dir" = "" ]; then
    echo '输入错误，请重新输入'
    getDir
  fi
}

function getDelete() {
  if [ ! -d $dir ]; then
    echo "请手动创建同步路径"
    exit 0
  fi
  if [ "`ls -A ${dir}`" = "" ];
  then
    echo "目录为空，执行同步中..."
  else
    echo "请手动清空同步路径下文件"
    exit 0
  fi
}

#设置同步
function menuGit() {
  getUrl
  getDir
  getDelete
  git clone -b $branch $getUrl $getDir
  echo "请将以下代码放入webhook脚本中"
cat << EOF
echo "=========================="
echo "start update git"
echo "time: date ' %Y-%m-%d %H:%M:%S'"
cd ${dir}
git fetch --all
git reset --hard origin/${branch}
echo "update complete"
chmod -R 777 ${dir}
echo "=========================="
EOF
}
menu