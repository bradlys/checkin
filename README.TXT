Howdy. The web app that I've added onto github is not even in alpha stages. At this point, I would say it's in a sketchbook.
Therefore, best practices of any kind are thrown out the window because I'm just jotting down ideas.
So, when you view the code and you're thinking of setting up this app, keep that in mind.

checkinapp.sql is an example database that has the schema and some included check-ins, organizations, and events.
I would import that into a new database and then fill-in the proper information in backend/settings.php.

Outside of that, you might be able to view an example of this running up at https://students.washington.edu/bradlys/ (But it will likely vaporize at any moment since I am no longer a student)


A few noticeable things to do for check-in app:

Overall structure list:
1. Add user roles and, at minimum, basic auth. Ideally, a full fledged login system with multiple user role types. (Admin can do it all. Analyst can view information to analyze stats but cannot modify or check-in anyone. Doorman can add new people, check them in, check them out, and modify current users. Organizers can do what a doorman can do but also edit and create events.)
2. Add a break-down page with stats about the whole organization and many statistics about turnout and profitability. This would be D3.js intensive.
3. Modify about and contact pages to be dynamic for each organization.
4.

Events page:
1. Add date option for when the events occur.
2. Add automatic event creation for reoccuring events.
3. Add dropdown option in modal for a quick overview of the event with nice stats. (Number of newcomers, users who have attended more than 4 times, how many people have checked-in, cash flow for the event, cost of the event, current balance, how this stacks up to other events (in terms like: It's 9:15PM, and 45 people have checked in for a revenue sream of $315. This is 43% over the average for this time of night and statistically speaking there is only a 5% chance this would happen based off previous stats.), etc.)
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