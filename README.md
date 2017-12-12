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

Creating a Freshbooks App
-------------------------

To create a Freshbooks app, visit this hidden url:
https://my.freshbooks.com/#/developer

An API reference and getting started guide for the freshbooks API is here:
https://www.freshbooks.com/api/start

In order to connect this command line app to your freshbooks account, you
will to create a new Freshbooks application, authorize yourself to that
app through the browser, and then paste your client id, client secret, and
authorization code into the freshbooks config file.

. Client ID: Copy from the developers page
. Client Secret: Copy from the developers page
. Authorization Code: Copy from the address bar after authorizating your app. 

