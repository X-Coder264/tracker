#!/usr/bin/env bash

readonly hosts=(
    tracker.loc
    mail.tracker.loc
)

function dokmanInstall
{
    validateHostEntries "${hosts[@]}"

    runCommand "rm -f ./.env" "Removing .env file (if it exists)"
    runCommand "rm -f ./phpunit.xml" "Removing phpunit.xml file (if it exists)"

    if isOsX; then
        title "OSX specifics..."

        if [ ! -f "./docker/.env" ] || ! grep -qx "DOKMAN_HOST_IP=host.docker.internal" ./docker/.env; then
            runCommand "echo DOKMAN_HOST_IP=host.docker.internal >> ./docker/.env" "Adding DOKMAN_HOST_IP to docker/.env"
        fi
    fi
}
