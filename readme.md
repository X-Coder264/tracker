<p align="center">
<img src="https://github.com/X-Coder264/tracker/workflows/tests/badge.svg" alt="Build Status">
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

> Legacy code is any code without tests. Code without tests is bad code. It doesn't matter how well written it is; it doesn't matter how pretty or object-oriented or well-encapsulated it is. With tests, we can change the behavior of our code quickly and verifiably. Without them, we really don't know if our code is getting better or worse.
> - Michael C. Feathers

## Project requirements

PHP: `8.0+`

SQL database: `MySQL` (min. 5.7, 8.0+ recommended), `PostgreSQL` or `SQL Server`

Caching: `Redis (recommended)` or `Memcached`

## Supported BEPs

| BEP | Name | Link |
| --- | --- | --- |
| 3  | `The BitTorrent Protocol Specification`    | http://www.bittorrent.org/beps/bep_0003.html |
| 7  | `IPv6 Tracker Extension`                   | http://www.bittorrent.org/beps/bep_0007.html |
| 23 | `Tracker Returns Compact Peer Lists`       | http://www.bittorrent.org/beps/bep_0023.html |
| 31 | `Failure Retry Extension`                  | http://www.bittorrent.org/beps/bep_0031.html |
| 36 | `Torrent RSS feeds`                        | http://www.bittorrent.org/beps/bep_0036.html |
| 48 | `Tracker Protocol Extension: Scrape`       | http://www.bittorrent.org/beps/bep_0048.html |
| 52 | `The BitTorrent Protocol Specification v2` | http://www.bittorrent.org/beps/bep_0052.html |

## Docker setup for local development

Docker 18.06.0+

Docker-Compose 1.22.0+

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
