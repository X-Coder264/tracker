#!/usr/bin/env bash

readonly hosts=(
    tracker.loc
    mail.tracker.loc
)

function dokmanInstall
{
    dokmanValidateHosts "${hosts[@]}"

    dokmanRunCommand "rm -f ./.env" "Removing .env file (if it exists)"
    dokmanRunCommand "rm -f ./phpunit.xml" "Removing phpunit.xml file (if it exists)"

    if isOsX; then
        title "OSX specifics..."

        if [ ! -f "./docker/.env" ] || ! grep -qx "DOKMAN_HOST_IP=host.docker.internal" ./docker/.env; then
            dokmanRunCommand "echo DOKMAN_HOST_IP=host.docker.internal >> ./docker/.env" "Adding DOKMAN_HOST_IP to docker/.env"
        fi
    fi
}
