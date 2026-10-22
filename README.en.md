# Symfony Starter Kit

Symfony 7.4 starter: a web app (front + backoffice) and a JWT API, running in Docker.

## Stack

- Symfony 7.4, PHP 8.5, Doctrine ORM, Twig
- PostgreSQL 18 and pgAdmin
- Webpack Encore, Tailwind CSS, pnpm (run in Docker, nothing to install locally)
- REST API secured with JWT (`lexik/jwt-authentication-bundle`), DTOs and Object Mapper
- Nginx, Docker Compose, Makefile

## Requirements

Docker, Docker Compose and `make`.

## Installation

### 1. Start the containers

```bash
docker compose up -d --build
```

### 2. Environment variables

Default values live in `app/.env`. Secrets go in `app/.env.local`, which is not committed:

```bash
docker compose exec php php -r 'echo bin2hex(random_bytes(32)), PHP_EOL;'   # or: openssl rand -hex 32
```

Create `app/.env.local` with the result:

```dotenv
JWT_SECRET=paste_the_generated_secret_here
```

`DATABASE_URL` must point to the `postgres` service (not `localhost`):

```dotenv
DATABASE_URL="postgresql://postgres:postgres@postgres:5432/symf_starter?serverVersion=18&charset=utf8"
```

### 3. PHP dependencies

```bash
make install
```

### 4. Database

The `symf_starter` database is created automatically the first time the `postgres` container starts. You only need to create the tables by running the migrations:

```bash
docker compose exec php php bin/console doctrine:migrations:migrate -n
```

### 5. First administrator

```bash
docker compose exec php php bin/console app:create-admin
```

Account created:

| Email | Password |
|---|---|
| `admin@example.com` | `123_admin` |

If the admin already exists, the command does nothing.

### 6. Front end (CSS and JS)

```bash
make front    # one-off build
make watch    # rebuild on every change (Ctrl+C to stop)
```

Run the build once, then again whenever you add Tailwind classes to the templates (or keep `make watch` running while you develop).

### 7. pgAdmin (optional)

pgAdmin lets you browse the database. It knows no server at first: open `http://localhost:5050`, sign in with `admin@example.com` / `admin`, then right-click **Servers** > **New** > **Server**:

| Field | Value |
|---|---|
| Name | `symf_starter` |
| Host name/address | `postgres` |
| Port | `5432` |
| Username | `postgres` |
| Password | `postgres` |

The host is `postgres` (the Docker service name), because pgAdmin runs in the same Docker network.

## Access

| Service | URL |
|---|---|
| Website | http://localhost:8080 |
| Sign in | http://localhost:8080/login |
| pgAdmin | http://localhost:5050 |
| PostgreSQL | `localhost:5432` |

## Useful commands

```bash
docker compose exec php php bin/console make:migration              # generate a migration
docker compose exec php php bin/console doctrine:migrations:migrate # apply it
docker compose exec php php bin/console cache:clear
docker compose exec php php bin/console lint:container              # check the configuration
docker compose exec php php bin/console debug:router                # list the routes
```

To start again from an empty database:

```bash
docker compose exec php php bin/console doctrine:schema:drop --full-database --force
docker compose exec php php bin/console doctrine:migrations:migrate -n
docker compose exec php php bin/console app:create-admin
```

### Test data (fixtures)

```bash
docker compose exec php php bin/console doctrine:fixtures:load -n --env=test
```

Warning: this command **empties the database before inserting**. Always keep `--env=test`, otherwise it wipes your development database.

Accounts created (shared password: `password123`):

| Email | Role |
|---|---|
| `admin01@example.com` | admin |
| `operator01@example.com` | operator |
| `user01@example.com` | user |

Reload the fixtures after every change in `src/DataFixtures/`.

### Running the tests

```bash
make tests                                                                    # all tests
docker compose exec php vendor/bin/phpunit tests/Unit                         # unit tests
docker compose exec php vendor/bin/phpunit tests/Functional/Controller/Api    # one folder
```

### Layout

```
tests/
├── Unit/                          # mirrors src/: classes tested on their own (mapper, ...)
└── Functional/
    ├── AuthenticatedClientTrait.php   # createClientLoggedInAs('admin01@example.com')
    └── Controller/                # mirrors src/Controller/
        ├── SecurityControllerTest.php  # 
        ├── Api/                   # JSON responses, tokens
        ├── Backoffice/            # admin pages
        └── Front/                 # public pages, login, profile
```

- **Unit tests**: no database, no Symfony kernel.
- **Functional tests**: they simulate HTTP requests with `WebTestCase`. To be signed in, `createClientLoggedInAs()` uses one of the accounts above, without going through the login form.

### Isolation

`dama/doctrine-test-bundle` runs each test in a transaction that is rolled back at the end: a test can create or modify data and leave no trace. Only the fixtures loaded before the tests remain in the database. Identifier counters are not reset, so the ids of the test accounts grow with each reload: a test must find a user by email, never by a hard-coded id.

## API

Routes are prefixed with `/api`. Errors are returned as JSON.

**Sign in**: returns a token valid for 12 hours.

```bash
curl -X POST http://localhost:8080/api/auth \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@example.com","password":"123_admin"}'
```

```json
{ "access_token": "..." }
```

**Authenticated calls**: send the token in the `Authorization` header.

```bash
curl http://localhost:8080/api/users/profile \
  -H "Authorization: Bearer YOUR_TOKEN"
```

| Method | Route | Access |
|---|---|---|
| `POST` | `/api/auth` | public |
| `GET` | `/api/users/profile` | signed-in user |
| `GET`, `POST` | `/api/admin/users` | admin |
| `GET`, `PATCH`, `DELETE` | `/api/admin/users/{id}` | admin |

Deletion is a soft delete (the `deletedAt` field is set and the account is deactivated).

## Structure

```
.
├── app/                    # Symfony application
│   ├── assets/             # Webpack entries (front, admin), JS and CSS
│   ├── src/Controller/     # Front/, Backoffice/, Api/
│   ├── src/Dto/            # API input and output DTOs
│   ├── src/Security/       # UserChecker (blocks inactive accounts)
│   └── templates/          # frontoffice/, backoffice/, security/
├── docker/                 # Dockerfiles (php, nginx, node)
├── docker-compose.yml
└── Makefile
```

## Before going to production

- Replace `JWT_SECRET` with a generated secret specific to the environment. With the default secret, anyone can forge an admin token.
- Change the admin password (`123_admin`) or delete the account.
- Change the PostgreSQL and pgAdmin credentials in `docker-compose.yml`, and do not expose ports `5432` and `5050`.
- Run `make front` to build the production CSS and JS.
