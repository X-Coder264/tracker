<p align="center">
<a href="https://travis-ci.org/X-Coder264/tracker">
<img src="https://api.travis-ci.org/X-Coder264/tracker.svg" alt="Build Status">
</a>
<a href="https://codecov.io/gh/X-Coder264/tracker">
  <img src="https://codecov.io/gh/X-Coder264/tracker/branch/master/graph/badge.svg" />
</a>
<a href="https://styleci.io/repos/103667786">
<img src="https://styleci.io/repos/103667786/shield?branch=master" alt="StyleCI">
</a>
<a href="https://github.com/X-Coder264/tracker">
<img src="https://img.shields.io/badge/License-MIT-green.svg" alt="MIT">
</a>
</p>

## About LaraTracker

LaraTracker is a BitTorrent tracker TDD app written using Laravel.

## Docker setup for local development

0. Clone the project (note the `--recursive` flag)
    ```bash
    git clone --recursive git@github.com:X-Coder264/tracker.git
    ```
    (if you did not run clone with `--resursive` then run `git submodule update --init docker/.dokman`) 

0. Install the project
    ```bash
    docker/install dev -v
    ```
0. Add `tracker.loc mail.tracker.loc` pointing to `127.0.0.1` to your `/etc/hosts` file.

0. You can now access the project on `tracker.loc:8888` and Mailhog on `mail.tracker.loc:8888`. If you wish to change the default port create
a new `.env` file in the docker folder and change the `NGINX_HTTP_PORT` to whatever port you want.

**Note:** All default values can be seen in the same docker folder in the **.env.dist** file.
After changing any of these settings you'll need to rebuild your Docker images with `docker/env dev build`.

The `docker/install dev` command will automatically up your Docker containers, but you can up and down them later using these commands:

```bash
docker/env dev on
```

and

```bash
docker/env dev off
```

You can restart all containers in the dev environment with this command:

```bash
docker/env dev restart
```

You can enter a specific container using (example for the PHP container):

```bash
docker/enter dev:php
```

If you need to inspect the logs you can use the following commands:

```bash
docker/env dev logs php
docker/env dev logs nginx
```

If you want to for example run `composer install` in the `dev` environment on a fresh container you can use this command:

```bash
docker/run dev:php composer install
```

After that the freshly created container will be removed.

These are just the basics of what Dokman can do, for other features please check Dokman's documentation
[here](https://github.com/robier/dokman).

By default you can use Xdebug on the project. All you have to do is configure it in PHPStorm.
Put a breakpoint in some file where you want to debug and the first time you do this PHPStorm
will tell you that the remote file path is not mapped to any file path in the project. You should map
your local project folder to `/app` (in the `Absolute path on the server` column).
This must be mapped because the paths in the Docker container and the paths on the local machine do not match.

You can also debug CLI scripts, for example:

```bash
docker/enter dev:php xdebug ./artisan peers:delete
```

If you want to run CLI scripts without debugging just ommit `xdebug` from the command.

At the moment there is only the `dev` environment in the Docker setup (as it can be seen in the `docker/environments` folder), but new environments can
be easily added in the future.

## Contributing

Thank you for considering contributing to LaraTracker! If you want to improve the project
please consider sending a PR.

Every new feature or bug must have integration or unit tests that prove that the feature you
made works or that the bug you found is actually fixed.

## License

LaraTracker is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT).
