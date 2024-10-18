# Project overview

Use it guide to build a web application called Task Management System. Where admin can assign task to user and user can submit and undo the task and can see the task status.
create this project using PHP, MySQL, HTML, CSS, Javascript, TailwindCSS and import the font and icon from google font and font awesome.


!!important
add hover effect to the page.
add pop up modal to the page.
add sweet alert to the page.
the web application should only use 100% of the screen size.

seperate dashboard for admin and user.

#Admin side function and navigation bar (username: admin, password: rndadmin **do not hash the password**)
1. Dashboard
    - Admin has a dashboard where he can see the number of task finished, pending and in-progress and total number of task and total number of user.
    - Dash board also has a donut graph to see the task status over time. 
    - Admin can see all the task in the task management page. 
    - admin can search by name of user, name of task. 
    - admin can also filter by status, priority and deadline.
    - the table should have the following columns: name of user, task name, status, priority, date created, deadline, and action button for update and delete.
    - forms when updating and adding task should have the following fields: name of user(dropdown), task name, status (pending, done), priority (low, medium, high), deadline(date picker).
    - if  the table is empty, admin can see the message "No task found" and if the table is full add next page button and previous page button.
    - if the admin update the status to "done", the task will be moved to the done finished task page.
2. Task Management
    - When Adding, updating and deleting task, the number of task finished, pending and in-progress and total number of task and total number of user should be updated.
    - No task found message should be shown in the center of the page if the table is empty.
    - Admin can add new task to the task management page. The form contains name of user(dropdown), task name, set the status automatically to "pending", priority (low, medium, high), deadline(datepicker).
3. Finished Task Management
    - Finished task page is the same as task management page, but the status is "done".
    - The table should have the following columns: name of user, task name, status, priority, date created, deadline, and action button for undo and delete.
    - if table is empty, user can see the message "No finished task found" and if the table is full add next page button and previous page button.
4. User Management
    - user management page has a table contains First name, last name, email, phone number and birth date, action button for update and delete.
    - admin can add, update and delete user.
    - when adding and deleting user, the number of total user should be updated.
    - default username and password for user is username = email and password = last name.
    - if the table is empty, admin can see the message "No user found" and if the table is full add next page button and previous page button.
5. Logout

*note: If admin update the status of task, the user dashboard should be updated too.

#User side functio and navigation bar.
1. Dashboard
    - User dashboard has a welcome message with the first name of user and the number of pending, in progress.
    - Dash board also has a donut graph to see the task status over time. 
2. Task Management
    - task management page has a table contains name of task, status, priority, deadline, action button for submit and undo.
    - if clicked submit, the task status will change to in-progress. Update the number of pending and in-progress.
    - if clicked undo, the task status will change from in-progress to pending. Update the number of pending and in-progress.
    - if the table is empty, user can see the message "No task found" and if the table is full add next page button and previous page button.
3. Finished Task Management
    - User can see the finished task in the finished task page. This page has a table contains name of task, status, priority, deadline.
4. Update Profile
    - update profile of user. The form contains first name, last name, email, phone number and birth date(date picker).
    - if user update email and last name, the username and password will be updated too.
4. Logout

# login page
1. login page has a form contains username and password.
2. registration page has a form contains first name, last name, email, phone number and birth date(date picker).
3. forgot password page has a form contains email.
    - send email to the user with a link to reset password.

#database structure
1. create a database called task_management_system.
2. create a table called accounts for admin and username and password.
    - username and password is not case sensitive and not hashed.
3. create a table called users for users
4. create a table called tasks for tasks
5. create a table called finished_tasks

