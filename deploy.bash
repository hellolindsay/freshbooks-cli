#! /bin/bash
composer install
chmod +x "`pwd`/freshbooks"
ln -s "`pwd`/freshbooks" "/usr/local/bin/freshbooks"
