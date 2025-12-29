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
        "senderService": { "type": "string", "description": "Le nom du service qui envoie la lettre" },
        "receiverService": { "type": "string", "description": "Le nom du service à qui la lettre est adressée" },
        "date": { "type": "string", "description": "La date de la lettre" },
        "letterNumber": { "type": "string", "description": "Le numéro de référence de la lettre" },
        "subject": { "type": "string", "description": "L'objet de la lettre" },
        "importance": { 
            "type": "string", 
            "enum": ["Normal", "Urgent", "Très Urgent"]
        },
        "body": { "type": "string", "description": "Le corps(les paragraphes) de la lettre" }
    },
    "required": ["senderService", "receiverService", "date", "letterNumber", "subject", "importance", "body"],
    "additionalProperties": False
}

EXTRACTION_CONFIG = {
        "priority": None,
        "extraction_target": "PER_DOC",
        "extraction_mode": "PREMIUM",
        "parse_model": None,
        "extract_model": None,
        "multimodal_fast_mode": False,
        "system_prompt": None,
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
