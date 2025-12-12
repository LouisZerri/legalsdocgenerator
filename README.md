# LegalDocs Generator 📄

Plateforme de génération de documents juridiques avec assistance IA, workflow de validation et gestion multi-organisations.

![Symfony](https://img.shields.io/badge/Symfony-7.4-purple?logo=symfony)
![PHP](https://img.shields.io/badge/PHP-8.4-blue?logo=php)
![TailwindCSS](https://img.shields.io/badge/TailwindCSS-4.0-38B2AC?logo=tailwind-css)
![Docker](https://img.shields.io/badge/Docker-Compose-2496ED?logo=docker)
![PostgreSQL](https://img.shields.io/badge/PostgreSQL-16-336791?logo=postgresql)

## ✨ Fonctionnalités

### Gestion des documents
- **Templates personnalisables** avec variables dynamiques (`{{nom}}`, `{{date}}`, etc.)
- **Génération de documents** à partir de templates avec formulaire dynamique
- **Export PDF** professionnel avec mise en page soignée
- **Workflow de validation** : Brouillon → En révision → Approuvé / Rejeté

### Intelligence Artificielle
- **Assistant juridique IA** intégré (widget chatbot)
- **Amélioration de documents** par IA
- **Génération de clauses** juridiques
- **Reformulation** et **résumé** automatique
- Powered by **Ollama** avec le modèle **Mistral**

### Multi-organisations
- **Isolation des données** par organisation
- **5 niveaux de rôles** : Super Admin, Admin Org, Éditeur, Validateur, Utilisateur
- **Templates globaux** ou privés par organisation

### Interface moderne
- **Dashboard** avec statistiques et graphiques (Chart.js)
- **Sidebar** avec navigation intuitive et badges de notification
- **Recherche globale** (documents, templates, utilisateurs)
- **Profil utilisateur** avec changement de mot de passe
- **Toasts** de notification
- Design responsive avec **TailwindCSS 4**

## 🛠️ Stack technique

| Composant | Technologie |
|-----------|-------------|
| Framework | Symfony 7.4 |
| PHP | 8.4 |
| Base de données | PostgreSQL 16 |
| Cache | Redis |
| Queue | RabbitMQ |
| CSS | TailwindCSS 4 |
| Bundler | Webpack Encore |
| IA | Ollama (Mistral) |
| PDF | DomPDF |
| Conteneurisation | Docker Compose |

## 📋 Prérequis

- Docker & Docker Compose
- Git
- 8 Go de RAM minimum (pour Mistral)

## 🚀 Installation

### 1. Cloner le projet

```bash
git clone https://github.com/votre-repo/legal-docs-generator.git
cd legal-docs-generator
```

### 2. Lancer les conteneurs

```bash
docker compose up -d
```

### 3. Installer les dépendances

```bash
docker exec -it legaldocs_app composer install
docker exec -it legaldocs_app npm install
```

### 4. Configurer la base de données

```bash
docker exec -it legaldocs_app php bin/console doctrine:migrations:migrate
docker exec -it legaldocs_app php bin/console doctrine:fixtures:load
```

### 5. Compiler les assets

```bash
docker exec -it legaldocs_app npm run dev
```

### 6. Télécharger le modèle IA

```bash
docker exec -it legaldocs_ollama ollama pull mistral
```

### 7. Accéder à l'application

- **Application** : http://localhost:8080
- **RabbitMQ** : http://localhost:15672 (guest/guest)

## 👥 Comptes de test

| Rôle | Email | Mot de passe |
|------|-------|--------------|
| Super Admin | superadmin@legaldocs.fr | password |
| Admin Org | admin@cabinet-martin.fr | password |
| Éditeur | marie@cabinet-martin.fr | password |
| Validateur | pierre@cabinet-martin.fr | password |
| Utilisateur | sophie@startup-innov.fr | password |

## 📁 Structure du projet

```
legal-docs-generator/
├── assets/                 # Assets frontend (JS, CSS)
├── config/                 # Configuration Symfony
├── docker/                 # Configuration Docker
│   ├── nginx/
│   └── php/
├── migrations/             # Migrations Doctrine
├── public/                 # Point d'entrée web
├── src/
│   ├── Controller/         # Contrôleurs
│   ├── Entity/             # Entités Doctrine
│   ├── EventSubscriber/    # Event subscribers
│   ├── Form/               # Formulaires
│   ├── Repository/         # Repositories
│   ├── Security/           # Voters et authentification
│   └── Service/            # Services (AiService, PdfService)
├── templates/              # Templates Twig
│   ├── document/
│   ├── template/
│   ├── admin/
│   ├── layout/
│   └── ...
├── docker-compose.yml
└── README.md
```

## 🔧 Commandes utiles

### Développement

```bash
# Lancer les conteneurs
docker compose up -d

# Voir les logs
docker compose logs -f

# Arrêter les conteneurs
docker compose down

# Compiler les assets (dev)
docker exec -it legaldocs_app npm run dev

# Compiler les assets (watch)
docker exec -it legaldocs_app npm run watch

# Vider le cache
docker exec -it legaldocs_app php bin/console cache:clear
```

### Base de données

```bash
# Créer une migration
docker exec -it legaldocs_app php bin/console make:migration

# Exécuter les migrations
docker exec -it legaldocs_app php bin/console doctrine:migrations:migrate

# Recharger les fixtures
docker exec -it legaldocs_app php bin/console doctrine:fixtures:load
```

### IA / Ollama

```bash
# Lister les modèles installés
docker exec -it legaldocs_ollama ollama list

# Installer un modèle
docker exec -it legaldocs_ollama ollama pull mistral

# Tester un modèle
docker exec -it legaldocs_ollama ollama run mistral "Bonjour"
```

## 📊 Workflow des documents

```
┌─────────┐     ┌───────────┐     ┌──────────┐
│ DRAFT   │────▶│  REVIEW   │────▶│ APPROVED │
│(Brouillon)    │(En révision)    │(Approuvé)│
└─────────┘     └───────────┘     └──────────┘
                      │
                      ▼
               ┌──────────┐
               │ REJECTED │
               │ (Rejeté) │
               └──────────┘
```

## 🔐 Rôles et permissions

| Permission | Super Admin | Admin Org | Éditeur | Validateur | Utilisateur |
|------------|:-----------:|:---------:|:-------:|:----------:|:-----------:|
| Voir tous les documents | ✅ | ✅ Org | ✅ Org | ✅ Org | ❌ |
| Créer document | ✅ | ✅ | ✅ | ❌ | ❌ |
| Modifier document | ✅ | ✅ | ✅ | ❌ | ❌ |
| Soumettre à validation | ✅ | ✅ | ✅ | ❌ | ❌ |
| Approuver/Rejeter | ✅ | ✅ | ❌ | ✅ | ❌ |
| Gérer templates | ✅ | ✅ | ❌ | ❌ | ❌ |
| Gérer utilisateurs | ✅ | ✅ Org | ❌ | ❌ | ❌ |
| Gérer organisations | ✅ | ❌ | ❌ | ❌ | ❌ |

## 🤖 Assistant IA

L'assistant IA intégré peut :
- Répondre aux questions juridiques
- Expliquer des termes juridiques
- Aider à rédiger des clauses
- Améliorer des documents existants
- Vérifier la conformité

Pour l'utiliser, cliquez sur le bouton de chat en bas à droite de l'écran.

## 📝 Variables de templates

Les templates supportent des variables dynamiques :

```
Madame, Monsieur {{nom_client}},

Suite à notre accord du {{date_accord}}, nous vous confirmons...

Montant : {{montant}} €
```

Types de variables supportés :
- `text` : Texte simple
- `textarea` : Texte multiligne
- `date` : Sélecteur de date
- `number` : Nombre
- `email` : Email
- `select` : Liste déroulante

## 🐛 Dépannage

### Le CSS ne s'affiche pas correctement

```bash
docker exec -it legaldocs_app npm run dev
# Puis Ctrl+Shift+R dans le navigateur
```

### L'IA ne répond pas

```bash
# Vérifier qu'Ollama fonctionne
docker exec -it legaldocs_ollama ollama list

# Redémarrer Ollama si nécessaire
docker restart legaldocs_ollama
```

### Erreur de base de données

```bash
docker exec -it legaldocs_app php bin/console doctrine:migrations:migrate
docker exec -it legaldocs_app php bin/console cache:clear
```

## 📄 Licence

Ce projet est sous licence MIT.

## 👨‍💻 Auteur

Développé par Louis Zerri

---

**LegalDocs Generator** - Simplifiez la création de vos documents juridiques 📄✨