<?php

namespace App\DataFixtures;

use App\Entity\Document;
use App\Entity\Organization;
use App\Entity\Template;
use App\Entity\User;
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
        // ========== ORGANISATIONS ==========
        $org1 = new Organization();
        $org1->setName('LegalDocs Corp');
        $org1->setSettings(['plan' => 'enterprise']);
        $org1->setCreatedAt(new \DateTimeImmutable('-6 months'));
        $manager->persist($org1);

        $org2 = new Organization();
        $org2->setName('Cabinet Martin');
        $org2->setSettings(['plan' => 'pro']);
        $org2->setCreatedAt(new \DateTimeImmutable('-3 months'));
        $manager->persist($org2);

        $org3 = new Organization();
        $org3->setName('Startup Innov');
        $org3->setSettings(['plan' => 'free']);
        $org3->setCreatedAt(new \DateTimeImmutable('-1 month'));
        $manager->persist($org3);

        // ========== UTILISATEURS ==========

        // Super Admin (sans organisation)
        $superAdmin = new User();
        $superAdmin->setEmail('superadmin@legaldocs.fr');
        $superAdmin->setName('Super Admin');
        $superAdmin->setRoles(['ROLE_SUPER_ADMIN']);
        $superAdmin->setPassword($this->passwordHasher->hashPassword($superAdmin, 'password'));
        $superAdmin->setCreatedAt(new \DateTimeImmutable('-6 months'));
        $manager->persist($superAdmin);

        // Admin Org (LegalDocs Corp)
        $admin = new User();
        $admin->setEmail('admin@legaldocs.fr');
        $admin->setName('Admin LegalDocs');
        $admin->setRoles(['ROLE_ADMIN_ORG']);
        $admin->setOrganization($org1);
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'password'));
        $admin->setCreatedAt(new \DateTimeImmutable('-6 months'));
        $manager->persist($admin);

        // Éditeur (LegalDocs Corp)
        $editor = new User();
        $editor->setEmail('editeur@legaldocs.fr');
        $editor->setName('Marie Dupont');
        $editor->setRoles(['ROLE_EDITOR']);
        $editor->setOrganization($org1);
        $editor->setPassword($this->passwordHasher->hashPassword($editor, 'password'));
        $editor->setCreatedAt(new \DateTimeImmutable('-5 months'));
        $manager->persist($editor);

        // Validateur (LegalDocs Corp)
        $validator = new User();
        $validator->setEmail('juriste@legaldocs.fr');
        $validator->setName('Pierre Martin');
        $validator->setRoles(['ROLE_VALIDATOR']);
        $validator->setOrganization($org1);
        $validator->setPassword($this->passwordHasher->hashPassword($validator, 'password'));
        $validator->setCreatedAt(new \DateTimeImmutable('-4 months'));
        $manager->persist($validator);

        // User simple (Cabinet Martin)
        $user = new User();
        $user->setEmail('user@legaldocs.fr');
        $user->setName('Sophie Laurent');
        $user->setRoles(['ROLE_USER']);
        $user->setOrganization($org2);
        $user->setPassword($this->passwordHasher->hashPassword($user, 'password'));
        $user->setCreatedAt(new \DateTimeImmutable('-3 months'));
        $manager->persist($user);

        // User Startup
        $userStartup = new User();
        $userStartup->setEmail('startup@legaldocs.fr');
        $userStartup->setName('Lucas Bernard');
        $userStartup->setRoles(['ROLE_EDITOR']);
        $userStartup->setOrganization($org3);
        $userStartup->setPassword($this->passwordHasher->hashPassword($userStartup, 'password'));
        $userStartup->setCreatedAt(new \DateTimeImmutable('-1 month'));
        $manager->persist($userStartup);

        // ========== TEMPLATES ==========

        // Template NDA (global)
        $templateNda = new Template();
        $templateNda->setName('Accord de Confidentialité (NDA)');
        $templateNda->setDescription('Accord de confidentialité bilatéral pour protéger les informations sensibles.');
        $templateNda->setVisibility('global');
        $templateNda->setCreatedBy($superAdmin);
        $templateNda->setCreatedAt(new \DateTimeImmutable('-6 months'));
        $templateNda->setBodyMarkdown(<<<'MARKDOWN'
# Accord de Confidentialité

Entre les soussignés :

**{{partie_1_nom}}**, {{partie_1_forme_juridique}}, dont le siège social est situé {{partie_1_adresse}}, représentée par {{partie_1_representant}},

Ci-après dénommée « **La Partie Divulgatrice** »

Et

**{{partie_2_nom}}**, {{partie_2_forme_juridique}}, dont le siège social est situé {{partie_2_adresse}}, représentée par {{partie_2_representant}},

Ci-après dénommée « **La Partie Réceptrice** »

## Article 1 - Objet

Le présent accord a pour objet de définir les conditions dans lesquelles la Partie Réceptrice s'engage à maintenir confidentielles les informations qui lui seront communiquées par la Partie Divulgatrice dans le cadre de {{objet_collaboration}}.

## Article 2 - Durée de confidentialité

Les obligations de confidentialité prévues au présent accord resteront en vigueur pendant une durée de **{{duree_confidentialite}} ans** à compter de la date de signature.

## Article 3 - Obligations

La Partie Réceptrice s'engage à :
- Ne pas divulguer les Informations Confidentielles à des tiers
- Protéger les Informations Confidentielles avec le même degré de précaution que ses propres informations confidentielles

Fait à {{lieu_signature}}, le {{date_signature}}
MARKDOWN);
        $templateNda->setVariablesJson([
            ['name' => 'partie_1_nom', 'type' => 'text', 'required' => true],
            ['name' => 'partie_1_forme_juridique', 'type' => 'text', 'required' => true],
            ['name' => 'partie_1_adresse', 'type' => 'text', 'required' => true],
            ['name' => 'partie_1_representant', 'type' => 'text', 'required' => true],
            ['name' => 'partie_2_nom', 'type' => 'text', 'required' => true],
            ['name' => 'partie_2_forme_juridique', 'type' => 'text', 'required' => true],
            ['name' => 'partie_2_adresse', 'type' => 'text', 'required' => true],
            ['name' => 'partie_2_representant', 'type' => 'text', 'required' => true],
            ['name' => 'objet_collaboration', 'type' => 'textarea', 'required' => true],
            ['name' => 'duree_confidentialite', 'type' => 'number', 'required' => true],
            ['name' => 'lieu_signature', 'type' => 'text', 'required' => true],
            ['name' => 'date_signature', 'type' => 'date', 'required' => true],
        ]);
        $manager->persist($templateNda);

        // Template CGV (global)
        $templateCgv = new Template();
        $templateCgv->setName('Conditions Générales de Vente');
        $templateCgv->setDescription('CGV standard pour prestations de services.');
        $templateCgv->setVisibility('global');
        $templateCgv->setCreatedBy($superAdmin);
        $templateCgv->setCreatedAt(new \DateTimeImmutable('-5 months'));
        $templateCgv->setBodyMarkdown(<<<'MARKDOWN'
# Conditions Générales de Vente

## Article 1 - Identification du prestataire

**{{entreprise_nom}}** {{entreprise_forme_juridique}} au capital de {{entreprise_capital}} €
Siège social : {{entreprise_adresse}}
SIRET : {{entreprise_siret}}

## Article 2 - Objet

Les présentes CGV régissent les relations contractuelles entre {{entreprise_nom}} et ses clients pour toute prestation de {{type_prestation}}.

## Article 3 - Prix

Les prix sont exprimés en euros et s'entendent {{prix_ht_ttc}}.
Tarif horaire : {{tarif_horaire}} €

## Article 4 - Paiement

Le paiement est dû à {{delai_paiement}} jours à compter de la date de facturation.

Fait le {{date_redaction}}
MARKDOWN);
        $templateCgv->setVariablesJson([
            ['name' => 'entreprise_nom', 'type' => 'text', 'required' => true],
            ['name' => 'entreprise_forme_juridique', 'type' => 'text', 'required' => true],
            ['name' => 'entreprise_capital', 'type' => 'number', 'required' => true],
            ['name' => 'entreprise_adresse', 'type' => 'text', 'required' => true],
            ['name' => 'entreprise_siret', 'type' => 'text', 'required' => true],
            ['name' => 'type_prestation', 'type' => 'text', 'required' => true],
            ['name' => 'prix_ht_ttc', 'type' => 'select', 'required' => true, 'options' => ['HT', 'TTC']],
            ['name' => 'tarif_horaire', 'type' => 'number', 'required' => true],
            ['name' => 'delai_paiement', 'type' => 'number', 'required' => true],
            ['name' => 'date_redaction', 'type' => 'date', 'required' => true],
        ]);
        $manager->persist($templateCgv);

        // Template Contrat SaaS (privé - LegalDocs Corp)
        $templateSaas = new Template();
        $templateSaas->setName('Contrat SaaS');
        $templateSaas->setDescription('Contrat de licence logicielle en mode SaaS.');
        $templateSaas->setVisibility('private');
        $templateSaas->setOrganization($org1);
        $templateSaas->setCreatedBy($admin);
        $templateSaas->setCreatedAt(new \DateTimeImmutable('-4 months'));
        $templateSaas->setBodyMarkdown(<<<'MARKDOWN'
# Contrat de Licence SaaS

## Article 1 - Objet

Le présent contrat a pour objet de définir les conditions d'utilisation de la solution SaaS fournie par LegalDocs Corp au client **{{client_nom}}**.

## Article 2 - Durée

Le contrat est conclu pour une durée de **{{duree_contrat}} mois** à compter du {{date_debut}}.

## Article 3 - Tarification

L'abonnement mensuel est fixé à **{{montant_mensuel}} €** HT.

## Article 4 - Support

Le client bénéficie d'un support technique par email et téléphone, du lundi au vendredi de 9h à 18h.
MARKDOWN);
        $templateSaas->setVariablesJson([
            ['name' => 'client_nom', 'type' => 'text', 'required' => true],
            ['name' => 'duree_contrat', 'type' => 'number', 'required' => true],
            ['name' => 'date_debut', 'type' => 'date', 'required' => true],
            ['name' => 'montant_mensuel', 'type' => 'number', 'required' => true],
        ]);
        $manager->persist($templateSaas);

        // Template Contrat de travail (privé - Cabinet Martin)
        $templateTravail = new Template();
        $templateTravail->setName('Contrat de Travail CDI');
        $templateTravail->setDescription('Contrat de travail à durée indéterminée.');
        $templateTravail->setVisibility('private');
        $templateTravail->setOrganization($org2);
        $templateTravail->setCreatedBy($user);
        $templateTravail->setCreatedAt(new \DateTimeImmutable('-2 months'));
        $templateTravail->setBodyMarkdown(<<<'MARKDOWN'
# Contrat de Travail à Durée Indéterminée

Entre l'employeur **{{employeur_nom}}** et le salarié **{{salarie_nom}}**.

## Article 1 - Engagement

Le salarié est engagé en qualité de **{{poste}}** à compter du {{date_debut}}.

## Article 2 - Rémunération

Le salaire brut mensuel est fixé à **{{salaire_brut}} €**.

## Article 3 - Durée du travail

La durée hebdomadaire de travail est de **{{heures_semaine}} heures**.
MARKDOWN);
        $templateTravail->setVariablesJson([
            ['name' => 'employeur_nom', 'type' => 'text', 'required' => true],
            ['name' => 'salarie_nom', 'type' => 'text', 'required' => true],
            ['name' => 'poste', 'type' => 'text', 'required' => true],
            ['name' => 'date_debut', 'type' => 'date', 'required' => true],
            ['name' => 'salaire_brut', 'type' => 'number', 'required' => true],
            ['name' => 'heures_semaine', 'type' => 'number', 'required' => true],
        ]);
        $manager->persist($templateTravail);

        // ========== DOCUMENTS ==========

        // Document 1 - NDA Draft (il y a 5 mois)
        $doc1 = new Document();
        $doc1->setTemplate($templateNda);
        $doc1->setOrganization($org1);
        $doc1->setCreatedBy($admin);
        $doc1->setStatus(Document::STATUS_DRAFT);
        $doc1->setCreatedAt(new \DateTimeImmutable('-5 months'));
        $doc1->setDataJson([
            'partie_1_nom' => 'ACME Technologies',
            'partie_1_forme_juridique' => 'SAS',
            'partie_1_adresse' => '123 rue de l\'Innovation, 75001 Paris',
            'partie_1_representant' => 'Jean Dupont, Président',
            'partie_2_nom' => 'DevStudio',
            'partie_2_forme_juridique' => 'SARL',
            'partie_2_adresse' => '45 avenue du Code, 69001 Lyon',
            'partie_2_representant' => 'Marie Martin, Gérante',
            'objet_collaboration' => 'développement d\'une application mobile de gestion RH',
            'duree_confidentialite' => '3',
            'lieu_signature' => 'Paris',
            'date_signature' => '2025-01-15',
        ]);
        $doc1->setGeneratedContent($this->generateContent($templateNda, $doc1->getDataJson()));
        $manager->persist($doc1);

        // Document 2 - NDA En revue (il y a 4 mois)
        $doc2 = new Document();
        $doc2->setTemplate($templateNda);
        $doc2->setOrganization($org1);
        $doc2->setCreatedBy($editor);
        $doc2->setStatus(Document::STATUS_REVIEW);
        $doc2->setCreatedAt(new \DateTimeImmutable('-4 months'));
        $doc2->setDataJson([
            'partie_1_nom' => 'TechVision SA',
            'partie_1_forme_juridique' => 'Société Anonyme',
            'partie_1_adresse' => '100 avenue des Champs-Élysées, 75008 Paris',
            'partie_1_representant' => 'M. Philippe Bernard, Directeur Général',
            'partie_2_nom' => 'CloudServices SAS',
            'partie_2_forme_juridique' => 'Société par Actions Simplifiée',
            'partie_2_adresse' => '25 rue du Cloud, 92100 Boulogne',
            'partie_2_representant' => 'Mme Sophie Leroy, Présidente',
            'objet_collaboration' => 'l\'hébergement et la maintenance de l\'infrastructure cloud',
            'duree_confidentialite' => '5',
            'lieu_signature' => 'Paris',
            'date_signature' => '2025-01-20',
        ]);
        $doc2->setGeneratedContent($this->generateContent($templateNda, $doc2->getDataJson()));
        $manager->persist($doc2);

        // Document 3 - CGV Approuvé (il y a 3 mois)
        $doc3 = new Document();
        $doc3->setTemplate($templateCgv);
        $doc3->setOrganization($org1);
        $doc3->setCreatedBy($admin);
        $doc3->setStatus(Document::STATUS_APPROVED);
        $doc3->setCreatedAt(new \DateTimeImmutable('-3 months'));
        $doc3->setDataJson([
            'entreprise_nom' => 'WebAgency Pro',
            'entreprise_forme_juridique' => 'SAS',
            'entreprise_capital' => '50000',
            'entreprise_adresse' => '8 boulevard Haussmann, 75009 Paris',
            'entreprise_siret' => '123 456 789 00012',
            'type_prestation' => 'développement web et maintenance',
            'prix_ht_ttc' => 'HT',
            'tarif_horaire' => '85',
            'delai_paiement' => '30',
            'date_redaction' => '2025-01-10',
        ]);
        $doc3->setGeneratedContent($this->generateContent($templateCgv, $doc3->getDataJson()));
        $manager->persist($doc3);

        // Document 4 - Contrat SaaS Signé (il y a 2 mois)
        $doc4 = new Document();
        $doc4->setTemplate($templateSaas);
        $doc4->setOrganization($org1);
        $doc4->setCreatedBy($admin);
        $doc4->setStatus(Document::STATUS_SIGNED);
        $doc4->setCreatedAt(new \DateTimeImmutable('-2 months'));
        $doc4->setDataJson([
            'client_nom' => 'Startup Innov\'',
            'duree_contrat' => '12',
            'date_debut' => '2025-02-01',
            'montant_mensuel' => '299',
        ]);
        $doc4->setGeneratedContent($this->generateContent($templateSaas, $doc4->getDataJson()));
        $manager->persist($doc4);

        // Document 5 - Contrat SaaS Archivé (il y a 1 mois)
        $doc5 = new Document();
        $doc5->setTemplate($templateSaas);
        $doc5->setOrganization($org1);
        $doc5->setCreatedBy($admin);
        $doc5->setStatus(Document::STATUS_ARCHIVED);
        $doc5->setCreatedAt(new \DateTimeImmutable('-1 month'));
        $doc5->setDataJson([
            'client_nom' => 'OldClient SARL',
            'duree_contrat' => '6',
            'date_debut' => '2024-06-01',
            'montant_mensuel' => '199',
        ]);
        $doc5->setGeneratedContent($this->generateContent($templateSaas, $doc5->getDataJson()));
        $manager->persist($doc5);

        // Document 6 - NDA récent (il y a 2 semaines)
        $doc6 = new Document();
        $doc6->setTemplate($templateNda);
        $doc6->setOrganization($org1);
        $doc6->setCreatedBy($editor);
        $doc6->setStatus(Document::STATUS_DRAFT);
        $doc6->setCreatedAt(new \DateTimeImmutable('-2 weeks'));
        $doc6->setDataJson([
            'partie_1_nom' => 'InnoTech Labs',
            'partie_1_forme_juridique' => 'SAS',
            'partie_1_adresse' => '50 rue de la Recherche, 31000 Toulouse',
            'partie_1_representant' => 'Dr. Anne Moreau, Directrice R&D',
            'partie_2_nom' => 'DataSecure',
            'partie_2_forme_juridique' => 'SARL',
            'partie_2_adresse' => '12 rue des Serveurs, 33000 Bordeaux',
            'partie_2_representant' => 'M. Thomas Blanc, Gérant',
            'objet_collaboration' => 'audit de sécurité informatique',
            'duree_confidentialite' => '10',
            'lieu_signature' => 'Toulouse',
            'date_signature' => '2025-12-01',
        ]);
        $doc6->setGeneratedContent($this->generateContent($templateNda, $doc6->getDataJson()));
        $manager->persist($doc6);

        // Document 7 - CGV récent (il y a 5 jours)
        $doc7 = new Document();
        $doc7->setTemplate($templateCgv);
        $doc7->setOrganization($org1);
        $doc7->setCreatedBy($validator);
        $doc7->setStatus(Document::STATUS_REVIEW);
        $doc7->setCreatedAt(new \DateTimeImmutable('-5 days'));
        $doc7->setDataJson([
            'entreprise_nom' => 'DesignStudio',
            'entreprise_forme_juridique' => 'EURL',
            'entreprise_capital' => '10000',
            'entreprise_adresse' => '22 rue des Arts, 13001 Marseille',
            'entreprise_siret' => '987 654 321 00045',
            'type_prestation' => 'création graphique et identité visuelle',
            'prix_ht_ttc' => 'HT',
            'tarif_horaire' => '65',
            'delai_paiement' => '45',
            'date_redaction' => '2025-12-07',
        ]);
        $doc7->setGeneratedContent($this->generateContent($templateCgv, $doc7->getDataJson()));
        $manager->persist($doc7);

        // Document 8 - Document aujourd'hui
        $doc8 = new Document();
        $doc8->setTemplate($templateSaas);
        $doc8->setOrganization($org1);
        $doc8->setCreatedBy($admin);
        $doc8->setStatus(Document::STATUS_DRAFT);
        $doc8->setCreatedAt(new \DateTimeImmutable('now'));
        $doc8->setDataJson([
            'client_nom' => 'FutureTech',
            'duree_contrat' => '24',
            'date_debut' => '2026-01-01',
            'montant_mensuel' => '499',
        ]);
        $doc8->setGeneratedContent($this->generateContent($templateSaas, $doc8->getDataJson()));
        $manager->persist($doc8);

        // Document 9 - Cabinet Martin (il y a 3 mois)
        $doc9 = new Document();
        $doc9->setTemplate($templateTravail);
        $doc9->setOrganization($org2);
        $doc9->setCreatedBy($user);
        $doc9->setStatus(Document::STATUS_SIGNED);
        $doc9->setCreatedAt(new \DateTimeImmutable('-3 months'));
        $doc9->setDataJson([
            'employeur_nom' => 'Cabinet Martin',
            'salarie_nom' => 'Julie Petit',
            'poste' => 'Juriste',
            'date_debut' => '2025-10-01',
            'salaire_brut' => '3500',
            'heures_semaine' => '35',
        ]);
        $doc9->setGeneratedContent($this->generateContent($templateTravail, $doc9->getDataJson()));
        $manager->persist($doc9);

        // Document 10 - Cabinet Martin (il y a 1 mois)
        $doc10 = new Document();
        $doc10->setTemplate($templateTravail);
        $doc10->setOrganization($org2);
        $doc10->setCreatedBy($user);
        $doc10->setStatus(Document::STATUS_DRAFT);
        $doc10->setCreatedAt(new \DateTimeImmutable('-1 month'));
        $doc10->setDataJson([
            'employeur_nom' => 'Cabinet Martin',
            'salarie_nom' => 'Marc Durand',
            'poste' => 'Assistant juridique',
            'date_debut' => '2026-01-15',
            'salaire_brut' => '2200',
            'heures_semaine' => '35',
        ]);
        $doc10->setGeneratedContent($this->generateContent($templateTravail, $doc10->getDataJson()));
        $manager->persist($doc10);

        $manager->flush();
    }

    private function generateContent(Template $template, array $data): string
    {
        $content = $template->getBodyMarkdown();

        // Formatter les dates en français
        $formatter = new \IntlDateFormatter(
            'fr_FR',
            \IntlDateFormatter::LONG,
            \IntlDateFormatter::NONE
        );

        foreach ($data as $key => $value) {
            // Détecter si c'est une date (format YYYY-MM-DD)
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
                $date = new \DateTime($value);
                $value = $formatter->format($date);
            }

            $content = str_replace('{{' . $key . '}}', $value, $content);
        }

        return $content;
    }
}
