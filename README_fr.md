# Symfony Starter Kit

Starter Symfony 7.4 : un site web (front + backoffice) et une API JWT, dans un environnement Docker.

## Stack

- Symfony 7.4, PHP 8.5, Doctrine ORM, Twig
- PostgreSQL 18 et pgAdmin
- Webpack Encore, Tailwind CSS, pnpm (exécuté dans Docker, rien à installer en local)
- API REST protégée par JWT (`lexik/jwt-authentication-bundle`), DTO et Object Mapper
- Nginx, Docker Compose, Makefile

## Prérequis

Docker, Docker Compose et `make`.

## Installation

### 1. Démarrer les containers

```bash
docker compose up -d --build
```

### 2. Variables d'environnement

Les valeurs par défaut sont dans `app/.env`. Les secrets vont dans `app/.env.local`, qui n'est pas versionné :

```bash
docker compose exec php php -r 'echo bin2hex(random_bytes(32)), PHP_EOL;'   # ou : openssl rand -hex 32
```

Crée `app/.env.local` avec le résultat :

```dotenv
JWT_SECRET=colle_ici_le_secret_genere
```

`DATABASE_URL` doit pointer vers le service `postgres` (et non `localhost`) :

```dotenv
DATABASE_URL="postgresql://postgres:postgres@postgres:5432/symf_starter?serverVersion=18&charset=utf8"
```

### 3. Dépendances

```bash
make install
```

### 4. Base de données

La base `symf_starter` est créée automatiquement au premier démarrage du container `postgres`. Il reste à créer les tables avec les migrations :

```bash
docker compose exec php php bin/console doctrine:migrations:migrate -n
```

### 5. Premier administrateur

```bash
docker compose exec php php bin/console app:create-admin
```

Compte créé :

| Email | Mot de passe |
|---|---|
| `admin@example.com` | `123_admin` |

Si l'admin existe déjà, la commande ne fait rien.

### 6. Front (CSS et JS)

```bash
make front    # build ponctuel
make watch    # recompile à chaque modification (Ctrl+C pour arrêter)
```

Le build est nécessaire une première fois, puis à chaque ajout de classes Tailwind dans les templates (ou lance `make watch` pendant que tu développes).

### 7. pgAdmin (optionnel)

pgAdmin sert à parcourir la base. Il ne connaît aucun serveur au départ : ouvre `http://localhost:5050`, connecte-toi avec `admin@example.com` / `admin`, puis clic droit sur **Servers** > **Nouveau** > **Server** :

| Champ | Valeur |
|---|---|
| Name | `symf_starter` |
| Host name/address | `postgres` |
| Port | `5432` |
| Username | `postgres` |
| Password | `postgres` |

Le host est `postgres` (le nom du service Docker), car pgAdmin tourne dans le même réseau Docker.

## Accès

| Service | URL |
|---|---|
| Site | http://localhost:8080 |
| Connexion | http://localhost:8080/login |
| pgAdmin | http://localhost:5050 |
| PostgreSQL | `localhost:5432` |

## Commandes utiles

```bash
docker compose exec php php bin/console make:migration              # générer une migration
docker compose exec php php bin/console doctrine:migrations:migrate # l'appliquer
docker compose exec php php bin/console cache:clear
docker compose exec php php bin/console lint:container              # vérifier la config
docker compose exec php php bin/console debug:router                # lister les routes
```

Pour repartir d'une base vide :

```bash
docker compose exec php php bin/console doctrine:schema:drop --full-database --force
docker compose exec php php bin/console doctrine:migrations:migrate -n
docker compose exec php php bin/console app:create-admin
```

## Tests

Les tests utilisent PHPUnit et s'exécutent dans le container PHP.

### Base de test

Les tests tournent sur leur propre base, `symf_starter_test` (Symfony ajoute le suffixe `_test` en environnement `test`), pour ne jamais toucher à ta base de développement. À créer une seule fois :

```bash
docker compose exec php php bin/console doctrine:database:create --env=test
docker compose exec php php bin/console doctrine:migrations:migrate -n --env=test
```

### Données de test (fixtures)

```bash
docker compose exec php php bin/console doctrine:fixtures:load -n --env=test
```

Attention : cette commande **vide la base avant d'insérer**. Garde toujours `--env=test`, sinon elle efface ta base de développement.

Comptes créés (mot de passe commun : `password123`) :

| Email | Rôle |
|---|---|
| `admin01@example.com` | admin |
| `operator01@example.com` | operator |
| `user01@example.com` | user |

Recharge les fixtures après chaque modification de `src/DataFixtures/`.

### Lancer les tests

```bash
make tests                                                                    # tous les tests
docker compose exec php vendor/bin/phpunit tests/Unit                         # tests unitaires
docker compose exec php vendor/bin/phpunit tests/Functional/Controller/Api    # un dossier
```

### Organisation

```
tests/
├── Unit/                               # miroir de src/ : classes testées seules (mapper, ...)
└── Functional/
    ├── AuthenticatedClientTrait.php    # createClientLoggedInAs('admin01@example.com')
    └── Controller/                     # miroir de src/Controller/
        ├── SecurityControllerTest.php  # 
        ├── Api/                        # réponses JSON, tokens
        ├── Backoffice/                 # pages d'administration
        └── Front/                      # pages publiques, login, profil
```

- **Tests unitaires** : aucune base, aucun kernel Symfony.
- **Tests fonctionnels** : ils simulent des requêtes HTTP avec `WebTestCase`. Pour être connecté, `createClientLoggedInAs()` utilise un des comptes ci-dessus, sans passer par le formulaire.

### Isolation

`dama/doctrine-test-bundle` exécute chaque test dans une transaction annulée à la fin : un test peut créer ou modifier des données sans laisser de trace. Seules les fixtures chargées avant les tests restent en base. Les compteurs d'identifiants ne sont pas remis à zéro, donc les ids des comptes de test augmentent au fil des rechargements : un test doit retrouver un utilisateur par son email, jamais par un id écrit en dur.

## API

Les routes sont préfixées par `/api`. Les erreurs sont renvoyées en JSON.

**Connexion** : renvoie un token valable 12 heures.

```bash
curl -X POST http://localhost:8080/api/auth \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@example.com","password":"123_admin"}'
```

```json
{ "access_token": "..." }
```

**Appels authentifiés** : le token est envoyé dans l'en-tête `Authorization`.

```bash
curl http://localhost:8080/api/users/profile \
  -H "Authorization: Bearer TON_TOKEN"
```

| Méthode | Route | Accès |
|---|---|---|
| `POST` | `/api/auth` | public |
| `GET` | `/api/users/profile` | utilisateur connecté |
| `GET`, `POST` | `/api/admin/users` | admin |
| `GET`, `PATCH`, `DELETE` | `/api/admin/users/{id}` | admin |

La suppression est un soft delete (le champ `deletedAt` est renseigné et le compte est désactivé).

## Structure

```
.
├── app/                    # application Symfony
│   ├── assets/             # entrées Webpack (front, admin), JS et CSS
│   ├── src/Controller/     # Front/, Backoffice/, Api/
│   ├── src/Dto/            # DTO d'entrée et de sortie de l'API
│   ├── src/Security/       # UserChecker (bloque les comptes inactifs)
│   └── templates/          # frontoffice/, backoffice/, security/
├── docker/                 # Dockerfiles (php, nginx, node)
├── docker-compose.yml
└── Makefile
```

## Avant une mise en production

- Remplacer `JWT_SECRET` par un secret généré, propre à l'environnement. Avec le secret par défaut, n'importe qui peut forger un token administrateur.
- Changer le mot de passe de l'admin (`Admin123!`) ou supprimer ce compte.
- Changer les identifiants de PostgreSQL et de pgAdmin dans `docker-compose.yml`, et ne pas exposer les ports `5432` et `5050`.
- Lancer `make front` pour générer le CSS et le JS de production.
