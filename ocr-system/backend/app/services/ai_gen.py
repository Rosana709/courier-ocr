import json
from groq import Groq
from app.core.config import GROQ_API_KEY, DATA_SCHEMA

groq_client = Groq(api_key=GROQ_API_KEY)

def generate_mail_content(req):
    system_prompt = f"""
    Tu es un assistant administratif expert en rédaction de courriers officiels pour l'administration malgache.
    Ta tâche est de générer un courrier au format JSON respectant strictement ce schéma:
    {json.dumps(DATA_SCHEMA)}
    
    Instructions:
    - Utilise un ton formel et professionnel.
    - Le 'senderService' est: {req.senderService}
    - Le 'receiverService' est: {req.receiverService}
    - Le 'letterNumber' est: {req.letterNumber}
    - L''importance' est: {req.importance}
    - La 'date' doit être la date du jour au format: 'Antananarivo, le DD Month YYYY' (ex: Antananarivo, le 06 OCT 2025).
    - Le 'subject' et le 'body' (plusieurs paragraphes si nécessaire) doivent être générés à partir du prompt utilisateur.
    
    Réponds UNIQUEMENT avec le JSON, sans explications.
    """
    
    completion = groq_client.chat.completions.create(
        model="llama-3.3-70b-versatile",
        messages=[
            {"role": "system", "content": system_prompt},
            {"role": "user", "content": req.prompt}
        ],
        response_format={"type": "json_object"}
    )
    
    result_json = json.loads(completion.choices[0].message.content)
    return result_json
