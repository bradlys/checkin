# Check-In App

### Status:
Check-In App is currently abandoned. I would do more work on it but I've been consumed with other priorities. Currently, the project is in a date of disrepair and between deployment cycles. Normally, I would have branched off these things and it'd be no big deal but as the only developer I didn't bother. So while everything may technically work, there is a bunch of code in there that shouldn't be or is halfway done. Similarly, the "RESTful" nature of it isn't really 100% kosher. Normally, methods would actually use a get/put/post/update/whatever when hitting the API but I didn't cause reasons. Still, it's a valid API (cause I said so) and does the job.

## What is it?

Check-In App (CIA) is a web application used to check-in customers at events an organization holds.  

The target audience of this application is social dances. Social dances frequently don't keep track of how many customers paid for entry. The way many dances find out how many people entered the dance is through counting how much money they have in the till at the end of the night. The reason for this is that it is difficult to keep accurate physical records and to interpret the data in a meaningful way. And physical records tend to be kept as a series of tally marks to indicate how many people paid to enter under a couple columns for different entry costs. This is only slightly better than just counting the till at the end of night. I can proudly say that I've seen one dance step up their tally marks even further by marking times of the night on the tally sheet and how many tally marks were made between times. (e.g. There were 20 tally marks between 9:00-9:30) That's work and still lags behind what you could do. This is where CIA comes in, streamlines the process, and makes sensible data out of customer entries.  

## How does it work?

### Overview

Check-In App starts at the organization selection screen. There, you search for an organization through the search box or click it if it already appears. You can also create a new organization by clicking, "Add new organization". From there, you click go on the modal that pops up or edit the organization info and hit save. Once hitting go, it will take you to the events listing for that organization. There you can search for an event, edit existing events, and create a new event. Click an event, put in some costs (if you want), make sure the date is good, and hit go. That will load up the event and from there you can start checking in people. You do that by searching for their name, clicking their name, loading relevant information into the modal, and hitting check in. If you didn't do something correctly, the modal will let you know. Adding a new customer is easy as well, just click on the "Add new user" button in the search results box. If you want to check a user out then you can click on them and click check out. You can also click on the "About event" link at the top of the checkin page to view the most recent 500 check ins for that event. From there, you can click on one of the entries and it will load up a modal where you can check out the customer for that entry.

# How to setup CIA

### Requirements

CIA requires PHP 5.2+ and a MySQL database.

### Getting started

The first step is to just say, "no". CIA is currently not ready for deployment. But, I'm not going to stop you from reading on and trying anyway. The second step is to checkout the latest (hopefully) stable release of CIA. At the moment, CIA does not have a stable release that would qualify for real world production use. In the future, you will follow up with good configuration settings described below.

### Database configuration

1. Create an empty database via cPanel, phpMyAdmin, bash, or your other tool of choice. Since this app is currently designed to be used in English speaking countries with only standard English characters... I would use a collation of utf8\_general\_ci for the database and all tables. I have no responsibility with what happens when you use utf8\_unicode\_ci and fancy accented characters or what have you. :-þ
1. Import checkinapp.sql from the base checkout directory into the database. It will fill-in the structure for you. Edit the collations as necessary.

### Configuring the settings

Open up database.php (backend/database.php) in a text-editor. Fill in the username, password, database name, and host information as necessary. Save. Below is an example of what you could enter in the settings.php file. Also, set the variable (PRODUCTION_SERVER) in backend/settings.php to true or false depending on whether this is a production server.  

Example:
> define('DB\_USER', 'CoolGuyStan');  
> define('DB\_PASS', 'SuperSecretPassword');  
> define('DB\_NAME', 'CheckInAppDatabase');  
> $mysql\_access = mysql\_connect('http://CoolGuyStansWebsite.org:1234', DB\_USER, DB\_PASS, true) or die ('Unable to connect to the Database');  

### Did I say this was a work-in-progress?

I did, right? OK, just making sure. There's more you'll need to do in the future but right now that's it. Create an organization, some events for that organization, and start checking-in people. Let the good times roll!  

# Work To Be Done

I am not currently accepting work from others on this project. However, you're welcome to check the Issues page on https://github.com/Schlenkerb/checkin/issues 

