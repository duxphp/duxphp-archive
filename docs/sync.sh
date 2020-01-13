#!/bin/bash

function menu ()
{
 cat << EOF
Please enter the digital operation
`echo "[1]to obtain the public key"`
`echo "[2]git sync(Please set up the public key)"`
EOF
read -p "Please enter the operation：" num1
case $num1 in
 1)
  echo "to obtain the public key"
  menuRsa
  ;;
 2)
  echo "git sync"
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
  read -p "Git SSH url：" str
  url=$str
  if [ "$url" = "" ]; then
    echo 'Input error'
    getUrl
  fi

  read -p "Branch name：" str
  branch=$str
  if [ "$branch" = "" ]; then
    echo 'Input error'
    getUrl
  fi
}

function getDir() {
  read -p "Sync path：" str
  dir=$str
  if [ "$dir" = "" ]; then
    echo 'Input error'
    getDir
  fi
}

function getDelete() {
  if [ ! -d $dir ]; then
    echo "Please create sync path"
    exit 0
  fi
  if [ "`ls -A ${dir}`" = "" ];
  then
    echo "Sync data..."
  else
    echo "Please clear the sync path file"
    exit 0
  fi
}

#设置同步
function menuGit() {
  getUrl
  getDir
  getDelete
  git clone -b $branch $getUrl $getDir
  echo "The following code into the webhook"
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