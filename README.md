# FlowForge - Moteur BPM (Business Process Management)

## 📋 Description

FlowForge est un moteur de gestion de processus métier (BPM) complet et moderne, permettant de créer, gérer et automatiser des workflows personnalisés. L'application offre une interface intuitive pour concevoir des processus métier complexes avec un éditeur visuel drag & drop, des conditions dynamiques, des actions automatisées et des notifications en temps réel.

## 🎯 Objectifs du projet

- Créer un outil de workflow flexible et extensible
- Permettre aux entreprises d'automatiser leurs processus métier
- Offrir une expérience utilisateur moderne et réactive
- Fournir une API REST pour l'intégration avec des systèmes externes

## 🛠️ Stack Technique

### Backend
- **PHP 8.3** avec **Symfony 7**
- **PostgreSQL 16** - Base de données relationnelle
- **Redis 7** - Cache et sessions
- **Doctrine ORM** - Mapping objet-relationnel

### Frontend
- **Twig** - Moteur de templates
- **Tailwind CSS** - Framework CSS utility-first
- **Stimulus/Turbo** - Framework JavaScript (Hotwired)
- **Mermaid.js** - Visualisation de diagrammes
- **CodeMirror** - Éditeur de code JSON
- **Drawflow** - Éditeur visuel drag & drop

### Infrastructure
- **Docker** - Conteneurisation
- **Mercure** - Notifications temps réel (Server-Sent Events)
- **Mailpit** - Serveur mail de développement
- **Symfony Scheduler** - Tâches planifiées

## ✨ Fonctionnalités principales

### Gestion des Workflows
- Création et configuration de workflows personnalisés
- Définition d'étapes (places) et de transitions
- Éditeur visuel drag & drop pour concevoir les workflows graphiquement
- Visualisation des workflows en diagramme Mermaid
- Import/Export de configurations

### Conditions et Actions
- Conditions dynamiques avec Expression Language de Symfony
- Actions automatiques sur les transitions :
  - Envoi d'emails personnalisés
  - Appels webhooks vers des APIs externes
- Éditeur JSON avec coloration syntaxique

### Gestion des utilisateurs
- Authentification sécurisée
- Système de rôles hiérarchiques (User, Manager, Admin)
- Permissions granulaires avec Voter Symfony
- Assignation des tâches aux utilisateurs

### Suivi et alertes
- Historique complet des transitions
- Système de deadlines avec alertes automatiques
- Notifications temps réel via Mercure
- Vérification automatique des deadlines (Scheduler)

### API REST
- Authentification par token API
- Endpoints CRUD complets
- Documentation des routes
- Intégration facile avec des systèmes externes

## 📊 Cas d'usage inclus

L'application est livrée avec 4 workflows de démonstration :

1. **Gestion des commandes e-commerce** - 10 étapes, du panier à la livraison
2. **Demandes de congés RH** - Validation hiérarchique multi-niveaux
3. **Tickets support technique** - Avec escalade automatique
4. **Publication d'articles** - Workflow éditorial complet

## 🔐 Sécurité

- Authentification avec hash de mots de passe (bcrypt)
- Protection CSRF sur tous les formulaires
- Tokens API sécurisés
- Contrôle d'accès basé sur les rôles (RBAC)
- Sessions stockées dans Redis

## 🚀 Points techniques remarquables

- **Composant Workflow Symfony** pour la gestion des états
- **Event-driven** avec notifications temps réel
- **Cache Redis** pour les performances
- **Containerisation Docker** pour le déploiement

## 🖼️ Captures d'écran

- Dashboard des workflows
- Éditeur visuel drag & drop
- Visualisation Mermaid
- Interface de gestion des sujets
- Notifications temps réel

## 📝 Informations techniques

- **Durée de développement** : Projet complet
- **Type** : Application web full-stack
- **Licence** : Projet personnel

---
