# Paraglide

## Introduction

Paraglide is a minimalist Model-View-Controller (MVC) framework for PHP5.

Models are handled by Paragon, an Object Relational Mapper (ORM).  
Views are handled by PHP itself, and rendered with a simple method from a controller which passes variables.  
Controllers are regular classes that follow a defined convention for naming methods.

Paraglide handles routing, hooks, configuration, URLs, and some helper methods.  
Paraglide provides conventions for keeping your application organized.

## Installation

When using Paraglide, we recommend downloading, not cloning.  
By cloning Paraglide, you inherit the git history and are modifying Paraglide.  
By downloading Paraglide, you can start fresh with a new git history for your project.

Download a zip file of the skeleton project

    $ wget --no-check-certificate https://github.com/bgoldman/Paraglide/zipball/master -O my-project.zip

Unzip the project

    $ unzip my-project.zip

This will unzip the project into a directory named something like bgoldman-Paraglide-760787b  
Rename the project directory to my-project (or whatever your project is called)

    $ mv bgoldman-Paraglide-760787b my-project

Move into the project directory

    $ cd my-project

Init a new git repo, add all the files, and commit

    $ git init
    $ git add .
    $ git commit -m 'first commit'

Edit configuration files:  
config/app.cfg - Specify default controller, and dev/live environments (you can also add other environments)  
config/company.cfg - Specify company name, and you can add other variables here too  
config/database.cfg - Specify database credentials for dev and live  
config/mail.cfg - Specify mail params

If you're not using MySQL or another database, you can ignore database.cfg  
Comment out or delete the database connection code in lib/hooks.php  
Delete database.cfg as well

Now we're ready to install Paragon, the official ORM of Paraglide  
If you don't want to use Paragon, delete or uncomment the lines in lib/hooks.php  
Move into the paragon directory

    $ cd lib/classes/paragon

Download a zip file of Paragon

    $ wget --no-check-certificate https://github.com/bgoldman/Paragon/zipball/master -O paragon.zip

Unzip Paragon

    $ unzip paragon.zip

This will unzip Paragon into a directory named something like bgoldman-Paragon-2775825  
Move all the files inside this directory into the current directory

    $ mv -rf bgoldman-Paragon-2775825/* .

Remove the empty bgoldman-Paragon-2775825 directory and the paragon zip file

    $ rm -r bgoldman-Paragon-2775825 paragon.zip

Now you need to make the public directory in your new project web-accessible  
If you're not putting your project in the web root, you can ignore the next set of instructions (however, this is not recommended)  
If you're using Apache, here are two options:

1) Edit your httpd.conf file, vhost file, or other conf file to make my-project/public your DocumentRoot

2) Symlink your existing document root to my-project/public  
This is my preferred way because it leaves the existing web server configuration intact  
Many web servers use the public_html or www directories as your document root  
All you need to do is remove these directories (or rename them), and then do this to symlink to your project's public directory

    $ ln -s my-project/public/ public_html

Make sure public/.htaccess is there  
If it's not, create one and copy the contents of the .htaccess file on github into it:  
https://github.com/bgoldman/Paraglide/blob/master/public/.htaccess  
Point your browser to your domain to confirm it's working  
You shouldn't be receiving any error messages at this point in time  
If you are, please contact me, Brandon Goldman, at brandon dot goldman at gmail dot com

Now you can build out your models, views, and controllers  
If you're using models, be sure to create a models directory  
The views and controllers directory already exists

## Directory Structure

/ - Project root  
/config - All config files  
/config/local - Any config files which override the main config files  
/controllers - All controllers (should usually be named plural with a _controller suffix, like items_controller.php, widgets_controller.php, etc)  
/lib - Base directory for other lib directories, and hooks.php  
/lib/apis - Facebook, Twitter, Amazon, whatever APIs you have  
/lib/classes - Classes like Paragon, whatever mailer class you use (we like Swift Mailer), etc  
/lib/helpers - Helpers like the form helper, html table helper, mailer helper, etc  
/models - All models (should usually be named singular with no suffix, like item.php, widget.php, etc)  
/public - This is your document root  
/public/images - All images  
/public/scripts - All Javascript files  
/public/styles - All CSS files  
/views - All directories for views that belong to controllers, and files like layout.tpl  
/views/common - All common views that are re-used throughout the project, like pagination  
/views/$controller - Each controller gets its own views directory for controller-specific views

## Support

Please use github's issue tracker to submit support requests  
You can also email the developer at brandon dot goldman at gmail dot com
