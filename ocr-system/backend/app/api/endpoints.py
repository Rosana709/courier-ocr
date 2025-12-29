import os
import shutil
from datetime import datetime
from fastapi import APIRouter, UploadFile, File, HTTPException
from fastapi.responses import FileResponse
from app.models.schemas import MailContent, GenerationRequest
from app.services.ocr import extract_document
from app.services.ai_gen import generate_mail_content
from app.utils.pdf import PDFGenerator

router = APIRouter(prefix="/api")

@router.post("/extract")
async def extract_letters(file: UploadFile = File(...)):
    temp_dir = "temp"
    os.makedirs(temp_dir, exist_ok=True)
    temp_path = os.path.join(temp_dir, file.filename)
    
    with open(temp_path, "wb") as buffer:
        shutil.copyfileobj(file.file, buffer)
    
    try:
        data = extract_document(temp_path)
        os.remove(temp_path)
        return {"data": data}
    except Exception as e:
        if os.path.exists(temp_path):
            os.remove(temp_path)
        raise HTTPException(status_code=500, detail=str(e))

@router.post("/generate-content")
async def generate_content(req: GenerationRequest):
    try:
        result = generate_mail_content(req)
        return result
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))

@router.post("/generate-pdf")
async def generate_pdf(mail: MailContent):
    try:
        output_dir = "generated_pdfs"
        os.makedirs(output_dir, exist_ok=True)
        filename = f"courrier_{datetime.now().strftime('%Y%m%d%H%M%S')}.pdf"
        filepath = os.path.join(output_dir, filename)

        pdf = PDFGenerator(filepath)
        
        # Draw all sections
        pdf.draw_header(mail.senderService, mail.date)
        pdf.draw_recipient(mail.receiverService)
        pdf.draw_ref_and_object(mail.letterNumber, mail.subject, mail.importance)
        pdf.draw_body_and_content(mail.body)
        
        pdf.save()
        
        return FileResponse(filepath, media_type='application/pdf', filename=filename)
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))
