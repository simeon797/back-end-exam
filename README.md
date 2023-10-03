# FFW Academy: Back-end exam

This is a template project for the Back-end exam in the FFW academy. It enables running a Drupal development
environment on Github Codespaces as well as locally.


## Development environment setup

* Fork the repository in your personal GitHub account
* Open the project in Codespaces
* Open the terminal
* Start the Drupal installation: `drush si` - specify **sqlite** as Database driver!
* Optional: Change the admin password: `drush upwd admin <password>`
* Open `web/sites/default/settings.php`
* Update the value of the `$settings['config_sync_directory']` to `../config/default`, e.g.: `$settings['config_sync_directory'] = '../config/default';`
* Export the initial configurations: `drush cex`
* Commit & push the initial setup.
* Run the development server: `drush serve`


## Assignment

Good luck with the [Assignment](./ASSIGNMENT.md)!

