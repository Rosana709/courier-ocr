import os
from datetime import datetime
from reportlab.pdfgen import canvas
from reportlab.lib.pagesizes import A4
from reportlab.lib.units import mm
from reportlab.lib.utils import ImageReader

class PDFGenerator:
    def __init__(self, filename):
        self.c = canvas.Canvas(filename, pagesize=A4)
        self.width, self.height = A4
        self.assets_dir = "assets"

    def format_date_fr(self, date_str):
        try:
            # Assume date_str is YYYY-MM-DD
            dt = datetime.strptime(date_str, "%Y-%m-%d")
            months = [
                "janvier", "février", "mars", "avril", "mai", "juin",
                "juillet", "août", "septembre", "octobre", "novembre", "décembre"
            ]
            return f"{dt.day} {months[dt.month-1]} {dt.year}"
        except Exception as e:
            return date_str

    def draw_header(self, sender_service, date_str):
        # == TOP CENTER: REPUBLIC == 
        logo_rep = os.path.join(self.assets_dir, "logo_republic.png")
        if not os.path.exists(logo_rep):
            logo_rep = os.path.join(self.assets_dir, "logo_republic.jpg")
            
        if os.path.exists(logo_rep):
            self.c.drawImage(logo_rep, 92.5 * mm, 270 * mm, width=25*mm, height=20*mm, mask='auto', preserveAspectRatio=True)
        
        self.c.setFont("Times-Bold", 9)
        self.c.drawCentredString(105 * mm, 266 * mm, "REPOBLIKAN'I MADAGASIKARA")
        self.c.setFont("Times-Roman", 8)
        self.c.drawCentredString(105 * mm, 263 * mm, "Fitiavana - Tanindrazana - Fandrosoana")
        
        self.c.setLineWidth(0.5)
        self.c.line(25 * mm, 260 * mm, 185 * mm, 260 * mm)

        # == LEFT: MINISTRY ==
        logo_mef = os.path.join(self.assets_dir, "logo_mef.png")
        if os.path.exists(logo_mef):
            self.c.drawImage(logo_mef, 35 * mm, 235 * mm, width=22*mm, height=22*mm, mask='auto', preserveAspectRatio=True)
            
        self.c.setFont("Times-Roman", 9)
        text_y = 232 * mm
        line_height = 4 * mm
        
        def draw_centered_text(text, x, y, font="Times-Roman", size=9):
            self.c.setFont(font, size)
            self.c.drawCentredString(x, y, text)

        center_x_left = 46 * mm
        draw_centered_text("MINISTERE DE L'ECONOMIE ET DES FINANCES", center_x_left, text_y)
        text_y -= line_height
        draw_centered_text("SECRETARIAT GENERAL", center_x_left, text_y)
        text_y -= line_height
        draw_centered_text("DIRECTION GENERALE DES IMPOTS", center_x_left, text_y)
        text_y -= line_height
        draw_centered_text(sender_service.upper(), center_x_left, text_y, font="Times-Bold")
        text_y -= 3 * mm
        draw_centered_text("---", center_x_left, text_y)

        # == RIGHT: DATE & SENDER TITLE ==
        self.c.setFont("Times-Roman", 11)
        formatted_date = self.format_date_fr(date_str)
        self.c.drawString(120 * mm, 235 * mm, f"Antananarivo, le {formatted_date}")

        # Sender generic title (LE CHEF DU SERVICE...)
        # Approx 15mm below date
        self.c.setFont("Times-Bold", 10)
        # Often "Le Chef du Service du ..." (corresponds to sender_service)
        # We can construct it or just print "LE CHEF DU SERVICE" if uncertain, but image has specific.
        # We'll try to use sender_service to construct it.
        sender_title = "LE CHEF DU " + sender_service.upper().replace("SERVICE DU ", "").replace("SERVICE ", "")
        # Split if too long
        self.draw_wrapped_text(sender_title, 120 * mm, 225 * mm, 80 * mm, 5 * mm, font="Times-Bold", size=10)

        # "à"
        self.c.setFont("Times-Roman", 11)
        self.c.drawCentredString(160 * mm, 210 * mm, "à") # Centered roughly in the right block

    def draw_recipient(self, receiver_service):
        # == RECIPIENT ==
        # Below "à"
        # "MONSIEUR LE CHEF DU SERVICE REGIONAL..."
        self.c.setFont("Times-Bold", 10)
        self.draw_wrapped_text(f"MONSIEUR LE CHEF DU {receiver_service.upper()}", 120 * mm, 200 * mm, 80 * mm, 5 * mm)
        
    def draw_ref_and_object(self, letter_number, subject, importance):
        # == REF NUMBER ==
        # Left side, lower down. Y approx 180mm?
        self.c.setFont("Times-Roman", 11)
        
        # User requested exact letter_number without extra prefixes/suffixes added by PDF template
        full_ref = f"N° {letter_number}"
        
        self.c.drawString(25 * mm, 180 * mm, full_ref)
        
        # == OBJECT ==
        # Below Ref, Y approx 170mm
        self.c.drawString(25 * mm, 170 * mm, "Objet : " + subject)
        if importance and importance.lower() != "normal":
             self.c.setFont("Times-Bold", 11)
             self.c.drawString(25 * mm + self.c.stringWidth("Objet : " + subject, "Times-Roman", 11) + 2*mm, 170 * mm, f"({importance})")

    def draw_body_and_content(self, body_text):
        # == GREETING ==
        # Y approx 155mm
        current_y = 155 * mm
        self.c.setFont("Times-Roman", 11)
        self.c.drawString(35 * mm, current_y, "Monsieur le Chef du service,")
        
        current_y -= 10 * mm
        
        # == BODY TEXT ==
        # Justified text is hard in pure canvas without low-level calculation.
        # reportlab Canvas has `beginText`, `textLine`, etc, but justification needs `pdfgen.textobject`.
        # Easier: Use `reportlab.lib.utils.simpleSplit` and draw lines.
        # Or better: construct a paragraph style logic.
        
        # Splitting body text into lines
        # Left margin 25mm, Right margin 20mm -> Width = 210 - 25 - 20 = 165mm
        text_width = 165 * mm
        
        # Parsing body for potential tabular data detection?
        # The user image has a specific table: "NIF : ...", "Raison sociale : ..."
        # We'll check if those keywords exist in body_text and extract them or just format them if found.
        
        lines = body_text.split('\n')
        
        self.c.setFont("Times-Roman", 11)
        
        for line in lines:
            line = line.strip()
            if not line:
                current_y -= 5 * mm
                continue
            
            # Check for key-value pairs that look like the table in the image
            # Image has indented keys: "NIF :", "Raison sociale :"
            if line.upper().startswith("NIF") or line.upper().startswith("RAISON SOCIALE") or ":" in line and len(line.split(":")[0]) < 20:
                # Treat as table row
                parts = line.split(":", 1)
                key = parts[0].strip()
                val = parts[1].strip() if len(parts) > 1 else ""
                
                # Draw Key
                self.c.drawString(45 * mm, current_y, key + " :")
                # Draw Value
                self.c.drawString(90 * mm, current_y, val)
                current_y -= 6 * mm
            else:
                # Regular paragraph text
                # We add indent for new paragraph
                # simpleSplit(text, fontName, fontSize, maxWidth)
                if line.startswith("   ") or line.startswith("\t"): 
                    # already indented?
                    pass
                else:
                    # Add indent
                    line = "        " + line
                
                wrapped_lines = self.wrap_text(line, text_width, "Times-Roman", 11)
                for wline in wrapped_lines:
                    self.c.drawString(25 * mm, current_y, wline)
                    current_y -= 5 * mm
            
            # Check bottom margin
            if current_y < 20 * mm:
                self.c.showPage()
                current_y = 270 * mm

        # == CLOSING ==
        current_y -= 5 * mm
        self.c.drawString(25 * mm, current_y, "        Je vous prie d'agréer, Monsieur le Chef du service, l'expression de mes salutations")
        current_y -= 5 * mm
        self.c.drawString(25 * mm, current_y, "distinguées.")
        
    def save(self):
        self.c.save()

    # Utilities
    def wrap_text(self, text, max_width, font_name, font_size):
        from reportlab.pdfbase.pdfmetrics import stringWidth
        words = text.split(' ')
        lines = []
        current_line = []
        
        for word in words:
            current_line.append(word)
            width = stringWidth(' '.join(current_line), font_name, font_size)
            if width > max_width:
                current_line.pop()
                lines.append(' '.join(current_line))
                current_line = [word]
        if current_line:
            lines.append(' '.join(current_line))
        return lines

    def draw_wrapped_text(self, text, x, y, max_width, line_height, font="Times-Roman", size=10):
        lines = self.wrap_text(text, max_width, font, size)
        for i, line in enumerate(lines):
            self.c.drawString(x, y - (i * line_height), line)

# Standalone testing if run directly
if __name__ == "__main__":
    gen = PDFGenerator("test_output.pdf")
    gen.draw_header("SERVICE DU SYSTEME D'INFORMATION FISCALE", "06 OCT 2025")
    gen.draw_recipient("SERVICE REGIONAL DES ENTREPRISE 2 ANALAMANGA")
    gen.draw_ref_and_object("231", "Demande d'information fiscale (urgente)", "Urgent")
    body = """        Monsieur le Chef du service,
        J'ai l'honneur de vous demander de bien vouloir nous fournir dans les meilleurs délais les noms du propriétaire et du gérant, les photocopies des statuts, avis d'imposition, déclaration de chiffre d'affaire et les situations fiscales durant l'année 2022, 2023 et 2024 auprès de votre unité de la société ci-après :

        NIF : 4001939516
        Raison sociale : SIAM MADAGASCAR SARLU"""
    gen.draw_body_and_content(body)
    gen.save()
