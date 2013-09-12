## CiviCRM Upgrade Test Suite

The civicrm-upgrade-test suite provides a set of sample databases which can
be used for testing upgrade-logic.

### Scope

To facilitate testing of many databases, the current script uses the
command-line based upgrade system (drush) and never uses the web-based UI
(civicrm/upgrade).  Therefore, it is appropriate for testing the database
manipulations. It does not currently test for:

 * Issues in the upgrader web UI (such as browser compatibility)
 * Issues with different CMS's (Joomla, WordPress, Drupal 6)
 * Issues in the civicrm.settings.php
 * Issues with setup or compatibility of PHP, MySQL, etc

### Pre-Requisites

 * Have a Unix-like environment (bash)
 * Install Drupal 7, CiviCRM, and Drush
 * Configure the username/password for a MySQL administrator in  ~/.my.cnf 

### Setup

```bash
## Checkout the repo
cd $HOME
git clone git://github.com/civicrm/civicrm-upgrade-test.git

## Create and edit a settings file
cd civicrm-upgrade-test
cp civicrm-upgrade-test.settings.txt civicrm-upgrade-test.settings
vi civicrm-upgrade-test.settings
## Note: The file will include comments on the configuration options
```

### Running Test Cases

```bash
## Run the script with a single database
bash civicrm-upgrade-test databases/4.2.0-setupsh.sql.bz2

## Run the script with all databases
bash civicrm-upgrade-test databases/*.sql.bz2

## Run the script with any databases based on CiviCRM 4.0.x or 4.1.x
bash civicrm-upgrade-test databases/{4.0,4.1}*.sql.bz2
```

After executing any of the above commands, output will be written to the
civicrm-upgrade-test/output directory. Examine these files to identify errors.

### Creating Test Cases

To create a new test-case, one can take any CiviCRM database and dump it
to a file -- as long as the CiviCRM database is separate from the Drupal
database. By convention, any sharable databases should be stored in the
"databases" directory and should be prefixed with a CiviCRM version
number. For example:

```bash
mysqldump my_civi_db | bzip2 > databases/4.2.3-my_civi_db.sql.bz2
```

This is not strictly required. If you want to create private test-cases,
you can store them anywhere and follow your own naming convention.