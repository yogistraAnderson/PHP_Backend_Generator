#Note convert to linux shell script, remember to change your slashes

php ..\php_autogen_utils\xml_to_sql.php < app.xml > gen_output\create_tables.sql
php ..\php_autogen_utils\xml_to_tblSingleton.php < app.xml > gen_output\myapp_db_objs.php
php ..\php_autogen_utils\xml_to_preload.php < app.xml >  gen_output\preload_data.sql
php ..\php_autogen_utils\xml_to_logging.php < app.xml >  gen_output\logging.sql

#uncomment when ready to create in the database
#change the password to match yours 
#mysql -u root --password=admin < ..\create_database.sql
#mysql -u root --password=admin app_test < gen_output\create_tables.sql
#mysql -u root --password=admin app_test < gen_output\preload_data.sql
#mysql -u root --password=admin app_test < gen_output\logging.sql
