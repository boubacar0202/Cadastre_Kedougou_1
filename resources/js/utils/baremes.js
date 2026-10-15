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
