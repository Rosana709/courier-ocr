from pathlib import Path
text = Path('templates/courrier/show.html.twig').read_text(encoding='utf-8')
idx = text.index("courrier_archiver")
chunk = text[idx-100:idx+200]
print(repr(chunk))
