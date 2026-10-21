<?php

namespace App\Http\Middleware;

use App\Models\ActiviteUtilisateur;
use App\Models\Arrivee;
use App\Models\Depart;
use App\Models\Mutation;
use App\Models\Terrain;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class JournaliserActivite
{
    private static ?bool $tableReady = null;

    private static ?bool $detailReady = null;

    public function handle(Request $request, Closure $next): Response
    {
        $etatAvant = $this->etatAvantAction($request);

        $response = $next($request);

        $userId = $request->user()?->id;
        $action = $this->actionFor($request);
        if (! $userId || ! $action || ! $this->tableReady()) {
            return $response;
        }

        $status = $response->getStatusCode();
        $recuperationManquee = $status === 404 && $action === 'recherche';
        if ($status >= 400 && ! $recuperationManquee) {
            return $response;
        }

        if (! $recuperationManquee && $request->hasSession() && $request->session()->has('errors')) {
            return $response;
        }

        try {
            $payload = [
                'user_id' => $userId,
                'action' => $action,
            ];

            if ($this->detailReady()) {
                $path = trim($request->path(), '/');
                $module = $this->moduleFor($path);
                $payload['methode'] = $request->method();
                $payload['chemin'] = mb_substr($path, 0, 255);
                $payload['module'] = $module;
                $payload['libelle'] = $this->libelleFor($action, $module, $path);
                if ($action === 'suppression') {
                    $payload['reference'] = $this->journalAvecDossier(
                        $this->numeroConserve($etatAvant, $request) ?? $this->numeroDepuisChemin($path),
                        $this->referencesDepuisEtat($etatAvant) ?? $this->referenceFor($request)
                    );
                } elseif ($action === 'modification' && $etatAvant !== null) {
                    $changements = array_key_exists('listes', $etatAvant)
                        ? $this->changementsSecretariat($request, $etatAvant)
                        : $this->changementsChamps($request, $etatAvant);
                    $payload['reference'] = $this->journalAvecDossier(
                        $this->numeroConserve($etatAvant, $request) ?? $this->numeroDepuisChemin($path),
                        $changements ?? 'Modification enregistrée'
                    );
                } else {
                    $detail = $this->estRecuperation($path)
                        ? ($this->ficheRecuperation($path) ?? $this->referenceFor($request))
                        : $this->referenceFor($request);
                    $payload['reference'] = $this->journalAvecDossier(
                        $this->numeroDepuisRequete($request)
                            ?? $this->numeroDepuisReponse($response)
                            ?? $this->numeroDepuisChemin($path),
                        $detail
                    );
                }

                if ($action === 'recherche' && $this->poursuivreRechercheEnCours($userId, $payload)) {
                    return $response;
                }
            }

            ActiviteUtilisateur::create($payload);
        } catch (\Throwable) {
            // Le journal ne doit pas interrompre le traitement.
        }

        return $response;
    }

    private function actionFor(Request $request): ?string
    {
        $path = trim($request->path(), '/');

        if ($this->isIgnored($path)) {
            return null;
        }

        $method = $request->method();

        if ($method === 'DELETE' || $this->estSuppression($request)) {
            return 'suppression';
        }

        if (in_array($method, ['PUT', 'PATCH'], true)) {
            return 'modification';
        }

        if ($method === 'POST' && $this->isSearchPath($path)) {
            return 'recherche';
        }

        if ($method === 'POST') {
            return 'enregistrement';
        }

        if ($method === 'GET' && ($this->hasSearch($request) || $this->estConsultation($path) || $this->estConsultationFiche($path))) {
            return 'recherche';
        }

        return null;
    }

    private function isIgnored(string $path): bool
    {
        $ignored = [
            'login',
            'logout',
            'register',
            'forgot-password',
            'reset-password',
            'sanctum/csrf-cookie',
            'api/chart-data',
            'api/stats',
            'api/cession-data',
            'api/evaluation-utilisateur',
            'messages/unread-total',
            'messages/unread-by-user',
            'messages/unread-per-user',
            'fetchReferencesLotissement',
            'dossier/next',
            'dossier/last',
            'departements',
            'arrondissements',
            'communes',
            'lotissements',
            'arrivee/next',
            'depart/next',
            'secretariat/dernier-ordre',
            'api/shapefiles',
            'api/points-densifies',
            'api/messages',
            'api/plan/previsualiser',
            'parametre/code/verify',
        ];

        foreach ($ignored as $prefix) {
            if ($path === $prefix || str_starts_with($path, $prefix.'/') || str_starts_with($path, $prefix)) {
                return true;
            }
        }

        return str_starts_with($path, 'livewire')
            || str_starts_with($path, 'verifier-code')
            || str_starts_with($path, 'code/verify');
    }

    private function isSearchPath(string $path): bool
    {
        return (bool) preg_match('/recherch|search|fetch-reference|verification|verify|parcelles|verifier-nicad/i', $path);
    }

    private function hasSearch(Request $request): bool
    {
        foreach (['numero', 'prenomNom', 'refServiceRendu', 'q', 'search', 'reference', 'query', 'nicad', 'txt_reference', 'nom', 'titulaire', 'commune', 'mois', 'annee', 'section', 'parcelle', 'lotissement'] as $key) {
            $value = $request->query($key);
            if (is_string($value) && trim($value) !== '') {
                return true;
            }
        }

        return false;
    }

    private function estConsultation(string $path): bool
    {
        return (bool) preg_match('#^(parcelles|verifier-nicad)/[^/]+$#', $path);
    }

    private function tableReady(): bool
    {
        if (self::$tableReady !== true) {
            self::$tableReady = Schema::hasTable('activites_utilisateurs');
        }

        return self::$tableReady === true;
    }

    private function detailReady(): bool
    {
        if (self::$detailReady !== true) {
            self::$detailReady = Schema::hasColumn('activites_utilisateurs', 'libelle');
        }

        return self::$detailReady === true;
    }

    private function moduleFor(string $path): string
    {
        if ($path === 'dossier/verifyDossier') {
            return 'Secrétariat';
        }

        if ($path === 'dossier/verifyLotissement') {
            return 'Mutation';
        }

        if ($path === 'fetch-references-arrivee' || preg_match('#^(depart|instancedepart/depart)(/|$)#', $path)) {
            return 'Départ';
        }

        if (preg_match('#^(arrivee|instancearrivee/arrivee)(/|$)#', $path)) {
            return 'Arrivée';
        }

        $rules = [
            'secretariat' => 'Secrétariat',
            'geometre' => 'Géomètre',
            'matrice' => 'Matrice cadastrale',
            'mutation' => 'Mutation',
            'shapefile' => 'Shapefile',
            'generer-polygone' => 'Plan',
            'generer-angles' => 'Plan',
            'pointDensifier' => 'Point densifié',
            'lotissement' => 'Lotissement',
            'instancearrivee' => 'Instance arrivée',
            'instancedepart' => 'Instance départ',
            'arrivee' => 'Arrivée',
            'depart' => 'Départ',
            'instance' => 'Instance',
            'parcelle' => 'Plan',
            'plan' => 'Plan',
            'terrain' => 'Terrain',
            'donnee' => 'Données',
            'bareme' => 'Barème',
            'service' => 'Service',
            'admin' => 'Utilisateurs',
            'profile' => 'Profil',
            'parametre' => 'Paramètre',
            'message' => 'Message',
        ];

        foreach ($rules as $needle => $label) {
            if (str_contains($path, $needle)) {
                return $label;
            }
        }

        $segment = explode('/', $path)[0] ?? '';

        return $segment !== '' ? ucfirst($segment) : 'Application';
    }

    private function libelleFor(string $action, string $module, string $path = ''): string
    {
        if ($this->estRecuperation($path)) {
            return 'Récupération — '.$module;
        }

        $labels = [
            'enregistrement' => 'Enregistrement',
            'modification' => 'Modification',
            'suppression' => 'Suppression',
            'recherche' => 'Recherche',
        ];

        return ($labels[$action] ?? 'Traitement').' — '.$module;
    }

    private function estConsultationFiche(string $path): bool
    {
        return (bool) preg_match(
            '#^(secretariat/edit/\d+|secretariat/\d+/edit|arrivee/editarrivee/\d+|depart/editdepart/\d+|parametre/mutationEdit/\d+)$#',
            $path
        );
    }

    /**
     * Ouverture ou recherche d'un dossier / courrier déjà existant.
     */
    private function estRecuperation(string $path): bool
    {
        return $this->estConsultationFiche($path)
            || in_array($path, [
                'dossier/verifyDossier',
                'dossier/verifyLotissement',
                'fetch-references-arrivee',
            ], true);
    }

    private function referenceFor(Request $request): ?string
    {
        return $this->detailTraitement($request);
    }

    /**
     * Décrit le traitement à partir de tous les champs envoyés, quelle que soit la page.
     */
    private function detailTraitement(Request $request): ?string
    {
        $lignes = [];
        $vus = [];
        $libelles = $this->libellesConnus();

        foreach ($libelles as $cle => $label) {
            if (! $request->exists($cle)) {
                continue;
            }

            $vus[$cle] = true;
            $valeur = $request->input($cle);
            if (is_array($valeur)) {
                $lignes = array_merge($lignes, $this->lignesTableau($cle, $valeur));
                continue;
            }

            $texte = $this->texteDetail($cle, $valeur);
            if ($texte !== null) {
                $lignes[] = $label.' : '.$texte;
            }
        }

        foreach ([
            'occupants' => ['Occupant', $this->champsOccupant()],
            'occupantsBP' => ['Bâti', $this->champsBati()],
            'occupantsCA' => ['Cours aménagée', $this->champsCours()],
            'occupantsCL' => ['Clôture', $this->champsCloture()],
            'occupantsAP' => ['Aménagement', $this->champsAmenagement()],
        ] as $cle => [$groupe, $champs]) {
            if (isset($vus[$cle]) || ! $request->exists($cle) || ! is_array($request->input($cle))) {
                continue;
            }

            $vus[$cle] = true;
            $lignes = array_merge($lignes, $this->lignesListe($groupe, $request->input($cle), $champs));
        }

        foreach ($request->all() as $cle => $valeur) {
            if (! is_string($cle) || isset($vus[$cle]) || $this->cleIgnoree($cle)) {
                continue;
            }

            $vus[$cle] = true;
            if (is_array($valeur)) {
                $lignes = array_merge($lignes, $this->lignesTableau($cle, $valeur));
                continue;
            }

            $texte = $this->texteDetail($cle, $valeur);
            if ($texte !== null) {
                $lignes[] = $this->libelleChamp($cle).' : '.$texte;
            }
        }

        $path = trim($request->path(), '/');
        if (preg_match('#^parcelles/([^/]+)$#', $path, $correspondance)) {
            $lignes[] = 'Parcelle : '.urldecode($correspondance[1]);
        }
        if (preg_match('#^verifier-nicad/([^/]+)$#', $path, $correspondance)) {
            $lignes[] = 'NICAD : '.urldecode($correspondance[1]);
        }

        if (count($lignes) > 120) {
            $lignes = array_slice($lignes, 0, 120);
            $lignes[] = '... détail tronqué';
        }

        return $lignes === [] ? null : implode("\n", $lignes);
    }

    private function cleIgnoree(string $cle): bool
    {
        return in_array($cle, [
            '_token', '_method', 'password', 'password_confirmation', 'current_password',
            'code', 'deleteCode', 'adminCode', 'remember', 'page',
        ], true);
    }

    private function texteDetail(string $cle, mixed $valeur): ?string
    {
        if (is_bool($valeur)) {
            return $valeur ? 'Oui' : 'Non';
        }

        $texte = $this->valeurLisible($this->resoudreValeur($cle, $valeur));
        if ($texte === null || strcasecmp($texte, 'null') === 0 || $texte === 'undefined') {
            return null;
        }

        return $texte;
    }

    private function resoudreValeur(string $cle, mixed $valeur): mixed
    {
        if (! is_numeric($valeur) || (string) (int) $valeur !== (string) $valeur) {
            return $valeur;
        }

        $id = (int) $valeur;
        if ($id <= 0) {
            return $valeur;
        }

        $nom = match ($cle) {
            'slt_region' => \App\Models\Region::query()->whereKey($id)->value('slt_region'),
            'slt_departement' => \App\Models\Departement::query()->whereKey($id)->value('slt_departement'),
            'slt_arrondissement' => \App\Models\Arrondissement::query()->whereKey($id)->value('slt_arrondissement'),
            'slt_commune' => \App\Models\Commune::query()->whereKey($id)->value('slt_commune'),
            default => null,
        };

        return is_string($nom) && trim($nom) !== '' ? $nom : $valeur;
    }

    private function lignesTableau(string $cle, array $valeur): array
    {
        if ($cle === 'parcelles') {
            return $this->lignesListe('Parcelle', $valeur, [
                'section' => 'Section',
                'parcelle' => 'Parcelle',
                'lotissement' => 'Lotissement',
            ]);
        }

        $estListe = array_is_list($valeur);
        if ($estListe && ($valeur === [] || ! is_array($valeur[0]))) {
            $textes = [];
            foreach ($valeur as $element) {
                $texte = $this->texteDetail($cle, $element);
                if ($texte !== null) {
                    $textes[] = $texte;
                }
            }

            return $textes === [] ? [] : [$this->libelleChamp($cle).' : '.implode(', ', array_slice($textes, 0, 20))];
        }

        $champs = [];
        foreach ($valeur as $element) {
            if (! is_array($element)) {
                continue;
            }
            foreach (array_keys($element) as $sousCle) {
                if (is_string($sousCle) && ! $this->cleIgnoree($sousCle)) {
                    $champs[$sousCle] = $this->libelleChamp($sousCle);
                }
            }
        }

        if ($champs === []) {
            return [];
        }

        $lignes = $this->lignesListe($this->libelleChamp($cle), $valeur, $champs);
        if (count($lignes) > 40) {
            $reste = count($lignes) - 40;
            $lignes = array_slice($lignes, 0, 40);
            $lignes[] = '... et '.$reste.' autre(s)';
        }

        return $lignes;
    }

    private function libelleChamp(string $cle): string
    {
        $connus = $this->libellesConnus();
        if (isset($connus[$cle])) {
            return $connus[$cle];
        }

        $texte = preg_replace('/^(txt|slt|nbr|dt|eml|tel|rd|ussu)_/i', '', $cle) ?? $cle;
        $texte = preg_replace('/(Mutation|Point|Note|cd|MT|PR|TG|PA|PM)$/', ' $1', (string) $texte);
        $texte = trim(str_replace('_', ' ', (string) $texte));

        return $texte !== '' ? mb_convert_case($texte, MB_CASE_TITLE, 'UTF-8') : $cle;
    }

    private function libellesConnus(): array
    {
        static $libelles = null;
        if ($libelles !== null) {
            return $libelles;
        }

        $libelles = $this->champsSecretariat()
            + $this->champsArrivee()
            + $this->champsDepart()
            + $this->champsMutation()
            + [
            'slt_region' => 'Région',
            'slt_departement' => 'Département',
            'slt_arrondissement' => 'Arrondissement',
            'slt_commune' => 'Commune',
            'txt_numdordre' => 'N° d\'ordre',
            'txt_numbordereau' => 'N° bordereau',
            'txt_numdossier' => 'N° dossier',
            'txt_numdossiercd' => 'N° dossier',
            'txt_caractere' => 'Caractère',
            'dt_datearrivee' => 'Date d\'arrivée',
            'txt_typecourrier' => 'Type de courrier',
            'txt_numcourier' => 'N° courrier',
            'dt_datecourier' => 'Date du courrier',
            'txt_reference' => 'Référence',
            'txt_categorie' => 'Catégorie',
            'txt_designation' => 'Désignation',
            'txt_prenomNom' => 'Prénom et nom',
            'txt_numLot' => 'N° lot',
            'txt_superficie' => 'Superficie',
            'txt_numTF' => 'N° titre foncier',
            'dt_date' => 'Date',
            'txt_heure' => 'Heure',
            'txt_lieu' => 'Lieu',
            'txt_nombrepiece' => 'Nombre de pièces',
            'txt_objet' => 'Objet',
            'txt_expediteur' => 'Expéditeur',
            'txt_agenttraiteur' => 'Agent traiteur',
            'txt_observation' => 'Observation',
            'fichierPDF' => 'Fichier PDF',
            'txt_numdordrecd' => 'N° d\'ordre départ',
            'txt_caracterecd' => 'Caractère départ',
            'dt_datecouriercd' => 'Date du courrier départ',
            'txt_avisReponsecd' => 'Avis de réponse',
            'txt_typecourriercd' => 'Type de courrier départ',
            'txt_categoriecd' => 'Catégorie départ',
            'txt_referencecourierarriveecd' => 'Référence courrier arrivée',
            'txt_referencecourierdepartcd' => 'Référence courrier départ',
            'txt_nombrepiececd' => 'Nombre de pièces départ',
            'txt_referencecd' => 'Référence départ',
            'txt_denomination' => 'Dénomination',
            'txt_nombreParcelle' => 'Nombre de parcelles',
            'txt_surfaceLotissement' => 'Surface du lotissement',
            'txt_documentsAfferants' => 'Documents',
            'txt_nomPoint' => 'Nom du point',
            'txt_dateCreationPoint' => 'Date de création',
            'txt_descriptionPoint' => 'Description',
            'txt_situationPoint' => 'Situation',
            'txt_estPoint' => 'Est',
            'txt_nordPoint' => 'Nord',
            'txt_hauteurPoint' => 'Hauteur',
            'fichierPDFPoint' => 'Fichier PDF',
            'txt_numeroNote' => 'N° de note',
            'txt_typeNote' => 'Type de note',
            'txt_titreNote' => 'Titre',
            'groupe' => 'Groupe',
            'type' => 'Type',
            'prix' => 'Prix',
            'ordre' => 'Ordre',
            'actif' => 'Actif',
            'name' => 'Nom',
            'email' => 'E-mail',
            'role' => 'Rôle',
            'numero' => 'Numéro',
            'nicad' => 'NICAD',
            'titulaire' => 'Titulaire',
            'commune' => 'Commune',
            'mois' => 'Mois',
            'annee' => 'Année',
            'section' => 'Section',
            'parcelle' => 'Parcelle',
            'lotissement' => 'Lotissement',
            'prenomNom' => 'Prénom et nom',
            'refServiceRendu' => 'Service rendu',
            'txt_nicad_mutation' => 'NICAD mutation',
            'txt_nicadMutation' => 'NICAD mutation',
            'txt_numeroMutation' => 'N° mutation',
            'categorie' => 'Catégorie',
            'q' => 'Recherche',
            'search' => 'Recherche',
            'query' => 'Recherche',
        ];

        return $libelles;
    }

    private function estFormulaireSecretariat(Request $request): bool
    {
        $path = trim($request->path(), '/');

        return str_starts_with($path, 'secretariat')
            && ($request->exists('txt_num_dossier') || $request->exists('slt_titulaire') || $request->exists('txt_nicad'));
    }

    private function estMiseAJourSecretariat(Request $request): bool
    {
        return in_array($request->method(), ['PUT', 'PATCH'], true)
            && (bool) preg_match('#^secretariat/\d+$#', trim($request->path(), '/'));
    }

    private function estSuppression(Request $request): bool
    {
        $path = trim($request->path(), '/');

        return (bool) preg_match('#/(delete|destroy)(/|$)#', $path)
            || $this->estSuppressionDossier($request);
    }

    private function estSuppressionDossier(Request $request): bool
    {
        $path = trim($request->path(), '/');

        return (bool) preg_match('#^(terrain/delete|terrains)/\d+$#', $path)
            || (bool) preg_match('#^parametre/mutation/destroy/\d+$#', $path);
    }

    /**
     * Photographie le dossier avant modification ou suppression.
     * Le numéro conservé est celui d'avant l'action.
     */
    private function etatAvantAction(Request $request): ?array
    {
        $path = trim($request->path(), '/');

        if ($this->estMiseAJourSecretariat($request) || preg_match('#^(terrain/delete|terrains)/\d+$#', $path)) {
            return $this->etatTerrain((int) basename($path));
        }

        if (preg_match('#^parametre/mutation/(?:destroy|update)/(\d+)$#', $path, $correspondance)) {
            return $this->etatPlat(Mutation::find((int) $correspondance[1]), $this->champsMutation());
        }

        if (preg_match('#^(?:arrivee/(\d+)(?:/pdf)?|instancearrivee/arrivee/(\d+))$#', $path, $correspondance)) {
            return $this->etatPlat(Arrivee::find($this->identifiantChemin($correspondance)), $this->champsArrivee());
        }

        if (preg_match('#^(?:depart/(\d+)(?:/pdf)?|instancedepart/depart/(\d+)|depart/delete-pdf/(\d+))$#', $path, $correspondance)) {
            return $this->etatPlat(Depart::find($this->identifiantChemin($correspondance)), $this->champsDepart());
        }

        return null;
    }

    private function etatPlat(?\Illuminate\Database\Eloquent\Model $modele, array $libelles): ?array
    {
        if (! $modele) {
            return null;
        }

        $champs = [];
        $lignes = [];
        foreach ($libelles as $cle => $label) {
            $valeur = $modele->getAttribute($cle);
            $champs[$cle] = $valeur instanceof \DateTimeInterface ? $valeur->format('Y-m-d') : $valeur;
            if (in_array($cle, ['txt_num_dossier', 'txt_numdossier', 'txt_numdossiercd', 'txt_numeroMutation'], true)) {
                continue;
            }

            $texte = $this->valeurLisible($champs[$cle]);
            if ($texte !== null) {
                $lignes[] = $label.' : '.$texte;
            }
        }

        return [
            'champs' => $champs,
            'libelles' => $libelles,
            'lignes' => $lignes,
        ];
    }

    private function changementsChamps(Request $request, array $etat): ?string
    {
        $libelles = $etat['libelles'] ?? [];
        $lignes = [];

        foreach ($libelles as $cle => $label) {
            if (! $request->exists($cle)) {
                continue;
            }

            $ancien = $etat['champs'][$cle] ?? null;
            $nouveau = $request->input($cle);
            if ($this->normaliserValeur($ancien) === $this->normaliserValeur($nouveau)) {
                continue;
            }

            $lignes[] = $this->ligneChangement($label, $ancien, $nouveau);
        }

        return $lignes === [] ? null : implode("\n", $lignes);
    }

    private function identifiantChemin(array $correspondance): int
    {
        foreach ($correspondance as $index => $valeur) {
            if ($index === 0 || $valeur === null || $valeur === '') {
                continue;
            }

            return (int) $valeur;
        }

        return 0;
    }

    /**
     * Le numéro affiché en tête reste l'ancien si le champ a été modifié.
     */
    private function numeroConserve(?array $etat, Request $request): ?string
    {
        if ($etat === null) {
            return $this->numeroDepuisRequete($request);
        }

        foreach (['txt_num_dossier', 'txt_numeroMutation', 'txt_numdossier', 'txt_numdossiercd'] as $cle) {
            $ancien = $this->texteNumero($etat['champs'][$cle] ?? null);
            if ($ancien !== null) {
                return $ancien;
            }
        }

        return $this->numeroDepuisRequete($request);
    }

    private function numeroDepuisRequete(Request $request): ?string
    {
        foreach (['txt_num_dossier', 'txt_numeroMutation', 'num_dossier', 'txt_numdossier', 'txt_numdossiercd', 'numero'] as $cle) {
            $texte = $this->texteNumero($request->input($cle));
            if ($texte !== null) {
                return $texte;
            }
        }

        return null;
    }

    private function numeroDepuisReponse(Response $response): ?string
    {
        $contenu = $response->getContent();
        if (! is_string($contenu) || $contenu === '' || strlen($contenu) > 200000) {
            return null;
        }

        $contenu = ltrim($contenu);
        if ($contenu === '' || ($contenu[0] !== '{' && $contenu[0] !== '[')) {
            return null;
        }

        $json = json_decode($contenu, true);
        if (! is_array($json)) {
            return null;
        }

        return $this->texteNumero(
            $json['txt_num_dossier']
            ?? $json['num_dossier']
            ?? ($json['dossier']['txt_num_dossier'] ?? null)
            ?? ($json['dossier_info']['num_dossier'] ?? null)
        );
    }

    private function numeroDepuisChemin(string $path): ?string
    {
        if (preg_match('#^secretariat/edit/(\d+)$#', $path, $correspondance)
            || preg_match('#^secretariat/(\d+)/(?:edit|upload-pdf(?:Mutation)?)$#', $path, $correspondance)) {
            $terrain = Terrain::with('dossier')->find((int) $correspondance[1]);

            return $this->texteNumero($terrain?->dossier?->txt_num_dossier);
        }

        if (preg_match('#^arrivee/editarrivee/(\d+)$#', $path, $correspondance)) {
            return $this->texteNumero(Arrivee::find((int) $correspondance[1])?->txt_numdossier);
        }

        if (preg_match('#^depart/editdepart/(\d+)$#', $path, $correspondance)) {
            return $this->texteNumero(Depart::find((int) $correspondance[1])?->txt_numdossiercd);
        }

        if (preg_match('#^parametre/mutationEdit/(\d+)$#', $path, $correspondance)) {
            $mutation = Mutation::find((int) $correspondance[1]);

            return $this->texteNumero($mutation?->txt_num_dossier ?: $mutation?->txt_numeroMutation);
        }

        return null;
    }

    /**
     * Résumé affiché quand l'utilisateur ouvre un dossier ou un courrier existant.
     */
    private function ficheRecuperation(string $path): ?string
    {
        if (preg_match('#^secretariat/edit/(\d+)$#', $path, $correspondance)
            || preg_match('#^secretariat/(\d+)/edit$#', $path, $correspondance)) {
            return $this->lignesIdentite($this->etatTerrain((int) $correspondance[1]), [
                'txt_nicad' => 'NICAD',
                'txt_lotissement' => 'Lotissement',
                'txt_num_lotissement' => 'N° lotissement',
                'txt_num_section' => 'Section',
                'txt_num_parcelle' => 'Parcelle',
                'txt_prenom' => 'Prénom',
                'txt_nom' => 'Nom',
                'txt_denominationPM' => 'Dénomination',
            ]);
        }

        if (preg_match('#^arrivee/editarrivee/(\d+)$#', $path, $correspondance)) {
            return $this->resumeModele(Arrivee::find((int) $correspondance[1]), [
                'txt_numdordre' => 'N° d\'ordre',
                'txt_reference' => 'Référence',
                'txt_objet' => 'Objet',
                'txt_expediteur' => 'Expéditeur',
                'txt_categorie' => 'Catégorie',
                'txt_typecourrier' => 'Type de courrier',
                'dt_datearrivee' => 'Date d\'arrivée',
            ]);
        }

        if (preg_match('#^depart/editdepart/(\d+)$#', $path, $correspondance)) {
            return $this->resumeModele(Depart::find((int) $correspondance[1]), [
                'txt_numdordrecd' => 'N° d\'ordre',
                'txt_referencecd' => 'Référence',
                'txt_referencecourierarriveecd' => 'Référence courrier arrivée',
                'txt_objetcd' => 'Objet',
                'txt_destinatairecd' => 'Destinataire',
                'txt_categoriecd' => 'Catégorie',
                'dt_datecouriercd' => 'Date du courrier',
            ]);
        }

        if (preg_match('#^parametre/mutationEdit/(\d+)$#', $path, $correspondance)) {
            return $this->resumeModele(Mutation::find((int) $correspondance[1]), [
                'txt_numeroMutation' => 'N° mutation',
                'txt_nicadMutation' => 'NICAD',
                'txt_num_lotissementMutation' => 'N° lotissement',
                'txt_lotissementMutation' => 'Lotissement',
                'txt_prenomMutation' => 'Prénom acquéreur',
                'txt_nomMutation' => 'Nom acquéreur',
                'txt_denominationPM_Mutation' => 'Dénomination acquéreur',
                'dt_dateMutation' => 'Date de mutation',
            ]);
        }

        return null;
    }

    private function lignesIdentite(?array $etat, array $libelles): ?string
    {
        if ($etat === null) {
            return null;
        }

        $lignes = [];
        foreach ($libelles as $cle => $label) {
            $texte = $this->valeurLisible($etat['champs'][$cle] ?? null);
            if ($texte !== null) {
                $lignes[] = $label.' : '.$texte;
            }
        }

        return $lignes === [] ? null : implode("\n", $lignes);
    }

    private function resumeModele(?\Illuminate\Database\Eloquent\Model $modele, array $libelles): ?string
    {
        if (! $modele) {
            return null;
        }

        $lignes = [];
        foreach ($libelles as $cle => $label) {
            $valeur = $modele->getAttribute($cle);
            if ($valeur instanceof \DateTimeInterface) {
                $valeur = $valeur->format('Y-m-d');
            }

            $texte = $this->valeurLisible($valeur);
            if ($texte !== null) {
                $lignes[] = $label.' : '.$texte;
            }
        }

        return $lignes === [] ? null : implode("\n", $lignes);
    }

    private function texteNumero(mixed $value): ?string
    {
        if ($value instanceof \DateTimeInterface || is_array($value)) {
            return null;
        }

        $texte = trim((string) ($value ?? ''));
        if ($texte === '' || strcasecmp($texte, 'null') === 0 || $texte === 'undefined') {
            return null;
        }

        return $texte;
    }

    private function journalAvecDossier(?string $numero, ?string $detail): ?string
    {
        $lignes = [];
        $numero = $this->texteNumero($numero);
        if ($numero !== null) {
            $lignes[] = 'Numéro dossier : '.$numero;
        }

        foreach (preg_split("/\r\n|\n|\r/", trim((string) $detail)) ?: [] as $ligne) {
            $ligne = trim($ligne);
            if ($ligne === '') {
                continue;
            }

            if ($numero !== null && (
                $ligne === $numero
                || $ligne === 'Numéro dossier : '.$numero
                || $ligne === 'N° dossier : '.$numero
                || $ligne === 'Numéro : '.$numero
            )) {
                continue;
            }

            $lignes[] = $ligne;
        }

        return $lignes === [] ? null : implode("\n", $lignes);
    }

    private function referencesDepuisEtat(?array $etat): ?string
    {
        if ($etat === null) {
            return null;
        }

        if (isset($etat['lignes']) && is_array($etat['lignes'])) {
            $texte = implode("\n", array_filter($etat['lignes']));

            return $texte !== '' ? $texte : null;
        }

        $lignes = [];
        foreach ($this->champsSecretariat() as $cle => $label) {
            if ($cle === 'txt_num_dossier') {
                continue;
            }

            $texte = $this->valeurLisible($etat['champs'][$cle] ?? null);
            if ($texte !== null) {
                $lignes[] = $label.' : '.$texte;
            }
        }

        $listes = [
            'Occupant' => ['occupants', $this->champsOccupant()],
            'Bâti' => ['occupantsBP', $this->champsBati()],
            'Cours aménagée' => ['occupantsCA', $this->champsCours()],
            'Clôture' => ['occupantsCL', $this->champsCloture()],
            'Aménagement' => ['occupantsAP', $this->champsAmenagement()],
        ];

        foreach ($listes as $groupe => [$cle, $champs]) {
            $items = $etat['listes'][$cle] ?? [];
            if (is_array($items)) {
                $lignes = array_merge($lignes, $this->lignesListe($groupe, $items, $champs));
            }
        }

        return $lignes === [] ? null : implode("\n", $lignes);
    }

    /**
     * Photographie le dossier avant l'enregistrement, pour ne garder ensuite que les champs modifiés.
     */
    private function etatTerrain(int $id): ?array
    {
        $terrain = Terrain::with([
            'dossier',
            'titulaire',
            'references_cadastrales',
            'references_usages',
            'evaluations_terrains',
            'evaluations_batis',
            'evaluations_cours_amenagees',
            'evaluations_clotures',
            'evaluations_amenagements',
            'mutations',
        ])->find($id);

        if (! $terrain) {
            return null;
        }

        $usage = $terrain->references_usages->first();
        $mutation = $terrain->mutations->sortByDesc('id')->first();
        $titulaire = $terrain->titulaire;

        $groupes = [
            [$terrain->dossier, [
                'txt_num_dossier', 'txt_num_dordre', 'slt_service_rendu', 'txt_etat_cession',
                'txt_cession_definitive', 'dt_date_creation',
            ]],
            [$terrain, [
                'txt_nicad', 'slt_region', 'slt_departement', 'slt_commune', 'slt_arrondissement',
                'txt_lotissement', 'txt_HorsLotissement', 'txt_num_lotissement', 'txt_num_section',
                'txt_num_parcelle', 'txt_appartement', 'txt_num_titre', 'txt_titre_mere', 'nbr_surface',
                'slt_document_admin', 'txt_num_deliberation', 'dt_date_deliberation', 'slt_statut_nicad',
                'slt_motif_annulation_nicad', 'slt_signalement', 'slt_designationSignalement',
            ]],
            [$terrain->references_cadastrales, [
                'rd_immatriculation_terrain', 'slt_dependant_domaine', 'ussu_bornage', 'slt_lf',
                'txt_num_requisition', 'txt_surface_bornage', 'dt_date_bornage', 'txt_nom_geometre',
            ]],
            [$titulaire, [
                'slt_titulaire', 'txt_nationalite', 'slt_civilite', 'txt_prenom', 'txt_nom', 'slt_piece',
                'txt_numPiece', 'dt_date_delivrance', 'dt_date_naissance', 'txt_lieu_naissance', 'txt_adresse',
                'tel_telephone', 'txt_ninea', 'eml_email', 'txt_representant', 'tel_telRepresentant',
                'slt_categoriePM', 'txt_formJuridiquePM', 'txt_denominationPM', 'txt_nineaPM',
                'txt_perssonneRepresentantPM', 'txt_fonctionPM', 'txt_telephonePM', 'txt_emailPM', 'txt_adressePM',
                'slt_etablissementPA', 'txt_personneResponsablePA', 'txt_fonctionResponsablePA',
                'txt_telephonePA', 'txt_emailPA',
            ]],
            [$mutation, [
                'slt_titulaireMutation', 'txt_prenomMutation', 'txt_nomMutation', 'txt_nationaliteMutation',
                'slt_civiliteMutation', 'slt_pieceMutation', 'txt_numPieceMutation', 'dt_date_delivranceMutation',
                'dt_date_naissanceMutation', 'txt_lieu_naissanceMutation', 'txt_adresseMutation',
                'tel_telephoneMutation', 'txt_representantMutation', 'tel_telRepresentantMutation',
                'txt_typeActeMutation', 'txt_nomNotaireMutation', 'dt_dateMutation', 'txt_natureMutation',
                'txt_jugementMutation', 'txt_observationMutation', 'slt_categoriePM_Mutation',
                'txt_formJuridiquePM_Mutation', 'txt_denominationPM_Mutation', 'txt_nineaPM_Mutation',
                'txt_perssonneRepresentantPM_Mutation', 'txt_fonctionPM_Mutation', 'txt_telephonePM_Mutation',
                'txt_emailPM_Mutation', 'txt_adressePM_Mutation', 'slt_etablissementPA_Mutation',
                'txt_personneResponsablePA_Mutation', 'txt_fonctionResponsablePA_Mutation',
                'txt_telephonePA_Mutation', 'txt_emailPA_Mutation', 'txt_ministereTutelePA_Mutation',
            ]],
            [$usage, ['slt_usage', 'slt_residence', 'nbr_montantLoyerTotal', 'nbr_TVATotal']],
            [$terrain->evaluations_terrains, [
                'txt_superficie_totale', 'txt_superficie_bati_sol', 'txt_superficie_non_bati', 'slt_secteur',
                'nbr_prix_metre_carre', 'nbr_valeur_terrain', 'nbr_valeurVenaleLimeuble', 'nbr_valeurLocative',
                'dt_dateEvaluation',
            ]],
            [$terrain->evaluations_batis, [
                'txt_dependant_domainePR', 'slt_categoriePR', 'currentCat', 'nbr_prix_metre_carrePR',
                'nbr_surface_bati_solPR', 'nbr_niveauPR', 'nbr_surface_utilePR', 'slt_coeffPR',
                'nbr_surface_corrigerPR', 'nbr_valeurPR', 'txt_valeur_terrain_bati',
            ]],
            [$terrain->evaluations_cours_amenagees, ['nbr_valeur_total_ca']],
            [$terrain->evaluations_clotures, ['nbr_valeur_total_clotur']],
            [$terrain->evaluations_amenagements, ['nbr_valeur_totale_ap']],
        ];

        $champs = [];
        foreach ($groupes as [$modele, $cles]) {
            foreach ($cles as $cle) {
                $champs[$cle] = $modele?->getAttribute($cle);
            }
        }
        $champs['txt_ministereTutelePA'] = $titulaire?->getAttribute('txt_ministèreTutelePA');

        return [
            'champs' => $champs,
            'listes' => [
                'occupants' => $this->lignesModele($terrain->references_usages, $this->champsOccupant()),
                'occupantsBP' => $this->lignesModele($terrain->evaluations_batis, $this->champsBati()),
                'occupantsCA' => $this->lignesModele($terrain->evaluations_cours_amenagees, $this->champsCours()),
                'occupantsCL' => $this->lignesModele($terrain->evaluations_clotures, $this->champsCloture()),
                'occupantsAP' => $this->lignesModele($terrain->evaluations_amenagements, $this->champsAmenagement()),
            ],
        ];
    }

    private function lignesModele(mixed $relation, array $champs): array
    {
        $modeles = $relation instanceof \Illuminate\Support\Collection
            ? $relation->values()
            : collect($relation ? [$relation] : []);

        return $modeles->map(function ($modele) use ($champs) {
            $ligne = [];
            foreach (array_keys($champs) as $cle) {
                $ligne[$cle] = $modele?->getAttribute($cle);
            }

            return $ligne;
        })->filter(fn (array $ligne) => $this->ligneRemplie($ligne))->values()->all();
    }

    private function ligneRemplie(array $ligne): bool
    {
        foreach ($ligne as $valeur) {
            if ($this->normaliserValeur($valeur) !== '') {
                return true;
            }
        }

        return false;
    }

    private function changementsSecretariat(Request $request, array $etat): ?string
    {
        $lignes = [];

        foreach ($this->champsSecretariat() as $cle => $label) {
            if (! $request->exists($cle)) {
                continue;
            }

            $ancien = $etat['champs'][$cle] ?? null;
            $nouveau = $request->input($cle);
            if ($this->normaliserValeur($ancien) === $this->normaliserValeur($nouveau)) {
                continue;
            }

            $lignes[] = $this->ligneChangement($label, $ancien, $nouveau);
        }

        $listes = [
            'Occupant' => ['occupants', $this->champsOccupant()],
            'Bâti' => ['occupantsBP', $this->champsBati()],
            'Cours aménagée' => ['occupantsCA', $this->champsCours()],
            'Clôture' => ['occupantsCL', $this->champsCloture()],
            'Aménagement' => ['occupantsAP', $this->champsAmenagement()],
        ];

        foreach ($listes as $groupe => [$cle, $champs]) {
            if (! $request->exists($cle)) {
                continue;
            }

            $anciennes = $etat['listes'][$cle] ?? [];
            $nouvelles = array_values(array_filter(
                $request->input($cle, []),
                fn ($item) => is_array($item) && $this->ligneRemplie($item)
            ));
            $total = max(count($anciennes), count($nouvelles));

            for ($i = 0; $i < $total; $i++) {
                foreach ($champs as $champ => $label) {
                    $ancien = $anciennes[$i][$champ] ?? null;
                    $nouveau = $nouvelles[$i][$champ] ?? null;
                    if ($this->normaliserValeur($ancien) === $this->normaliserValeur($nouveau)) {
                        continue;
                    }

                    $lignes[] = $groupe.' '.($i + 1).' — '.$this->ligneChangement($label, $ancien, $nouveau);
                }
            }
        }

        return $lignes === [] ? null : implode("\n", $lignes);
    }

    private function ligneChangement(string $label, mixed $ancien, mixed $nouveau): string
    {
        return $label.' : ( '.$this->afficherValeur($ancien).' => '.$this->afficherValeur($nouveau).' )';
    }

    private function afficherValeur(mixed $value): string
    {
        if ($value instanceof \Illuminate\Http\UploadedFile) {
            return $value->getClientOriginalName();
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        $texte = trim((string) ($value ?? ''));
        if ($texte === '' || strcasecmp($texte, 'null') === 0 || $texte === 'undefined') {
            return '—';
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}/', $texte)) {
            return substr($texte, 0, 10);
        }

        if (is_numeric($texte) && (float) $texte == 0.0) {
            return '—';
        }

        return $texte;
    }

    private function normaliserValeur(mixed $value): string
    {
        if ($value instanceof \Illuminate\Http\UploadedFile) {
            return 'fichier:'.$value->getClientOriginalName();
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        if (is_array($value) || is_bool($value) || $value === null) {
            return '';
        }

        $texte = trim((string) $value);
        if ($texte === '' || strcasecmp($texte, 'null') === 0 || $texte === 'undefined') {
            return '';
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}/', $texte)) {
            return substr($texte, 0, 10);
        }

        if (is_numeric($texte)) {
            $nombre = (float) $texte;
            if ($nombre == 0.0) {
                return '';
            }

            return floor($nombre) == $nombre
                ? (string) (int) $nombre
                : rtrim(rtrim(number_format($nombre, 6, '.', ''), '0'), '.');
        }

        return $texte;
    }

    private function referenceSecretariat(Request $request): ?string
    {
        $lignes = [];

        foreach ($this->champsSecretariat() as $key => $label) {
            $texte = $this->valeurLisible($request->input($key));
            if ($texte !== null) {
                $lignes[] = $label.' : '.$texte;
            }
        }

        $listes = [
            'Occupant' => ['occupants', $this->champsOccupant()],
            'Bâti' => ['occupantsBP', $this->champsBati()],
            'Cours aménagée' => ['occupantsCA', $this->champsCours()],
            'Clôture' => ['occupantsCL', $this->champsCloture()],
            'Aménagement' => ['occupantsAP', $this->champsAmenagement()],
        ];

        foreach ($listes as $groupe => [$key, $champs]) {
            $items = $request->input($key, []);
            if (is_array($items)) {
                $lignes = array_merge($lignes, $this->lignesListe($groupe, $items, $champs));
            }
        }

        return $lignes === [] ? null : implode("\n", $lignes);
    }

    private function lignesListe(string $groupe, array $items, array $champs): array
    {
        $lignes = [];
        $index = 0;

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $morceaux = [];
            foreach ($champs as $key => $label) {
                $texte = $this->valeurLisible($item[$key] ?? null);
                if ($texte !== null) {
                    $morceaux[] = $label.' : '.$texte;
                }
            }

            if ($morceaux === []) {
                continue;
            }

            $index++;
            $lignes[] = $groupe.' '.$index.' — '.implode(' · ', $morceaux);
        }

        return $lignes;
    }

    private function valeurLisible(mixed $value): ?string
    {
        if ($value instanceof \Illuminate\Http\UploadedFile) {
            return $value->getClientOriginalName();
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        if (is_array($value) || is_bool($value) || $value === null) {
            return null;
        }

        if (is_int($value) || is_float($value)) {
            return (float) $value == 0.0 ? null : (string) $value;
        }

        $texte = trim((string) $value);
        if ($texte === '' || $texte === '0' || $texte === '0.0' || $texte === '0.00') {
            return null;
        }

        return $texte;
    }

    private function champsArrivee(): array
    {
        return [
            'txt_numdordre' => 'N° d\'ordre',
            'txt_numbordereau' => 'N° bordereau',
            'txt_numdossier' => 'N° dossier',
            'txt_caractere' => 'Caractère',
            'dt_datearrivee' => 'Date d\'arrivée',
            'txt_typecourrier' => 'Type de courrier',
            'txt_numcourier' => 'N° courrier',
            'dt_datecourier' => 'Date du courrier',
            'txt_reference' => 'Référence',
            'txt_categorie' => 'Catégorie',
            'txt_designation' => 'Désignation',
            'txt_prenomNom' => 'Prénom et nom',
            'txt_numLot' => 'N° lot',
            'txt_superficie' => 'Superficie',
            'txt_numTF' => 'N° titre foncier',
            'txt_lotissement' => 'Lotissement',
            'dt_date' => 'Date',
            'txt_heure' => 'Heure',
            'txt_lieu' => 'Lieu',
            'txt_nombrepiece' => 'Nombre de pièces',
            'txt_objet' => 'Objet',
            'txt_expediteur' => 'Expéditeur',
            'txt_agenttraiteur' => 'Agent traiteur',
            'txt_observation' => 'Observation',
            'fichierPDF' => 'Fichier PDF',
        ];
    }

    private function champsDepart(): array
    {
        return [
            'txt_numdordrecd' => 'N° d\'ordre',
            'txt_numdossiercd' => 'N° dossier',
            'txt_caracterecd' => 'Caractère',
            'dt_datecouriercd' => 'Date du courrier',
            'txt_avisReponsecd' => 'Avis de réponse',
            'txt_typecourriercd' => 'Type de courrier',
            'txt_categoriecd' => 'Catégorie',
            'txt_referencecourierarriveecd' => 'Référence courrier arrivée',
            'txt_referencecourierdepartcd' => 'Référence courrier départ',
            'txt_nombrepiececd' => 'Nombre de pièces',
            'txt_referencecd' => 'Référence',
            'txt_prenomNomcd' => 'Prénom et nom',
            'txt_numLotcd' => 'N° lot',
            'txt_superficiecd' => 'Superficie',
            'txt_numTFcd' => 'N° titre foncier',
            'txt_lotissementcd' => 'Lotissement',
            'txt_objetcd' => 'Objet',
            'txt_destinatairecd' => 'Destinataire',
            'dt_dateenvoicd' => 'Date d\'envoi',
            'txt_referencereceptioncd' => 'Référence de réception',
            'txt_observationcd' => 'Observation',
            'txt_dureetraitementcd' => 'Durée de traitement',
            'fichierPDFcd' => 'Fichier PDF',
        ];
    }

    private function champsMutation(): array
    {
        return [
            'txt_numeroMutation' => 'N° mutation',
            'txt_nicadMutation' => 'NICAD',
            'txt_num_dossier' => 'N° dossier',
            'txt_num_lotissementMutation' => 'N° lotissement',
            'txt_lotissementMutation' => 'Lotissement',
            'slt_titulaireMT' => 'Type de titulaire',
            'txt_nationaliteMT' => 'Nationalité',
            'slt_civiliteMT' => 'Civilité',
            'txt_prenomMT' => 'Prénom titulaire',
            'txt_nomMT' => 'Nom titulaire',
            'slt_pieceMT' => 'Pièce',
            'txt_numPieceMT' => 'N° pièce',
            'dt_date_delivranceMT' => 'Date de délivrance',
            'dt_date_naissanceMT' => 'Date de naissance',
            'txt_lieu_naissanceMT' => 'Lieu de naissance',
            'txt_adresseMT' => 'Adresse',
            'tel_telephoneMT' => 'Téléphone',
            'txt_representantMT' => 'Représentant',
            'tel_telRepresentantMT' => 'Téléphone représentant',
            'slt_categoriePM_MT' => 'Catégorie personne morale',
            'txt_formJuridiquePM_MT' => 'Forme juridique',
            'txt_denominationPM_MT' => 'Dénomination titulaire',
            'txt_nineaPM_MT' => 'NINEA',
            'txt_personneRepresentantPM_MT' => 'Représentant personne morale',
            'txt_fonctionPM_MT' => 'Fonction',
            'txt_telephonePM_MT' => 'Téléphone personne morale',
            'txt_emailPM_MT' => 'E-mail personne morale',
            'txt_adressePM_MT' => 'Adresse personne morale',
            'slt_etablissementPA_MT' => 'Établissement',
            'txt_personneResponsablePA_MT' => 'Responsable',
            'txt_fonctionResponsablePA_MT' => 'Fonction du responsable',
            'txt_telephonePA_MT' => 'Téléphone administration',
            'txt_emailPA_MT' => 'E-mail administration',
            'txt_ministereTutelePA_MT' => 'Ministère de tutelle',
            'slt_titulaireMutation' => 'Type d\'acquéreur',
            'txt_prenomMutation' => 'Prénom acquéreur',
            'txt_nomMutation' => 'Nom acquéreur',
            'txt_nationaliteMutation' => 'Nationalité acquéreur',
            'slt_civiliteMutation' => 'Civilité acquéreur',
            'slt_pieceMutation' => 'Pièce acquéreur',
            'txt_numPieceMutation' => 'N° pièce acquéreur',
            'dt_date_delivranceMutation' => 'Date de délivrance acquéreur',
            'dt_date_naissanceMutation' => 'Date de naissance acquéreur',
            'txt_lieu_naissanceMutation' => 'Lieu de naissance acquéreur',
            'txt_adresseMutation' => 'Adresse acquéreur',
            'tel_telephoneMutation' => 'Téléphone acquéreur',
            'txt_representantMutation' => 'Représentant acquéreur',
            'tel_telRepresentantMutation' => 'Téléphone représentant acquéreur',
            'txt_typeActeMutation' => 'Type d\'acte',
            'txt_nomNotaireMutation' => 'Notaire',
            'dt_dateMutation' => 'Date de mutation',
            'txt_natureMutation' => 'Nature de mutation',
            'txt_jugementMutation' => 'Jugement',
            'txt_observationMutation' => 'Observation',
            'slt_categoriePM_Mutation' => 'Catégorie personne morale acquéreur',
            'txt_formJuridiquePM_Mutation' => 'Forme juridique acquéreur',
            'txt_denominationPM_Mutation' => 'Dénomination acquéreur',
            'txt_nineaPM_Mutation' => 'NINEA acquéreur',
            'txt_personneRepresentantPM_Mutation' => 'Représentant acquéreur',
            'txt_fonctionPM_Mutation' => 'Fonction acquéreur',
            'txt_telephonePM_Mutation' => 'Téléphone acquéreur',
            'txt_emailPM_Mutation' => 'E-mail acquéreur',
            'txt_adressePM_Mutation' => 'Adresse acquéreur',
            'slt_etablissementPA_Mutation' => 'Établissement acquéreur',
            'txt_personneResponsablePA_Mutation' => 'Responsable acquéreur',
            'txt_fonctionResponsablePA_Mutation' => 'Fonction responsable acquéreur',
            'txt_telephonePA_Mutation' => 'Téléphone administration acquéreur',
            'txt_emailPA_Mutation' => 'E-mail administration acquéreur',
            'txt_ministereTutelePA_Mutation' => 'Ministère de tutelle acquéreur',
            'fichierPDFMutation' => 'Fichier PDF',
        ];
    }

    private function champsSecretariat(): array
    {
        return [
            'txt_num_dossier' => 'N° dossier',
            'txt_num_dordre' => 'N° ordre',
            'slt_service_rendu' => 'Service rendu',
            'txt_etat_cession' => 'État de cession',
            'txt_cession_definitive' => 'Cession définitive',
            'dt_date_creation' => 'Date de création',
            'txt_nicad' => 'NICAD',
            'slt_statut_nicad' => 'Statut NICAD',
            'slt_motif_annulation_nicad' => 'Motif annulation NICAD',
            'txt_lotissement' => 'Lotissement',
            'txt_HorsLotissement' => 'Hors lotissement',
            'txt_num_lotissement' => 'N° lotissement',
            'txt_num_section' => 'Section',
            'txt_num_parcelle' => 'Parcelle',
            'txt_appartement' => 'Appartement',
            'txt_num_titre' => 'N° titre',
            'txt_titre_mere' => 'Titre mère',
            'nbr_surface' => 'Surface',
            'slt_document_admin' => 'Document administratif',
            'txt_num_deliberation' => 'N° délibération',
            'dt_date_deliberation' => 'Date délibération',
            'slt_signalement' => 'Signalement',
            'slt_designationSignalement' => 'Désignation du signalement',
            'rd_immatriculation_terrain' => 'Immatriculation',
            'slt_dependant_domaine' => 'Dépendant du domaine',
            'ussu_bornage' => 'Bornage',
            'slt_lf' => 'Livret foncier',
            'txt_num_requisition' => 'N° réquisition',
            'txt_surface_bornage' => 'Surface de bornage',
            'dt_date_bornage' => 'Date de bornage',
            'txt_nom_geometre' => 'Géomètre',
            'slt_titulaire' => 'Type de titulaire',
            'txt_nationalite' => 'Nationalité',
            'slt_civilite' => 'Civilité',
            'txt_prenom' => 'Prénom',
            'txt_nom' => 'Nom',
            'slt_piece' => 'Pièce',
            'txt_numPiece' => 'N° pièce',
            'dt_date_delivrance' => 'Date de délivrance',
            'dt_date_naissance' => 'Date de naissance',
            'txt_lieu_naissance' => 'Lieu de naissance',
            'txt_adresse' => 'Adresse',
            'tel_telephone' => 'Téléphone',
            'txt_ninea' => 'NINEA',
            'eml_email' => 'E-mail',
            'txt_representant' => 'Représentant',
            'tel_telRepresentant' => 'Téléphone représentant',
            'slt_categoriePM' => 'Catégorie personne morale',
            'txt_formJuridiquePM' => 'Forme juridique',
            'txt_denominationPM' => 'Dénomination',
            'txt_nineaPM' => 'NINEA personne morale',
            'txt_perssonneRepresentantPM' => 'Représentant personne morale',
            'txt_fonctionPM' => 'Fonction',
            'txt_telephonePM' => 'Téléphone personne morale',
            'txt_emailPM' => 'E-mail personne morale',
            'txt_adressePM' => 'Adresse personne morale',
            'slt_etablissementPA' => 'Établissement',
            'txt_personneResponsablePA' => 'Responsable',
            'txt_fonctionResponsablePA' => 'Fonction du responsable',
            'txt_telephonePA' => 'Téléphone administration',
            'txt_emailPA' => 'E-mail administration',
            'txt_ministereTutelePA' => 'Ministère de tutelle',
            'slt_titulaireMutation' => 'Type de titulaire mutation',
            'txt_prenomMutation' => 'Prénom mutation',
            'txt_nomMutation' => 'Nom mutation',
            'txt_nationaliteMutation' => 'Nationalité mutation',
            'slt_civiliteMutation' => 'Civilité mutation',
            'slt_pieceMutation' => 'Pièce mutation',
            'txt_numPieceMutation' => 'N° pièce mutation',
            'dt_date_delivranceMutation' => 'Date de délivrance mutation',
            'dt_date_naissanceMutation' => 'Date de naissance mutation',
            'txt_lieu_naissanceMutation' => 'Lieu de naissance mutation',
            'txt_adresseMutation' => 'Adresse mutation',
            'tel_telephoneMutation' => 'Téléphone mutation',
            'txt_representantMutation' => 'Représentant mutation',
            'tel_telRepresentantMutation' => 'Téléphone représentant mutation',
            'txt_typeActeMutation' => 'Type d\'acte',
            'txt_nomNotaireMutation' => 'Notaire',
            'dt_dateMutation' => 'Date de mutation',
            'txt_natureMutation' => 'Nature de mutation',
            'txt_jugementMutation' => 'Jugement',
            'txt_observationMutation' => 'Observation mutation',
            'slt_categoriePM_Mutation' => 'Catégorie personne morale mutation',
            'txt_formJuridiquePM_Mutation' => 'Forme juridique mutation',
            'txt_denominationPM_Mutation' => 'Dénomination mutation',
            'txt_nineaPM_Mutation' => 'NINEA mutation',
            'txt_perssonneRepresentantPM_Mutation' => 'Représentant mutation PM',
            'txt_fonctionPM_Mutation' => 'Fonction mutation',
            'txt_telephonePM_Mutation' => 'Téléphone mutation PM',
            'txt_emailPM_Mutation' => 'E-mail mutation PM',
            'txt_adressePM_Mutation' => 'Adresse mutation PM',
            'slt_etablissementPA_Mutation' => 'Établissement mutation',
            'txt_personneResponsablePA_Mutation' => 'Responsable mutation',
            'txt_fonctionResponsablePA_Mutation' => 'Fonction responsable mutation',
            'txt_telephonePA_Mutation' => 'Téléphone administration mutation',
            'txt_emailPA_Mutation' => 'E-mail administration mutation',
            'txt_ministereTutelePA_Mutation' => 'Ministère de tutelle mutation',
            'slt_usage' => 'Usage',
            'slt_residence' => 'Résidence',
            'nbr_montantLoyerTotal' => 'Montant loyer total',
            'nbr_TVATotal' => 'TVA totale',
            'txt_superficie_totale' => 'Superficie totale',
            'txt_superficie_bati_sol' => 'Superficie bâtie au sol',
            'txt_superficie_non_bati' => 'Superficie non bâtie',
            'slt_secteur' => 'Secteur',
            'nbr_prix_metre_carre' => 'Prix du m²',
            'nbr_valeur_terrain' => 'Valeur du terrain',
            'nbr_valeurVenaleLimeuble' => 'Valeur vénale',
            'nbr_valeurLocative' => 'Valeur locative',
            'dt_dateEvaluation' => 'Date d\'évaluation',
            'txt_dependant_domainePR' => 'Dépendant du domaine bâti',
            'slt_categoriePR' => 'Catégorie bâti',
            'currentCat' => 'Type de bâti',
            'nbr_prix_metre_carrePR' => 'Prix du m² bâti',
            'nbr_surface_bati_solPR' => 'Surface bâtie au sol',
            'nbr_niveauPR' => 'Niveau',
            'nbr_surface_utilePR' => 'Surface utile',
            'slt_coeffPR' => 'Coefficient bâti',
            'nbr_surface_corrigerPR' => 'Surface corrigée',
            'nbr_valeurPR' => 'Valeur du bâti',
            'txt_valeur_terrain_bati' => 'Valeur terrain bâti',
            'nbr_valeur_total_ca' => 'Valeur totale cours aménagées',
            'nbr_valeur_total_clotur' => 'Valeur totale clôtures',
            'nbr_valeur_totale_ap' => 'Valeur totale aménagements',
        ];
    }

    private function champsOccupant(): array
    {
        return [
            'txt_nomOccupantTG' => 'Nom',
            'txt_numAppartementTG' => 'Appartement',
            'txt_activiteTG' => 'Activité',
            'txt_nineaTG' => 'NINEA',
            'tel_telephoneTG' => 'Téléphone',
            'nbr_montantLoyerTG' => 'Loyer',
            'txt_dateLieuNaissanceTG' => 'Naissance',
            'txt_cniPasseportTG' => 'CNI / passeport',
            'dt_dateDelivranceTG' => 'Délivrance',
        ];
    }

    private function champsBati(): array
    {
        return [
            'slt_dependant_domaineTG' => 'Domaine',
            'slt_categorieTG' => 'Catégorie',
            'nbr_prix_metre_carreTG' => 'Prix du m²',
            'nbr_surface_bati_solTG' => 'Surface au sol',
            'nbr_niveauTG' => 'Niveau',
            'nbr_surface_utileTG' => 'Surface utile',
            'slt_coeffTG' => 'Coefficient',
            'nbr_surface_corrigerTG' => 'Surface corrigée',
            'nbr_valeurTG' => 'Valeur',
        ];
    }

    private function champsCours(): array
    {
        return [
            'slt_cours_amenager_totale' => 'Cours',
            'nbr_surface_ca_total' => 'Surface',
            'slt_categorie_ca_total' => 'Catégorie',
            'nbr_prix_metre_carre_ca_total' => 'Prix du m²',
            'nbr_coefficient_ca_total' => 'Coefficient',
            'nbr_valeur_ca_total' => 'Valeur',
        ];
    }

    private function champsCloture(): array
    {
        return [
            'slt_cours_amenager_clo' => 'Clôture',
            'nbr_longueur_avant_clo' => 'Longueur',
            'slt_categorie_clo' => 'Catégorie',
            'nbr_prix_metre_carre_clo' => 'Prix du m²',
            'nbr_coefficient_clo' => 'Coefficient',
            'nbr_valeur_clo' => 'Valeur',
        ];
    }

    private function champsAmenagement(): array
    {
        return [
            'txt_designation_am' => 'Désignation',
            'nbr_valeur_unitaire_am' => 'Valeur unitaire',
            'nbr_quantile_am' => 'Quantité',
            'slt_coeficien_am' => 'Coefficient',
            'nbr_valeur_am' => 'Valeur',
        ];
    }

    /**
     * Une saisie qui prolonge la précédente (boub, bouba, boubacar)
     * met à jour la même recherche au lieu d'en créer une nouvelle.
     */
    private function poursuivreRechercheEnCours(int $userId, array $payload): bool
    {
        $precedente = ActiviteUtilisateur::query()
            ->where('user_id', $userId)
            ->where('action', 'recherche')
            ->where('chemin', $payload['chemin'])
            ->where('updated_at', '>=', now()->subSeconds(20))
            ->latest('id')
            ->first();

        if (! $precedente || ! $this->memeSaisie($precedente->reference, $payload['reference'] ?? null)) {
            return false;
        }

        $precedente->update([
            'methode' => $payload['methode'],
            'reference' => $payload['reference'],
        ]);

        return true;
    }

    private function memeSaisie(?string $precedente, ?string $actuelle): bool
    {
        $precedente = trim((string) $precedente);
        $actuelle = trim((string) $actuelle);

        if ($precedente === '' || $actuelle === '') {
            return false;
        }

        return mb_stripos($actuelle, $precedente) === 0
            || mb_stripos($precedente, $actuelle) === 0;
    }
}
