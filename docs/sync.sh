#!/bin/bash

function menu ()
{
 cat << EOF
Please enter the digital operation, Please set automatically save the password: "git config --local credential.helper store" "git config --global credential.helper store"
`echo "[1]git sync"`
`echo "[2]exit"`
EOF
read -p "Please enter the operation: " num1
case $num1 in
 1)
  echo "git sync"
  menuGit
  ;;
 2)
  exit 0
esac
}

url=""
username=""
password=""
branch="master"
dir=""
delete=1

function getUrl() {
  read -p "Git http/Https url: " str
  url=$str
  if [ "$url" = "" ]; then
    echo 'Input error'
    getUrl
  fi

#  read -p "Git username: " str
#  username=$str
#  if [ "$username" = "" ]; then
#    echo 'Input error'
#    getUrl
#  fi
#
#  read -p "Git password: " str
#  password=$str
#  if [ "$password" = "" ]; then
#    echo 'Input error'
#    getUrl
#  fi

  read -p "Branch name: " str
  branch=$str
  if [ "$branch" = "" ]; then
    echo 'Input error'
    getUrl
  fi
}

function getDir() {
  read -p "Sync path: " str
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
  echo "git clone -b ${branch} ${url} ${dir}"
  git clone -b ${branch} ${url} ${dir}
  cd ${dir}
  git submodule update --init --recursive
  chmod -R 777 ${dir}
  echo "The following code into the webhook"
cat << EOF
echo "=========================="
echo "start update git"
echo "time: `date ' %Y-%m-%d %H:%M:%S'`"
cd ${dir}
git checkout ${branch}
git reset --hard origin/${branch}
git fetch --all
git pull
git submodule update --init --recursive
git submodule foreach git checkout master
git submodule foreach git reset --hard origin/master
git submodule foreach git fetch --all
git submodule foreach git pull
echo "update complete"
chmod -R 777 ${dir}
echo "=========================="
EOF
}
menu
