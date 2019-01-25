#!/bin/bash
# For developers only!

# get config
if [ ! -f "config.sh" ]
then
	echo "No config file! Please, create 'config.sh' file."
	exit 1
fi
source config.sh

# start PHP CodeSniffer
phpcs="$($path/vendor/bin/phpcs --standard=PSR2 application/)"
if [ ! -z "$phpcs" ]
then
  echo "PHP CodeSniffer failed! $phpcs"
  exit 1
fi

# start PHPUnit
"$path/vendor/bin/phpunit" --bootstrap "$path/bootstrap.php" tests/phpunit/application/Espo/Modules/Completeness/