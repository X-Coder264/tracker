#!/usr/bin/env bash

title "Symlinking files..."

dokmanRunCommand "ln -s ./docker/configurations/env/dev .env" "Creating symlink for .env file..."
dokmanRunCommand "ln -s ./docker/configurations/phpunit.xml phpunit.xml" "Creating symlink for phpunit.xml file..."

title "Docker..."

dokmanRunCommand "docker/env dev on" "Building and upping Docker containers..."

title "Dependencies..."

dokmanRunCommand "docker/enter dev:php composer install -n" "Installing project PHP packages..."
