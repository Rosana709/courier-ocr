from llama_cloud_services import LlamaExtract
from llama_cloud import ExtractConfig
from app.core.config import DATA_SCHEMA, EXTRACTION_CONFIG, LLAMA_CLOUD_API_KEY

extractor = LlamaExtract(api_key=LLAMA_CLOUD_API_KEY)

def extract_document(file_path: str):
    config = ExtractConfig(**EXTRACTION_CONFIG)
    result = extractor.extract(DATA_SCHEMA, config, file_path)
    return result.data
