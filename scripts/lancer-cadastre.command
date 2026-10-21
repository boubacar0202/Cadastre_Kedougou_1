#!/bin/zsh
# Double-cliquez ce fichier pour lancer le serveur PHP et Vite.
export PATH="/usr/local/bin:/opt/homebrew/bin:/usr/bin:/bin:$PATH"

PROJET="/Applications/XAMPP/xamppfiles/htdocs/Cadastre_Kedougou"
URL="http://127.0.0.1:8002"

cd "$PROJET" || {
  echo "Dossier introuvable : $PROJET"
  read -r "?Appuyez sur Entrée pour fermer."
  exit 1
}

if [[ ! -x "$PROJET/node_modules/.bin/concurrently" ]]; then
  echo "Les dépendances npm ne sont pas installées."
  echo "Dans le dossier du projet, lancez : npm install"
  read -r "?Appuyez sur Entrée pour fermer."
  exit 1
fi

php_deja=0
vite_deja=0
if lsof -nP -iTCP:8002 -sTCP:LISTEN >/dev/null 2>&1; then
  php_deja=1
  echo "Le serveur PHP tourne déjà sur le port 8002."
fi
if lsof -nP -iTCP:5176 -sTCP:LISTEN >/dev/null 2>&1; then
  vite_deja=1
  echo "Vite tourne déjà sur le port 5176."
fi

if [[ $php_deja -eq 1 && $vite_deja -eq 1 ]]; then
  open "$URL"
  echo "L'application est déjà lancée : $URL"
  read -r "?Appuyez sur Entrée pour fermer."
  exit 0
fi

(sleep 3 && open "$URL") &

echo "Pour arrêter, appuyez sur Ctrl+C ou fermez cette fenêtre."
echo ""

if [[ $php_deja -eq 0 && $vite_deja -eq 0 ]]; then
  "$PROJET/node_modules/.bin/concurrently" --kill-others-on-fail -n php,vite -c blue,green \
    "php artisan serve --port=8002" \
    "npm run dev -- --host"
elif [[ $php_deja -eq 0 ]]; then
  php artisan serve --port=8002
else
  npm run dev -- --host
fi
