# Check-In App

## What is it?

Check-In App (CIA) is a work-in-progress (WIP) web application used to check-in customers at events an organization holds. This is similar to how a hotel would check-in customers who have no reservation. One of the things that I do frequently is social dance. Social dances frequently don't keep good track of how many customers paid for entry. In fact, the way many dances find out how many people entered the dance is through counting how much money they have in the till at the end of the night. The reason for this is that it is difficult to keep physical records and difficult to interpret the data in a meaningful way when you do keep the records. Even when they do keep physical records, they simply keep a tally of how many people paid to enter. Which is only slightly better than just counting the till at the end of night. This is where CIA comes in and makes sensible data out of customer entries. Right now, CIA is a WIP and is in active development. An active demo of CIA is viewable here at http://bradly.me/checkin/

# How to setup CIA

### Requirements

CIA requires PHP 5.2+ and a MySQL database.

### Getting started

The first step is to just say, "no". CIA is currently not ready for deployment. But, I'm not going to stop you from reading on and trying anyway. The second step is to checkout the latest (hopefully) stable release of CIA.

### Database configuration

1. Create an empty database via cPanel, phpMyAdmin, bash, or your other tool of choice. Since this app is currently designed to be used in English speaking countries with only standard English characters... I would use a collation of utf8\_general\_ci for the database and all tables. I have no responsibility with what happens when you use utf8\_unicode\_ci and fancy accented characters or what have you. :-þ
1. Import checkinapp.sql from the base checkout directory into the database. It will fill-in the structure for you. Edit the collations as necessary.

### Configuring the settings

Open up settings.php (backend/settings.php) in a text-editor. Fill in the username, password, database name, and host information as necessary. Save. Below is an example of what you could enter in the settings.php file.  

Example:
> define('DB\_USER', 'CoolGuyStan');  
> define('DB\_PASS', 'SuperSecretPassword');  
> define('DB\_NAME', 'CheckInAppDatabase');  
> $mysql\_access = mysql\_connect('http://CoolGuyStansWebsite.org:1234', DB\_USER, DB\_PASS, true) or die ('Unable to connect to the Database');  

### Did I say this was a work-in-progress?

I did, right? OK, just making sure. There's more you'll need to do in the future but right now that's it. Create an organization, some events for that organization, and start checking-in people. Let the good times roll!  

# Work To Be Done

Check the Issues page on https://github.com/Schlenkerb/checkin/issues

