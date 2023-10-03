# FFW Academy: Back-end exam

This is a template project for the Back-end exam in the FFW academy. It enables running a Drupal development
environment on Github Codespaces as well as locally.


## Development environment setup

* Fork the repository in your personal GitHub account
* Open the project in Codespaces
* Open the terminal
* Install composer dependencies `composer install`
* Start the Drupal installation: `drush si` - specify **sqlite** as Database driver!
* Optional: Change the admin password: `drush upwd admin <password>`
* Open `web/sites/default/settings.php`
* Update the value of the `$settings['config_sync_directory']` setting to `../config/default`, e.g.: `$settings['config_sync_directory'] = '../config/default';`
* Export the initial configurations: `drush cex`
* Commit & push the initial setup.
* Run the development server: `drush serve`

## Development execution
* At least one commit per user story is needed
* Configurations should be exported and committed as part of each user story

## Assignment
Good luck with the [Assignment](https://docs.google.com/document/d/1ygWDwnFDOckER3uYDinj3YWZIWDicvu5os-LDCcaGo4/edit?usp=sharing)!

