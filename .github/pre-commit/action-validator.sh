#!/bin/bash

npm version > /dev/null || (echo "npm not installed; please install npm." && exit 1)

function _install() {
   package=$1
   if [ `npm list --location=global | grep -c $package` -eq 0 ]; then
      echo "Installing $package"
      npm install --location=global $package
   fi
}

_install '@action-validator/core'
_install '@action-validator/cli'

for file in $@
do
   echo "File: ${file}"
   errors=$(action-validator $file 2>&1)
   if [ ! -z "$errors" ]; then
      echo "${errors}"
      exit 1
   fi
done
