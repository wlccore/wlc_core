#!/bin/sh

# Generates .mo file from .po file. Adds timestamp to prevent caching by server. Deletes other .mo files.

# OS locale string
locale=$1
# .po filename (without extension)
domain=$2
directory="$( cd "$(dirname "$0")" ; pwd -P )"\

if [ ! -f "${directory}"/locale/"${locale}"/LC_MESSAGES/"${domain}".po ]; then
  exit $?
fi

find "${directory}"/locale/"${locale}"/LC_MESSAGES/ -name "${domain}".mo -delete
if [ -x "$(command -v gettext)" ]; then
  msgfmt "${directory}"/locale/"${locale}"/LC_MESSAGES/"${domain}".po -o ${directory}/locale/"${locale}"/LC_MESSAGES/"${domain}".mo
else
  echo "\033[37;1;41m Please install gettext (sudo apt-get install gettext) \033[0m"
  exit 1
fi
exit $?

