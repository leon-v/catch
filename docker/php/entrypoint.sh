#!/bin/sh

cd /usr/src/app/apps/csv_db

composer install && composer clear-cache

# Oddly, we get this error when dump-autoload is used:
# ```
# Attempted to load class "MakerBundle" from namespace "Symfony\Bundle\MakerBundle".
# Did you forget a "use" statement for another namespace?
# ```
# composer dump-autoload --optimize --classmap-authoritative --no-dev


# Execute the main container command
exec "$@"