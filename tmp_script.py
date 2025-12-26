from pathlib import Path
import re
path = Path('templates/courrier/show.html.twig')
text = path.read_text(encoding='utf-8')
pattern = r'onsubmit="return confirm\(\'Confirmer l\\\'archivage d[^\"]*\);"'
new = 'onsubmit="return confirm(\'Archiver ce courrier ?\');"'
new_text, count = re.subn(pattern, new, text, count=1)
if count:
    path.write_text(new_text, encoding='utf-8')
print('replaced', count)
