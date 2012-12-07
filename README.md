The civicrm-upgrade-test suite provides a set of sample databases which can
be used for testing upgrade-logic.

### Scope

To facilitate testing of many databases, the current script uses the
command-line based upgrade system (drush) and never uses the web-based UI
(civicrm/upgrade).  Therefore, it is appropriate for testing the database
manipulations -- but it does not test the UI aspects of the upgrader (such
as browser compatibility).

### Pre-Requisites

 * Have a Unix-like environment (bash)
 * Install Drupal 7, CiviCRM, and Drush
 * Use separate databases for Drupal and CiviCRM
 * Configure the username/password for a MySQL administrator in  ~/.my.cnf 

### Setup

```bash
## Checkout the repo
cd $HOME
git clone git://github.com/totten/civicrm-upgrade-test.git

## Create and edit a settings file
cd civicrm-upgrade-test
cp civicrm-upgrade-test.settings.txt civicrm-upgrade-test.settings
vi civicrm-upgrade-test.settings
## Note: The file will include comments on the configuration options

## Run the script
bash civicrm-upgrade-test
```
