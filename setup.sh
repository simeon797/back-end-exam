composer install
vendor/bin/drush si --db-url=sqlite://drupal:drupal@127.0.0.1:3306/drupal --yes
vendor/bin/drush upwd admin 123

chmod u+w web/sites/default/settings.php
echo '$settings["reverse_proxy"] = TRUE;' >> web/sites/default/settings.php
echo '$settings["reverse_proxy_addresses"] = ["127.0.0.1"];' >> web/sites/default/settings.php
echo '$settings["config_sync_directory"] = "../config/default";' >> web/sites/default/settings.php
echo 'export PATH="./vendor/bin:$PATH"' >> ~/.bashrc
source ~/.bashrc
