import os
import json
from dotenv import load_dotenv

load_dotenv()

# API Keys from environment
LLAMA_CLOUD_API_KEY = os.getenv("LLAMA_CLOUD_API_KEY")
GROQ_API_KEY = os.getenv("GROQ_API_KEY")

# Data Schema matching the 7 fields for LlamaExtract
DATA_SCHEMA = {
    "type": "object",
    "properties": {
        "senderService": { "type": "string", "description": "L'expéditeur mentionné sur la lettre (Service ou Direction)" },
        "receiverService": { "type": "string", "description": "Le destinataire mentionné sur la lettre (Service ou Direction)" },
        "date": { "type": "string", "description": "La date du courrier" },
        "letterNumber": { "type": "string", "description": "Le numéro de référence ou numéro d'ordre du courrier" },
        "subject": { "type": "string", "description": "L'objet du courrier" },
        "importance": { 
            "type": "string", 
            "enum": ["Normal", "Urgent", "Très Urgent"]
        },
        "body": { "type": "string", "description": "Le texte principal ou corps de la lettre" }
    },
    "required": ["letterNumber", "subject", "body"],
    "additionalProperties": False
}

EXTRACTION_CONFIG = {
        "priority": None,
        "extraction_target": "PER_DOC",
        "extraction_mode": "PREMIUM",
        "parse_model": None,
        "extract_model": None,
        "multimodal_fast_mode": False,
        "system_prompt": "Extraire les informations du courrier administratif avec précision. Ne pas filtrer ou rejeter les données si le destinataire (receiverService) ou l'expéditeur ne semble pas correspondre à une liste connue. Extraire ce qui est écrit littéralement sur le document.",
        "use_reasoning": False,
        "cite_sources": False,
        "citation_bbox": False,
        "confidence_scores": False,
        "chunk_mode": "PAGE",
        "high_resolution_mode": False,
        "invalidate_cache": False,
        "num_pages_context": None,
        "page_range": None
}
