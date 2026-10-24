function actifs(baremes) {
    return (baremes ?? []).filter((bareme) => bareme.actif !== false && bareme.actif !== 0);
}

function memeGroupe(bareme, groupe, type) {
    return bareme.groupe === groupe && (bareme.type || "") === type;
}

export function mapsFromBaremes(baremes = []) {
    const toMap = (groupe, type = "") =>
        Object.fromEntries(
            actifs(baremes)
                .filter((bareme) => memeGroupe(bareme, groupe, type))
                .map((bareme) => [String(bareme.code), Number(bareme.prix)])
        );

    return {
        categories: {
            "Maison individuelle": toMap("batiment", "Maison individuelle"),
            "Immeuble collectif": toMap("batiment", "Immeuble collectif"),
        },
        prixParSecteur: toMap("secteur"),
        coeffCAValues: toMap("cours_amenagee"),
        coeffCLValues: toMap("cloture"),
    };
}

export function valeurBareme(baremes = [], groupe, code, type = "") {
    const row = actifs(baremes).find(
        (bareme) => memeGroupe(bareme, groupe, type) && String(bareme.code) === String(code)
    );

    return row ? Number(row.prix) : null;
}

export function coefficientBareme(baremes = [], code, fallback) {
    const valeur = valeurBareme(baremes, "coefficient", code);

    return valeur === null ? fallback : valeur;
}

function tauxLocatifHistorique(type, code) {
    const valeur = String(code);

    if ((type === "Immeuble collectif" && valeur === "A") || (type === "Maison individuelle" && valeur === "1")) {
        return 0.1344;
    }

    if (
        (type === "Immeuble collectif" && ["B", "C"].includes(valeur))
        || (type === "Maison individuelle" && ["2", "3"].includes(valeur))
    ) {
        return 0.12;
    }

    return 0.1;
}

export function tauxLocatifBareme(baremes = [], type, code) {
    if (!type || code === "" || code === null || code === undefined) {
        return 0;
    }

    const exact = valeurBareme(baremes, "taux_locatif", code, type);
    if (exact !== null) {
        return exact;
    }

    const defaut = valeurBareme(baremes, "taux_locatif", "defaut", type);
    if (defaut !== null) {
        return defaut;
    }

    return tauxLocatifHistorique(type, code);
}

export function baremeOptions(baremes = [], groupe, type = "", { numeric = false, withPrice = false } = {}) {
    return actifs(baremes)
        .filter((bareme) => memeGroupe(bareme, groupe, type))
        .slice()
        .sort((a, b) => (Number(a.ordre) || 0) - (Number(b.ordre) || 0))
        .map((bareme) => {
            const code = String(bareme.code);
            const value = numeric && /^\d+$/.test(code) ? Number(code) : code;
            const label = withPrice
                ? `${code} (${Number(bareme.prix).toLocaleString("fr-FR")} FCFA/m²)`
                : code;

            return { value, label };
        });
}
