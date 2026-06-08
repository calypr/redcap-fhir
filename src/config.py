import os
from dotenv import load_dotenv

load_dotenv()

class Config:
    """Base configuration"""
    DEBUG = os.getenv('API_DEBUG', 'false').lower() == 'true'
    HOST = os.getenv('API_HOST', '0.0.0.0')
    PORT = int(os.getenv('API_PORT', 5000))
    
    # FHIR Configuration
    FHIR_BASE_URL = os.getenv('FHIR_BASE_URL', 'https://google-fhir.fhir-aggregator.org/')
    SCHEMA_BASE_URL = os.getenv('SCHEMA_BASE_URL', 'https://hl7.org/fhir/R5')
    FHIR_ACCESS_TOKEN = os.getenv('FHIR_ACCESS_TOKEN', '')
    
    # Database Configuration
    MYSQL_HOST = os.getenv('MYSQL_HOST', 'localhost')
    MYSQL_USER = os.getenv('MYSQL_USER', 'redcap')
    MYSQL_PASSWORD = os.getenv('MYSQL_PASSWORD', 'redcap_pass')
    MYSQL_DATABASE = os.getenv('MYSQL_DATABASE', 'redcap')
    MYSQL_PORT = int(os.getenv('MYSQL_PORT', 3306))
    
    # Logging Configuration
    LOG_LEVEL = os.getenv('LOG_LEVEL', 'INFO')
    LOG_FILE = os.getenv('LOG_FILE', '/app/logs/fhir-api.log')
    
    # Supported FHIR Resources
    SUPPORTED_RESOURCES = [
        'Patient',
        'Observation',
        'Condition',
        'MedicationStatement',
        'Procedure',
        'AllergyIntolerance',
        'DiagnosticReport',
        'Encounter',
        'Immunization',
        'MedicationRequest',
        'ResearchStudy',
        'ResearchSubject',
        'Sample'
    ]
