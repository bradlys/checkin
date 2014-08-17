Howdy. The web app that I've added onto github is not even in alpha stages. At this point, I would say it's in a sketchbook.
Therefore, best practices of any kind are thrown out the window because I'm just jotting down ideas.
So, when you view the code and you're thinking of setting up this app, keep that in mind.

checkinapp.sql is an example database that has the schema and some included check-ins, organizations, and events.
I would import that into a new database and then fill-in the proper information in backend/settings.php.

Outside of that, you might be able to view an example of this running up at bradly.me/checkin/

A few noticeable things to do for check-in app:

Overall structure list:
1. Add user roles and, at minimum, basic auth. Ideally, a full fledged login system with multiple user role types. (Admin can do it all. Analyst can view information to analyze stats but cannot modify or check-in anyone. Doorman can add new people, check them in, check them out, and modify current users. Organizers can do what a doorman can do but also edit and create events.)
2. Add a break-down page with stats about the whole organization and many statistics about turnout and profitability. This would be D3.js intensive.
3. Modify about and contact pages to be dynamic for each organization.
4.

Events page:
1. Add date option for when the events occur.
2. Add automatic event creation for reoccuring events.
3. Add dropdown option in modal for a quick overview of the event with nice stats. (Number of newcomers, users who have attended more than 4 times, how many people have checked-in, cash flow for the event, cost of the event, current balance, how this stacks up to other events (in terms like: It's 9:15PM, and 45 people have checked in for a revenue stream of $315. This is 43% over the average for this time of night and statistically speaking there is only a 5% chance this would happen based off previous stats.), etc.)
4. Add costs forms for various staff members and whether they've been paid or not. Venue cost, etc.
5.

Check-in users page:
1. Add option in database or settings.php for birthday's of the users.
2. Quick stats on when the user's previous check-ins, how many times, and how much they've contributed total.
3. Add options for automatic free entrance for their next check-in based upon giving e-mail address, birthday week, or just a tick box for "next check-in is free".
4. 

Organizations page:

1. Editing of organization information and content in modal. (Includes things like organizers, editing users permissions, and so forth)
2. Dropdown option in modal for a quick overview of organization with accessible stats.
3. 


Plan of Attack:

Overall structure list:
1. Start with basic auth. Create an appropriate .htaccess file and .htpasswd file. (First users: demo (for showing off the program with a demo organization), admin, test) Create new table called users. Also create userPermissions table with schema (id (unique auto-increment integer), user_id (user.id), organization_id (organzations.id), role (analyst, admin, user, doorman, etc.), status (on/off)). The idea for that being that each user has various permissions assigned to them per organization. (User allows you to even know that the organization exists from the organization select area (but nothing else); doorman allows to see the events, create new customers, edit existing customers, and check-in customers; analyst allows for viewing organization, events, and customer statistics; organizer allows for editing the organization information and creating/editing events; admin is an override for all permissions) Add backend/permissions.php that gets roles for users, verifies user has the required role (requireRole($role)), fetches users and roles, editing permissions functions, etc. Add a page or area on the organization section to modify user roles for each user that is assigned/has-permission to that organization. Possible thought is that you need a special role to view certain events. (use UserAttributes with (id, user_id, name, value, status) where (id, user.id, SpecialEventPermission, event_id, on) thus allows for some user to view an event that requires a special permission to view. Maybe you have a secret event that you don't want most doormen, analysts, or organizers to see. (Eyes Wide Shut, anyone?))
2. Feel like this would be difficult to contain in a modal. Add new link in nav at top for statistics breakdown of the thing you're currently viewing. At page for all events? Nav to see organization stats breakdown. At page for checking-in customers? Add link to breakdown of statistics for the event you're viewing. Could also add some simple non-D3 stats in organization and event modal. Things to do before implementing: Figure out what statistics I need to display. Look for implementations that would display those statistics nicely. Get some feedback.
3. What would an about page hold? Small information about the organization, relevant links, possibly images of important people (Or maybe put that in the contact section/both?). This would also hold a link to or information about the check-in app itself (how-to, what it's for). Contact section would have a form to contact an organizer and the administrator OR would contain phone numbers,e-mail addresses, or what not.
4. 

Events page:
1. Add date option for events: Add a column in the DB for events table that is date specific (and is not the timestamp column that exists. That would be for when the entry was created). Add bootstrap-datepicker (Apache License 2.0).
2. Cron job? Alternative is to check the database every time the events search page is loaded and see if an event exists for the current date. (If it doesn't, create it) organizationAttributes table (id (unique auto-increment integer), organization_id (organizations.id), name (string), value (string), status (on/off)) could hold that special information. Format it (id, organization.id, recurringEvent, very-special-string-with-encoded-message(JSON?), on) as such?
3. Similar to #2 in overall structure. Simplistic statistics is likely to be a bigger winner here. I think most demo stuff I've seen is too intensive and large for a modal.
4. Yep... I think this would be something suitable for an eventAttributes table. (id (unique auto-increment integer), event_id (events.id), name (string), value (string), status (on/off)) with values like (id, event.id, hosts, JSON(host1: John Smith, host2: Jane Doe), on) and/or (id, event.id, paidHosts, 75, on). The hardest part of this will be making it manageable and accessible in the modal for each event. I think it would also be useful to see statistics in the breakdown page like (when John hosts, average cash flow is X. when Jane hosts, average cash flow is Y. Jane is 26% higher than John and 32% higher than the average. Something like that? Who knows how useful that is but I think it's interesting to see.)
5.

Check-in users page:
1. Add birthday option for users: Add a column for birthday in customers table? Add a customerAttributes table that has a birthday entry and relates to the id in customers? Also use bootstrap-datepicker for this.
2. How to do this? Should I pre-load that information into the customer divs that are displayed in the search results or should I query the database upon loading of the check-in modal? (Pre-loading could be nice but is more work on the database and could be an issue when searching through a lot of customers... Every press of a key would cause about 20-40 queries to go off...)
3. This is something that could easily be added via a customerAttributes table. Managing it in the modal will be the more difficult part. (Don't want to overload the modal)
4. 

Organizations page:

1. Yep. organizationAttributes table will hold this information and will display the proper forms in the organization modal for it.
2. Dropdown will have to have send a POST request to retrieve the information. Again, need to figure out what statistics to display and how. Analytics on this stuff is not something I frequently see.
3.

