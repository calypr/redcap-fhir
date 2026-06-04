import requests
import logging
from typing import Dict, List, Any, Optional
from config import Config

logger = logging.getLogger(__name__)

class FHIRClient:
    """FHIR Client for interacting with FHIR-Aggregator"""
    
    def __init__(self):
        self.base_url = Config.FHIR_BASE_URL.rstrip('/')
        self.schema_url = Config.SCHEMA_BASE_URL
        self.headers = {
            'Content-Type': 'application/fhir+json',
            'Accept': 'application/fhir+json'
        }
    
    def get_resource(self, resource_type: str, resource_id: str) -> Optional[Dict[str, Any]]:
        """
        Retrieve a FHIR resource by type and ID
        
        Args:
            resource_type: FHIR resource type (e.g., 'Patient', 'Observation')
            resource_id: Resource ID
            
        Returns:
            FHIR resource as dictionary or None if not found
        """
        try:
            url = f"{self.base_url}/{resource_type}/{resource_id}"
            response = requests.get(url, headers=self.headers, timeout=10)
            
            if response.status_code == 200:
                logger.info(f"Successfully retrieved {resource_type}/{resource_id}")
                return response.json()
            elif response.status_code == 404:
                logger.warning(f"Resource {resource_type}/{resource_id} not found")
                return None
            else:
                logger.error(f"Error retrieving {resource_type}/{resource_id}: {response.status_code}")
                return None
                
        except requests.RequestException as e:
            logger.error(f"Request error retrieving {resource_type}/{resource_id}: {str(e)}")
            return None
    
    def search_resources(self, resource_type: str, params: Dict[str, str]) -> Optional[Dict[str, Any]]:
        """
        Search for FHIR resources
        
        Args:
            resource_type: FHIR resource type
            params: Search parameters
            
        Returns:
            Bundle response or None
        """
        try:
            url = f"{self.base_url}/{resource_type}"
            response = requests.get(url, params=params, headers=self.headers, timeout=10)
            
            if response.status_code == 200:
                logger.info(f"Successfully searched {resource_type} with params: {params}")
                return response.json()
            else:
                logger.error(f"Error searching {resource_type}: {response.status_code}")
                return None
                
        except requests.RequestException as e:
            logger.error(f"Request error searching {resource_type}: {str(e)}")
            return None
    
    def create_resource(self, resource_type: str, resource: Dict[str, Any]) -> Optional[Dict[str, Any]]:
        """
        Create a FHIR resource
        
        Args:
            resource_type: FHIR resource type
            resource: Resource data
            
        Returns:
            Created resource or None
        """
        try:
            url = f"{self.base_url}/{resource_type}"
            response = requests.post(url, json=resource, headers=self.headers, timeout=10)
            
            if response.status_code in [200, 201]:
                logger.info(f"Successfully created {resource_type}")
                return response.json()
            else:
                logger.error(f"Error creating {resource_type}: {response.status_code} - {response.text}")
                return None
                
        except requests.RequestException as e:
            logger.error(f"Request error creating {resource_type}: {str(e)}")
            return None
    
    def update_resource(self, resource_type: str, resource_id: str, resource: Dict[str, Any]) -> Optional[Dict[str, Any]]:
        """
        Update a FHIR resource
        
        Args:
            resource_type: FHIR resource type
            resource_id: Resource ID
            resource: Updated resource data
            
        Returns:
            Updated resource or None
        """
        try:
            url = f"{self.base_url}/{resource_type}/{resource_id}"
            response = requests.put(url, json=resource, headers=self.headers, timeout=10)
            
            if response.status_code in [200, 201]:
                logger.info(f"Successfully updated {resource_type}/{resource_id}")
                return response.json()
            else:
                logger.error(f"Error updating {resource_type}/{resource_id}: {response.status_code}")
                return None
                
        except requests.RequestException as e:
            logger.error(f"Request error updating {resource_type}/{resource_id}: {str(e)}")
            return None
    
    def delete_resource(self, resource_type: str, resource_id: str) -> bool:
        """
        Delete a FHIR resource
        
        Args:
            resource_type: FHIR resource type
            resource_id: Resource ID
            
        Returns:
            True if successful, False otherwise
        """
        try:
            url = f"{self.base_url}/{resource_type}/{resource_id}"
            response = requests.delete(url, headers=self.headers, timeout=10)
            
            if response.status_code in [200, 204]:
                logger.info(f"Successfully deleted {resource_type}/{resource_id}")
                return True
            else:
                logger.error(f"Error deleting {resource_type}/{resource_id}: {response.status_code}")
                return False
                
        except requests.RequestException as e:
            logger.error(f"Request error deleting {resource_type}/{resource_id}: {str(e)}")
            return False
    
    def validate_resource(self, resource_type: str, resource: Dict[str, Any]) -> bool:
        """
        Validate a FHIR resource against schema
        
        Args:
            resource_type: FHIR resource type
            resource: Resource to validate
            
        Returns:
            True if valid, False otherwise
        """
        # Basic validation: check required fields
        if not resource.get('resourceType'):
            logger.warning(f"Resource missing resourceType field")
            return False
        
        if resource.get('resourceType') != resource_type:
            logger.warning(f"Resource type mismatch: expected {resource_type}, got {resource.get('resourceType')}")
            return False
        
        logger.info(f"Resource validation passed for {resource_type}")
        return True
