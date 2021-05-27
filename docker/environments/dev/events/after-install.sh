#!/usr/bin/env bash

title "Symlinking files..."

runCommand "ln -s ./docker/configurations/env/dev .env" "Creating symlink for .env file..."
runCommand "ln -s ./docker/configurations/phpunit.xml phpunit.xml" "Creating symlink for phpunit.xml file..."

# Git hooks
runCommand "ln -sf ../../docker/scripts/git-hooks/pre-commit .git/hooks/pre-commit"

title "Docker..."

runCommand "docker/env dev on" "Building and upping Docker containers..."

title "Dependencies..."

runCommand "docker/enter dev:php composer install -n" "Installing project PHP packages..."

runCommand "docker/enter dev:php ./artisan migrate:refresh --seed" "Migrating fresh database with some dummy data"

runCommand "docker/enter dev:php ./artisan passport:install" "Install Passport"
