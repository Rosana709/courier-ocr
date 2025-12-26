# -*- coding: utf-8 -*-
from pathlib import Path
p=Path('templates/courrier/show.html.twig')
text=p.read_text(encoding='utf-8')
needle="""                <div class=\"card-body\">\n                    <form method=\"post\" action=\"{{ path('courrier_archiver', {id: courrier.id}) }}\" onsubmit=\"return confirm('Archiver ce courrier ?');\">\n                        <button type=\"submit\" class=\"btn btn-secondary btn-sm w-100\">\n                            <i class=\"bi bi-archive\"></i> Archiver\n                        </button>\n                    </form>\n                    <p class=\"small text-muted mb-0 mt-2\">La suppression est d?sactiv?e. Utilisez l'archivage.</p>\n                </div>\n"""
replace="""                <div class=\"card-body\">\n                    <button type=\"button\" class=\"btn btn-secondary btn-sm w-100\" data-bs-toggle=\"modal\" data-bs-target=\"#archiveModal\">\n                        <i class=\"bi bi-archive\"></i> Archiver\n                    </button>\n                    <p class=\"small text-muted mb-0 mt-2\">La suppression est d?sactiv?e. Utilisez l'archivage.</p>\n                </div>\n"""
if needle not in text:
    raise SystemExit('needle missing')
text = text.replace(needle, replace, 1)
modal = """\n<!-- Modal confirmation archivage -->\n<div class=\"modal fade\" id=\"archiveModal\" tabindex=\"-1\" aria-labelledby=\"archiveModalLabel\" aria-hidden=\"true\">\n    <div class=\"modal-dialog\">\n        <div class=\"modal-content\">\n            <div class=\"modal-header\">\n                <h5 class=\"modal-title\" id=\"archiveModalLabel\"><i class=\"bi bi-archive\"></i> Confirmation</h5>\n                <button type=\"button\" class=\"btn-close\" data-bs-dismiss=\"modal\" aria-label=\"Close\"></button>\n            </div>\n            <div class=\"modal-body\">\n                Archiver ce courrier ? Il sera conservé en lecture seule dans la liste des archives.\n            </div>\n            <div class=\"modal-footer\">\n                <button type=\"button\" class=\"btn btn-outline-secondary\" data-bs-dismiss=\"modal\">Annuler</button>\n                <form method=\"post\" action=\"{{ path('courrier_archiver', {id: courrier.id}) }}\">\n                    <button type=\"submit\" class=\"btn btn-secondary\">\n                        <i class=\"bi bi-archive\"></i> Confirmer l'archivage\n                    </button>\n                </form>\n            </div>\n        </div>\n    </div>\n</div>\n"""
if 'id="archiveModal"' not in text:
    text = text.replace("<!-- Modal d'upload de pi", modal + "\n<!-- Modal d'upload de pi", 1)
p.write_text(text, encoding='utf-8')
print('updated')
