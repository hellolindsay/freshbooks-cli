Freshbooks CLI
==============

Deploy
------

To deploy this CLI. Run the deploy script:

```
chmod +x deploy
./deploy
```

The deploy script itself is quite short, and simply
symlinks the `freshbooks` script into the /usr/local/bin
folder.

``` [[ deploy ]]
#! /bin/bash
composer install
chmod +x "`pwd`/freshbooks"
ln -s "`pwd`/freshbooks" "/usr/local/bin/freshbooks"
```
