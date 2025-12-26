# -*- coding: utf-8 -*-
from pathlib import Path
text = Path('templates/courrier/show.html.twig').read_text(encoding='utf-8')
old = """<form method=\"post\" action=\"{{ path('courrier_archiver', {id: courrier.id}) }}\" onsubmit=\"return confirm('Archiver ce courrier ?');\">\n                        <button type=\"submit\" class=\"btn btn-secondary btn-sm w-100\">\n                            <i class=\"bi bi-archive\"></i> Archiver\n                        </button>\n                    </form>"""
new = """<button type=\"button\" class=\"btn btn-secondary btn-sm w-100\" data-bs-toggle=\"modal\" data-bs-target=\"#archiveModal\">\n                        <i class=\"bi bi-archive\"></i> Archiver\n                    </button>"""
if old not in text:
    print('not found')
else:
    text = text.replace(old, new, 1)
    print('replaced form')
if 'id="archiveModal"' not in text:
    modal = """<!-- Modal confirmation archivage -->\n<div class=\"modal fade\" id=\"archiveModal\" tabindex=\"-1\" aria-labelledby=\"archiveModalLabel\" aria-hidden=\"true\">\n    <div class=\"modal-dialog\">\n        <div class=\"modal-content\">\n            <div class=\"modal-header\">\n                <h5 class=\"modal-title\" id=\"archiveModalLabel\"><i class=\"bi bi-archive\"></i> Confirmation</h5>\n                <button type=\"button\" class=\"btn-close\" data-bs-dismiss=\"modal\" aria-label=\"Close\"></button>\n            </div>\n            <div class=\"modal-body\">\n                Archiver ce courrier ? Il sera conservé en lecture seule dans la liste des archives.\n            </div>\n            <div class=\"modal-footer\">\n                <button type=\"button\" class=\"btn btn-outline-secondary\" data-bs-dismiss=\"modal\">Annuler</button>\n                <form method=\"post\" action=\"{{ path('courrier_archiver', {id: courrier.id}) }}\">\n                    <button type=\"submit\" class=\"btn btn-secondary\">\n                        <i class=\"bi bi-archive\"></i> Confirmer l'archivage\n                    </button>\n                </form>\n            </div>\n        </div>\n    </div>\n</div>\n"""
    text = text.replace("<!-- Modal d'upload de pi", modal + "<!-- Modal d'upload de pi", 1)
    print('modal added')
Path('templates/courrier/show.html.twig').write_text(text, encoding='utf-8')
