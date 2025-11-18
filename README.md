# Obsidiane Auth Bundle

Bundle Symfony fournissant un client HTTP prêt à l’emploi pour l’API Obsidiane Auth (cookies + CSRF stateless).

## Installation

1. Requérir le bundle (publié sur Packagist) :

```
composer require obsidiane/auth-sdk:<VERSION>
```

2. Activer le bundle si Flex ne l’ajoute pas automatiquement:

`config/bundles.php`

```
return [
    // ...
    Obsidiane\AuthBundle\ObsidianeAuthBundle::class => ['all' => true],
];
```

## Configuration

`config/packages/obsidiane_auth.yaml` (optionnel):

```
obsidiane_auth:
  base_url: '%env(string:OBSIDIANE_AUTH_BASE_URL)%'
```

## Utilisation

Injection de `Obsidiane\AuthBundle\AuthClient` dans vos services/contrôleurs:

```php
public function __construct(private AuthClient $auth) {}

public function login(): Response
{
    $payload = $this->auth->login('user@example.com', 'Secret123!');
    // ...
}
```

Le client gère automatiquement les cookies (access/refresh) et génère lui‑même un token CSRF stateless envoyé dans l’en‑tête `csrf-token` pour les mutations.

Pour le détail des endpoints et des flows d’authentification, reportez‑vous au `README.md` du projet principal.

Pour des appels HTTP personnalisés, vous pouvez également générer un token CSRF compatible via :

```php
$csrf = $this->auth->generateCsrfToken();
// puis l'envoyer dans l'en-tête "csrf-token" de votre requête custom
```

---

## Modèles & routes API Platform

Le SDK fournit des modèles simples qui reflètent les ressources exposées par API Platform, ainsi que des helpers pour les consommer.

### Modèles

- `Obsidiane\AuthBundle\Model\User` : projection de la ressource `User` (`id`, `email`, `roles`, `isEmailVerified`).
- `Obsidiane\AuthBundle\Model\Invite` : projection de la ressource `InviteUser` (`id`, `email`, `createdAt`, `expiresAt`, `acceptedAt`).
- `Obsidiane\AuthBundle\Model\Item<T>` : wrapper générique pour un item JSON‑LD (métadonnées `@id`, `@type`, `@context` + attributs métiers).
- `Obsidiane\AuthBundle\Model\Collection<T>` : wrapper générique pour une collection JSON‑LD sans Hydra (métadonnées + `items` + `totalItems`).

Ces classes disposent d’une méthode `fromArray(array $data)` compatible avec les payloads JSON / JSON‑LD retournés par `/api/users/*` et `/api/invite_users*`. `Item` et `Collection` peuvent être utilisés si vous travaillez directement avec la représentation JSON‑LD (format `jsonld` d’API Platform v4, sans Hydra).

### Helpers User (ApiPlatform)

```php
use Obsidiane\AuthBundle\Model\User;

/** @var User $user */
$user = $this->auth->currentUserResource(); // GET /api/users/me
```

### Helpers Invite (ApiPlatform)

```php
use Obsidiane\AuthBundle\Model\Invite;

/** @var Invite[] $invites */
$invites = $this->auth->listInvites(); // GET /api/invite_users

/** @var Invite $invite */
$invite = $this->auth->getInvite(1); // GET /api/invite_users/1
```

### Helpers d’invitation (endpoints auth)

```php
// POST /api/auth/invite (admin uniquement)
$status = $this->auth->inviteUser('invitee@example.com'); // ['status' => 'INVITE_SENT', ...]

// POST /api/auth/invite/complete
$result = $this->auth->completeInvite('invitation-token', 'Secret123!');
// $result contient le payload utilisateur (similaire à l’inscription)
```
