===========================================================
Backend Generator by Yogistra Anderson(yogistra@yahoo.com)
===========================================================

This project is one of the tools, I have used time and again to get my LAMP applications started quickly.

Input: an XML file describing the tables in your application.
	

It generates:

1. A "create tables" SQL script to create the tables described.
2. A "logging" SQL script to generate a logging(audit) table and the triggers required to get data into the logging table.
3. A "load" SQL script to preload lookup tables with preliminary data.
4. A PHP file of DB_Table sub-classes for accessing the database.

Notes: you need to manually create your create database script('create database app_test;')
Notes: DB_APP and DB_TABLE classes provide parameter binding and other security measures built-in to protect against
SQL-Injection attacks.


Limitations:
	No accomodation for foreign keys or table relationships.
	
Check out the example directory to get a better idea, start with 
	* gen_dbase.bat
After generation and creation of the backend, try test.php

When testing make sure the includes are in your include path:

	require_once('DB_APP.php');
	require_once('DB_TABLE2.php');
	require_once('gen_output\myapp_db_objs.php');
	


