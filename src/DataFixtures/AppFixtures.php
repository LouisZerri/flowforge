<?php

namespace App\DataFixtures;

use App\Entity\Action;
use App\Entity\Place;
use App\Entity\Transition;
use App\Entity\User;
use App\Entity\Workflow;
use App\Entity\WorkflowSubject;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher
    ) {}

    public function load(ObjectManager $manager): void
    {
        // ========================================
        // UTILISATEURS
        // ========================================
        $users = [];

        $usersData = [
            ['admin@flowforge.io', 'Admin', 'FlowForge', ['ROLE_ADMIN'], 'admin-token-123456'],
            ['marie.dupont@company.fr', 'Marie', 'Dupont', ['ROLE_USER'], 'marie-token-123456'],
            ['thomas.martin@company.fr', 'Thomas', 'Martin', ['ROLE_USER'], 'thomas-token-123456'],
            ['sophie.bernard@company.fr', 'Sophie', 'Bernard', ['ROLE_MANAGER'], 'sophie-token-123456'],
            ['nicolas.leroy@company.fr', 'Nicolas', 'Leroy', ['ROLE_USER'], 'nicolas-token-123456'],
            ['julie.moreau@company.fr', 'Julie', 'Moreau', ['ROLE_USER'], 'julie-token-123456'],
        ];
        
        foreach ($usersData as [$email, $firstName, $lastName, $roles, $apiToken]) {
            $user = new User();
            $user->setEmail($email);
            $user->setFirstName($firstName);
            $user->setLastName($lastName);
            $user->setRoles($roles);
            $user->setPassword($this->passwordHasher->hashPassword($user, 'password'));
            $user->setApiToken($apiToken);
            $manager->persist($user);
            $users[$email] = $user;
        }

        // ========================================
        // WORKFLOW 1 : E-commerce - Gestion des commandes
        // ========================================
        $wfCommande = new Workflow();
        $wfCommande->setName('Gestion des commandes');
        $wfCommande->setDescription('Workflow complet de traitement des commandes e-commerce, de la réception à la livraison.');
        $wfCommande->setInitialPlace('nouvelle');
        $wfCommande->setCreatedAt(new \DateTimeImmutable('-30 days'));
        $manager->persist($wfCommande);

        $placesCommande = [
            'nouvelle' => 'Nouvelle commande',
            'paiement_attente' => 'En attente de paiement',
            'paiement_valide' => 'Paiement validé',
            'en_preparation' => 'En préparation',
            'prete_expedition' => 'Prête à expédier',
            'expediee' => 'Expédiée',
            'en_livraison' => 'En cours de livraison',
            'livree' => 'Livrée',
            'annulee' => 'Annulée',
            'remboursee' => 'Remboursée',
        ];

        $placeEntitiesCommande = [];
        foreach ($placesCommande as $name => $label) {
            $place = new Place();
            $place->setName($name);
            $place->setLabel($label);
            $place->setWorkflow($wfCommande);
            $manager->persist($place);
            $placeEntitiesCommande[$name] = $place;
        }

        $transitionsCommande = [
            ['attendre_paiement', 'Attendre paiement', 'nouvelle', 'paiement_attente', null],
            ['valider_paiement', 'Valider paiement', 'paiement_attente', 'paiement_valide', null],
            ['preparer', 'Lancer préparation', 'paiement_valide', 'en_preparation', null],
            ['terminer_preparation', 'Terminer préparation', 'en_preparation', 'prete_expedition', null],
            ['expedier', 'Expédier', 'prete_expedition', 'expediee', 'data["transporteur"] != null'],
            ['en_cours_livraison', 'Prise en charge transporteur', 'expediee', 'en_livraison', null],
            ['confirmer_livraison', 'Confirmer livraison', 'en_livraison', 'livree', null],
            ['annuler_nouvelle', 'Annuler', 'nouvelle', 'annulee', null],
            ['annuler_paiement', 'Annuler', 'paiement_attente', 'annulee', null],
            ['rembourser', 'Rembourser', 'annulee', 'remboursee', 'data["montant"] > 0'],
        ];

        $transitionEntitiesCommande = [];
        foreach ($transitionsCommande as [$name, $label, $from, $to, $condition]) {
            $transition = new Transition();
            $transition->setName($name);
            $transition->setLabel($label);
            $transition->setFromPlace($placeEntitiesCommande[$from]);
            $transition->setToPlace($placeEntitiesCommande[$to]);
            $transition->setWorkflow($wfCommande);
            $transition->setCondition($condition);
            $manager->persist($transition);
            $transitionEntitiesCommande[$name] = $transition;
        }

        // Actions sur les transitions de commande
        $actionExpedition = new Action();
        $actionExpedition->setName('Notifier client expédition');
        $actionExpedition->setType('email');
        $actionExpedition->setTransition($transitionEntitiesCommande['expedier']);
        $actionExpedition->setConfig([
            'to' => 'client@example.com',
            'subject' => 'Votre commande {{ title }} a été expédiée',
            'body' => 'Bonne nouvelle ! Votre commande {{ title }} est en route.'
        ]);
        $manager->persist($actionExpedition);

        // Sujets commandes avec assignation
        $commandesData = [
            ['CMD-2024-001', 'nouvelle', ['client' => 'Marie Dupont', 'montant' => 156.90], 'marie.dupont@company.fr'],
            ['CMD-2024-002', 'paiement_attente', ['client' => 'Jean Martin', 'montant' => 89.00], 'thomas.martin@company.fr'],
            ['CMD-2024-003', 'en_preparation', ['client' => 'Sophie Bernard', 'montant' => 234.50, 'urgent' => true], 'nicolas.leroy@company.fr'],
            ['CMD-2024-004', 'expediee', ['client' => 'Pierre Durand', 'montant' => 45.00, 'transporteur' => 'Colissimo'], 'marie.dupont@company.fr'],
            ['CMD-2024-005', 'livree', ['client' => 'Isabelle Moreau', 'montant' => 312.00, 'transporteur' => 'Chronopost'], null],
            ['CMD-2024-006', 'annulee', ['client' => 'François Petit', 'montant' => 78.50], null],
        ];

        foreach ($commandesData as [$title, $place, $data, $assignedEmail]) {
            $subject = new WorkflowSubject();
            $subject->setTitle($title);
            $subject->setWorkflow($wfCommande);
            $subject->setCurrentPlace($place);
            $subject->setData($data);
            $subject->setCreatedAt(new \DateTimeImmutable('-' . rand(1, 20) . ' days'));
            if ($assignedEmail && isset($users[$assignedEmail])) {
                $subject->setAssignedTo($users[$assignedEmail]);
            }
            $manager->persist($subject);
        }

        // ========================================
        // WORKFLOW 2 : RH - Demandes de congés
        // ========================================
        $wfConges = new Workflow();
        $wfConges->setName('Demandes de congés');
        $wfConges->setDescription('Gestion des demandes de congés avec validation hiérarchique et RH.');
        $wfConges->setInitialPlace('brouillon');
        $wfConges->setCreatedAt(new \DateTimeImmutable('-60 days'));
        $manager->persist($wfConges);

        $placesConges = [
            'brouillon' => 'Brouillon',
            'validation_manager' => 'En attente validation manager',
            'validation_rh' => 'En attente validation RH',
            'approuvee' => 'Approuvée',
            'refusee' => 'Refusée',
            'annulee' => 'Annulée par employé',
        ];

        $placeEntitiesConges = [];
        foreach ($placesConges as $name => $label) {
            $place = new Place();
            $place->setName($name);
            $place->setLabel($label);
            $place->setWorkflow($wfConges);
            $manager->persist($place);
            $placeEntitiesConges[$name] = $place;
        }

        $transitionsConges = [
            ['soumettre', 'Soumettre la demande', 'brouillon', 'validation_manager', null],
            ['approuver_manager', 'Approuver (Manager)', 'validation_manager', 'validation_rh', null],
            ['refuser_manager', 'Refuser (Manager)', 'validation_manager', 'refusee', null],
            ['approuver_rh', 'Approuver (RH)', 'validation_rh', 'approuvee', 'data["jours"] <= 25'],
            ['refuser_rh', 'Refuser (RH)', 'validation_rh', 'refusee', null],
            ['annuler', 'Annuler ma demande', 'brouillon', 'annulee', null],
            ['annuler_soumise', 'Annuler ma demande', 'validation_manager', 'annulee', null],
        ];

        $transitionEntitiesConges = [];
        foreach ($transitionsConges as [$name, $label, $from, $to, $condition]) {
            $transition = new Transition();
            $transition->setName($name);
            $transition->setLabel($label);
            $transition->setFromPlace($placeEntitiesConges[$from]);
            $transition->setToPlace($placeEntitiesConges[$to]);
            $transition->setWorkflow($wfConges);
            $transition->setCondition($condition);
            $manager->persist($transition);
            $transitionEntitiesConges[$name] = $transition;
        }

        // Action congés
        $actionCongesApprouve = new Action();
        $actionCongesApprouve->setName('Notifier employé approbation');
        $actionCongesApprouve->setType('email');
        $actionCongesApprouve->setTransition($transitionEntitiesConges['approuver_rh']);
        $actionCongesApprouve->setConfig([
            'to' => 'employe@company.com',
            'subject' => 'Congés approuvés : {{ title }}',
            'body' => 'Votre demande de congés {{ title }} a été approuvée.'
        ]);
        $manager->persist($actionCongesApprouve);

        // Sujets congés
        $congesData = [
            ['Congés été - Alice Martin', 'brouillon', ['employe' => 'Alice Martin', 'jours' => 11], 'marie.dupont@company.fr'],
            ['RTT Novembre - Marc Dubois', 'validation_manager', ['employe' => 'Marc Dubois', 'jours' => 1], 'sophie.bernard@company.fr'],
            ['Congé parental - Julie Chen', 'validation_rh', ['employe' => 'Julie Chen', 'jours' => 66], 'sophie.bernard@company.fr'],
            ['Congés Noël - Pierre Roux', 'approuvee', ['employe' => 'Pierre Roux', 'jours' => 7], null],
            ['RTT Octobre - Emma Wilson', 'refusee', ['employe' => 'Emma Wilson', 'jours' => 2, 'motif_refus' => 'Période de clôture'], null],
        ];

        foreach ($congesData as [$title, $place, $data, $assignedEmail]) {
            $subject = new WorkflowSubject();
            $subject->setTitle($title);
            $subject->setWorkflow($wfConges);
            $subject->setCurrentPlace($place);
            $subject->setData($data);
            $subject->setCreatedAt(new \DateTimeImmutable('-' . rand(1, 30) . ' days'));
            if ($assignedEmail && isset($users[$assignedEmail])) {
                $subject->setAssignedTo($users[$assignedEmail]);
            }
            $manager->persist($subject);
        }

        // ========================================
        // WORKFLOW 3 : Support - Tickets
        // ========================================
        $wfTicket = new Workflow();
        $wfTicket->setName('Tickets support');
        $wfTicket->setDescription('Gestion des tickets de support technique.');
        $wfTicket->setInitialPlace('ouvert');
        $wfTicket->setCreatedAt(new \DateTimeImmutable('-90 days'));
        $manager->persist($wfTicket);

        $placesTicket = [
            'ouvert' => 'Ouvert',
            'en_cours' => 'En cours de traitement',
            'en_attente_client' => 'En attente réponse client',
            'escalade' => 'Escaladé niveau 2',
            'resolu' => 'Résolu',
            'ferme' => 'Fermé',
        ];

        $placeEntitiesTicket = [];
        foreach ($placesTicket as $name => $label) {
            $place = new Place();
            $place->setName($name);
            $place->setLabel($label);
            $place->setWorkflow($wfTicket);
            $manager->persist($place);
            $placeEntitiesTicket[$name] = $place;
        }

        $transitionsTicket = [
            ['prendre_en_charge', 'Prendre en charge', 'ouvert', 'en_cours', null],
            ['demander_info', 'Demander infos au client', 'en_cours', 'en_attente_client', null],
            ['recevoir_reponse', 'Réponse reçue', 'en_attente_client', 'en_cours', null],
            ['escalader', 'Escalader N2', 'en_cours', 'escalade', 'data["priorite"] == "haute"'],
            ['resoudre', 'Marquer résolu', 'en_cours', 'resolu', null],
            ['resoudre_escalade', 'Marquer résolu', 'escalade', 'resolu', null],
            ['fermer', 'Fermer le ticket', 'resolu', 'ferme', null],
            ['rouvrir', 'Rouvrir', 'resolu', 'en_cours', null],
        ];

        $transitionEntitiesTicket = [];
        foreach ($transitionsTicket as [$name, $label, $from, $to, $condition]) {
            $transition = new Transition();
            $transition->setName($name);
            $transition->setLabel($label);
            $transition->setFromPlace($placeEntitiesTicket[$from]);
            $transition->setToPlace($placeEntitiesTicket[$to]);
            $transition->setWorkflow($wfTicket);
            $transition->setCondition($condition);
            $manager->persist($transition);
            $transitionEntitiesTicket[$name] = $transition;
        }

        // Sujets tickets
        $ticketsData = [
            ['TKT-001 - Impossible de se connecter', 'ouvert', ['client' => 'Acme Corp', 'priorite' => 'haute'], null],
            ['TKT-002 - Erreur 500 sur dashboard', 'en_cours', ['client' => 'TechStart', 'priorite' => 'haute'], 'nicolas.leroy@company.fr'],
            ['TKT-003 - Question facturation', 'en_attente_client', ['client' => 'PME Solutions', 'priorite' => 'basse'], 'julie.moreau@company.fr'],
            ['TKT-004 - Performance lente', 'escalade', ['client' => 'BigCorp', 'priorite' => 'haute'], 'thomas.martin@company.fr'],
            ['TKT-005 - Demande nouvelle fonctionnalité', 'resolu', ['client' => 'Startup Lab', 'priorite' => 'basse'], 'julie.moreau@company.fr'],
            ['TKT-006 - Bug export PDF', 'ferme', ['client' => 'Cabinet Conseil', 'priorite' => 'moyenne'], null],
        ];

        foreach ($ticketsData as [$title, $place, $data, $assignedEmail]) {
            $subject = new WorkflowSubject();
            $subject->setTitle($title);
            $subject->setWorkflow($wfTicket);
            $subject->setCurrentPlace($place);
            $subject->setData($data);
            $subject->setCreatedAt(new \DateTimeImmutable('-' . rand(1, 45) . ' days'));
            if ($assignedEmail && isset($users[$assignedEmail])) {
                $subject->setAssignedTo($users[$assignedEmail]);
            }
            $manager->persist($subject);
        }

        // ========================================
        // WORKFLOW 4 : Publication - Articles
        // ========================================
        $wfArticle = new Workflow();
        $wfArticle->setName('Publication articles');
        $wfArticle->setDescription('Workflow éditorial pour la publication d\'articles.');
        $wfArticle->setInitialPlace('brouillon');
        $wfArticle->setCreatedAt(new \DateTimeImmutable('-45 days'));
        $manager->persist($wfArticle);

        $placesArticle = [
            'brouillon' => 'Brouillon',
            'en_relecture' => 'En relecture',
            'corrections' => 'Corrections demandées',
            'valide' => 'Validé',
            'planifie' => 'Planifié',
            'publie' => 'Publié',
            'archive' => 'Archivé',
        ];

        $placeEntitiesArticle = [];
        foreach ($placesArticle as $name => $label) {
            $place = new Place();
            $place->setName($name);
            $place->setLabel($label);
            $place->setWorkflow($wfArticle);
            $manager->persist($place);
            $placeEntitiesArticle[$name] = $place;
        }

        $transitionsArticle = [
            ['soumettre', 'Soumettre pour relecture', 'brouillon', 'en_relecture', 'data["mots"] >= 500'],
            ['demander_corrections', 'Demander corrections', 'en_relecture', 'corrections', null],
            ['resoumettre', 'Resoumettre', 'corrections', 'en_relecture', null],
            ['valider', 'Valider', 'en_relecture', 'valide', null],
            ['planifier', 'Planifier publication', 'valide', 'planifie', 'data["date_publication"] != null'],
            ['publier', 'Publier maintenant', 'valide', 'publie', null],
            ['publier_planifie', 'Publier', 'planifie', 'publie', null],
            ['archiver', 'Archiver', 'publie', 'archive', null],
        ];

        foreach ($transitionsArticle as [$name, $label, $from, $to, $condition]) {
            $transition = new Transition();
            $transition->setName($name);
            $transition->setLabel($label);
            $transition->setFromPlace($placeEntitiesArticle[$from]);
            $transition->setToPlace($placeEntitiesArticle[$to]);
            $transition->setWorkflow($wfArticle);
            $transition->setCondition($condition);
            $manager->persist($transition);
        }

        // Sujets articles
        $articlesData = [
            ['10 astuces SEO', 'brouillon', ['auteur' => 'Marie Content', 'mots' => 320], 'marie.dupont@company.fr'],
            ['Guide Docker débutants', 'en_relecture', ['auteur' => 'Thomas Dev', 'mots' => 2500], 'sophie.bernard@company.fr'],
            ['Tendances UX 2024', 'corrections', ['auteur' => 'Julie Design', 'mots' => 1800], 'julie.moreau@company.fr'],
            ['Migration cloud réussie', 'valide', ['auteur' => 'Pierre Cloud', 'mots' => 3200], null],
            ['Interview CEO TechStartup', 'planifie', ['auteur' => 'Marie Content', 'mots' => 1500, 'date_publication' => '2024-12-15'], null],
            ['Retour conférence DevFest', 'publie', ['auteur' => 'Thomas Dev', 'mots' => 800, 'vues' => 1250], null],
        ];

        foreach ($articlesData as [$title, $place, $data, $assignedEmail]) {
            $subject = new WorkflowSubject();
            $subject->setTitle($title);
            $subject->setWorkflow($wfArticle);
            $subject->setCurrentPlace($place);
            $subject->setData($data);
            $subject->setCreatedAt(new \DateTimeImmutable('-' . rand(1, 60) . ' days'));
            if ($assignedEmail && isset($users[$assignedEmail])) {
                $subject->setAssignedTo($users[$assignedEmail]);
            }
            $manager->persist($subject);
        }

        $manager->flush();
    }
}