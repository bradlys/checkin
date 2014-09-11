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

## A few noticeable things to do for check-in app:

### Overall structure list:
1. Add user roles and, at minimum, basic auth. Ideally, a full fledged login system with multiple user role types. (Admin can do it all. Analyst can view information to analyze statistics but cannot modify or check-in anyone. Doorman can add new people, check them in, check them out, and modify current users. Organizers can do what a doorman can do but also edit and create events.)
1. Add a break-down page with statistics about the whole organization and many statistics about turnout and profitability. This would be D3.js intensive.
1. Modify about and contact pages to be dynamic for each organization.
1.  

### Events page:
1. Add date option for when the events occur.
1. Add automatic event creation for recurring events.
1. Add dropdown option in modal for a quick overview of the event with nice statistics. (Number of newcomers, users who have attended more than 4 times, how many people have checked-in, cash flow for the event, cost of the event, current balance, how this stacks up to other events (in terms like: It's 9:15PM, and 45 people have checked in for a revenue stream of $315. This is 43% over the average for this time of night and statistically speaking there is only a 5% chance this would happen based off previous statistics.), etc.)
1. Add costs forms for various staff members and whether they've been paid or not. Venue cost, etc.
1.  

### Check-in users page:
1. Add option in database or settings.php for birthday's of the users.
1. Quick statistics on when the user's previous check-ins, how many times, and how much they've contributed total.
1. Add options for automatic free entrance for their next check-in based upon giving e-mail address, birthday week, or just a tick box for "next check-in is free".
1. 

### Organizations page:

1. Editing of organization information and content in modal. (Includes things like organizers, editing users permissions, and so forth)
1. Dropdown option in modal for a quick overview of organization with accessible statistics.
1. 


## Plan of Attack:

### Overall structure list:
1. Start with basic auth. Create an appropriate .htaccess file and .htpasswd file. (First users: demo (for showing off the program with a demo organization), admin, test) Create new table called users. Also create userPermissions table with schema (id (unique auto-increment integer), user\_id (user.id), organization\_id (organzations.id), role (analyst, admin, user, doorman, etc.), status (on/off)). The idea for that being that each user has various permissions assigned to them per organization. (User allows you to even know that the organization exists from the organization select area (but nothing else); doorman allows to see the events, create new customers, edit existing customers, and check-in customers; analyst allows for viewing organization, events, and customer statistics; organizer allows for editing the organization information and creating/editing events; admin is an override for all permissions) Add backend/permissions.php that gets roles for users, verifies user has the required role (requireRole($role)), fetches users and roles, editing permissions functions, etc. Add a page or area on the organization section to modify user roles for each user that is assigned/has-permission to that organization. Possible thought is that you need a special role to view certain events. (use UserAttributes with (id, user\_id, name, value, status) where (id, user.id, SpecialEventPermission, event\_id, on) thus allows for some user to view an event that requires a special permission to view. Maybe you have a secret event that you don't want most doormen, analysts, or organizers to see. (Eyes Wide Shut, anyone?))
1. Feel like this would be difficult to contain in a modal. Add new link in nav at top for statistics breakdown of the thing you're currently viewing. At page for all events? Nav to see organization statistics breakdown. At page for checking-in customers? Add link to breakdown of statistics for the event you're viewing. Could also add some simple non-D3 statistics in organization and event modal. Things to do before implementing: Figure out what statistics I need to display. Look for implementations that would display those statistics nicely. Get some feedback.
1. What would an about page hold? Small information about the organization, relevant links, possibly images of important people (Or maybe put that in the contact section/both?). This would also hold a link to or information about the check-in app itself (how-to, what it's for). Contact section would have a form to contact an organizer and the administrator OR would contain phone numbers,e-mail addresses, or what not.
1. 

### Events page:
1. Add date option for events: Add a column in the DB for events table that is date specific (and is not the timestamp column that exists. That would be for when the entry was created). Add bootstrap-datepicker (Apache License 2.0).
1. Cron job? Alternative is to check the database every time the events search page is loaded and see if an event exists for the current date. (If it doesn't, create it) organizationAttributes table (id (unique auto-increment integer), organization\_id (organizations.id), name (string), value (string), status (on/off)) could hold that special information. Format it (id, organization.id, recurringEvent, very-special-string-with-encoded-message(JSON?), on) as such?
1. Similar to #2 in overall structure. Simplistic statistics is likely to be a bigger winner here. I think most demo stuff I've seen is too intensive and large for a modal.
1. Yep... I think this would be something suitable for an eventAttributes table. (id (unique auto-increment integer), event\_id (events.id), name (string), value (string), status (on/off)) with values like (id, event.id, hosts, JSON(host1: John Smith, host2: Jane Doe), on) and/or (id, event.id, paidHosts, 75, on). The hardest part of this will be making it manageable and accessible in the modal for each event. I think it would also be useful to see statistics in the breakdown page like (when John hosts, average cash flow is X. when Jane hosts, average cash flow is Y. Jane is 26% higher than John and 32% higher than the average. Something like that? Who knows how useful that is but I think it's interesting to see.)
1.  

### Check-in users page:
1. Add birthday option for users: Add a column for birthday in customers table? Add a customerAttributes table that has a birthday entry and relates to the id in customers? Also use bootstrap-datepicker for this.
1. How to do this? Should I pre-load that information into the customer divs that are displayed in the search results or should I query the database upon loading of the check-in modal? (Pre-loading could be nice but is more work on the database and could be an issue when searching through a lot of customers... Every press of a key would cause about 20-40 queries to go off...)
1. This is something that could easily be added via a customerAttributes table. Managing it in the modal will be the more difficult part. (Don't want to overload the modal)
1. 

### Organizations page:
1. Yep. organizationAttributes table will hold this information and will display the proper forms in the organization modal for it.
1. Dropdown will have to have send a POST request to retrieve the information. Again, need to figure out what statistics to display and how. Analytics on this stuff is not something I frequently see.
1.  

