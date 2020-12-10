# Ark Services

## Introduction 

Provide a user interface to mint, (bulk) bind [ARK Identifiers](https://wiki.lyrasis.org/display/ARKs/ARK+Identifiers+FAQ), and resolver for Ark URLs.

## Requirement

This application required following either one of the below environment to be setup: 
* a plain [Virtual machine](https://phoenixnap.com/kb/how-to-install-vagrant-on-ubuntu) OR [Drupal VM](https://github.com/geerlingguy/drupal-vm) to be installed.
* OR [LAMP stack](https://www.digitalocean.com/community/tutorials/how-to-install-linux-apache-mysql-php-lamp-stack-on-ubuntu-20-04) to be installed

## Installation

### [In Virtual Machine (for demo purposes)](#install-in-vm)

1. Download VM from: https://github.com/geerlingguy/drupal-vm.
2. Open terminal, and change to the drupal-vm directory `cd DIR/drupal-vm` and run `vagrant up` to have VM running.    
3. Remote access to VM by running `vagrant ssh`
4. Change directory by run `cd /var/www`, then donwload the source code by running `git clone https://github.com/digitalutsc/ark-services.git` 
5. Enable hosting for the Ark Service by running `sudo nano /etc/apache2/sites-available/vhosts.conf`, then paste the following code to the file:

````php
<VirtualHost *:80>
  ServerName ark.drupalvm.test
  ServerAlias www.ark.drupalvm.test
  DocumentRoot "/var/www/ark-services"

    <Directory "/var/www/ark-services">
        AllowOverride All
        Options -Indexes +FollowSymLinks
        Require all granted
    </Directory>
    <FilesMatch \.php$>
    SetHandler "proxy:fcgi://127.0.0.1:9000"
    </FilesMatch>

</VirtualHost>
````
5. Reload Apache by running `sudo systemctl reload apache2`
6. To create the database, visit http://adminer.drupalvm.test/index.php.
7. Login with the following info to login to manage MYSQL.
````
System: MYSQL
Server:	locahost
Username: root	
Password:root	
Database: Leave empty	
````
8. After logging successfully, create a database for the Ark Identifiers.
9. In terminal, running `sudo nano /var/www/ark-services/NoidLib/custom/MysqlArkConf.php`, and paste the following code, and set the name of the database which has been created above to `$mysql_dbname`, then save it. 
````php
<?php

namespace Noid\Lib\Custom;

class MysqlArkConf
{
    static public $mysql_host = 'localhost';
    static public $mysql_user = 'root';
    static public $mysql_passwd = 'root';
    static public $mysql_dbname = ''; // please enter the name of database which you have just created.  
    static public $mysql_port = 3306;
    static public $path_db_backup = "/var/www/ark-services/db/backup/";
}

````
10. Change the permission of the directory `db` by runnning `sudo chmod 777 /var/www/ark-services/db`
11. Go to http://ark.drupalvm.test and fill out the form in screenshot as below for initially set the system up.

![System setup form](http://ark.digital.utsc.utoronto.ca/docs/images/Screen%20Shot%202020-12-09%20at%208.33.09%20AM.png) 

12. Visit the http://ark.drupalvm.test/admin.php and login with the account which has been created in the previous step to get started.

### In an Apache server
1. Follow [this guide](https://www.digitalocean.com/community/tutorials/how-to-install-linux-apache-mysql-php-lamp-stack-on-ubuntu-20-04) to install the LAMP stack to your server. 
    * In **STEP 4**, follow exactly this step with **ark-services** instead of **your_domain**
    * Then, open the terminal, change the current directory by running `cd /var/www`,
    * then download the source code by running `git clone https://github.com/digitalutsc/ark-services.git` 
2. Follow [this guide](https://www.digitalocean.com/community/tutorials/how-to-install-and-secure-phpmyadmin-on-ubuntu-20-04) to install phpMyAdmin to your server.
3. Using phpMyAdmin to create an user, a database and then assign a user to that database with the following Privileges as screenshot below:
   
   ![mysql-account-privillege])
   

4. In terminal, running `sudo nano /var/www/ark-services/NoidLib/custom/MysqlArkConf.php`, and paste the following code, and set the name of variables by which you created in the previous step, then save it.

````php
<?php

namespace Noid\Lib\Custom;

class MysqlArkConf
{
    static public $mysql_host = ''; // host of your server
    static public $mysql_user = ''; // your MYSQL username
    static public $mysql_passwd = ''; // your MYSQL password
    static public $mysql_dbname = ''; // please enter the name of database which you have just created.  
    static public $mysql_port = 3306;
    static public $path_db_backup = "/var/www/ark-services/db/backup/"; // backup directory for database snapshot
}

````

5. Follow exactly step 10, 11, 12 in [the above section](https://github.com/digitalutsc/ark-services#install-in-vm). 

## Usage 
* Please visit full detail at https://ark.digital.utsc.utoronto.ca/docs/manual.html#Setting-up-Ark-creationbinding


   
