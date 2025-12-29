from pydantic import BaseModel

class MailContent(BaseModel):
    senderService: str
    receiverService: str
    date: str
    letterNumber: str
    subject: str
    importance: str
    body: str

class GenerationRequest(BaseModel):
    senderService: str
    receiverService: str
    letterNumber: str
    importance: str
    prompt: str
