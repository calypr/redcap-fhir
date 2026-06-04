import logging
import json
from flask import Flask, request, jsonify
from functools import wraps
from config import Config
from fhir_client import FHIRClient
import mysql.connector
from mysql.connector import Error

# Setup logging
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(name)s - %(levelname)s - %(message)s',
    handlers=[
        logging.FileHandler(Config.LOG_FILE),
        logging.StreamHandler()
    ]
)
logger = logging.getLogger(__name__)

# Initialize Flask app
app = Flask(__name__)
app.config['JSON_SORT_KEYS'] = False

# Initialize FHIR Client
fhir_client = FHIRClient()

# Database connection pool
def get_db_connection():
    """Get a database connection"""
    try:
        connection = mysql.connector.connect(
            host=Config.MYSQL_HOST,
            port=Config.MYSQL_PORT,
            user=Config.MYSQL_USER,
            password=Config.MYSQL_PASSWORD,
            database=Config.MYSQL_DATABASE
        )
        return connection
    except Error as e:
        logger.error(f"Database connection error: {str(e)}")
        return None

# Error handler decorator
def handle_errors(f):
    @wraps(f)
    def decorated_function(*args, **kwargs):
        try:
            return f(*args, **kwargs)
        except Exception as e:
            logger.error(f"Unhandled error: {str(e)}")
            return jsonify({'error': str(e)}), 500
    return decorated_function

# Health check endpoint
@app.route('/health', methods=['GET'])
@handle_errors
def health_check():
    """Health check endpoint"""
    return jsonify({
        'status': 'healthy',
        'fhir_base_url': Config.FHIR_BASE_URL,
        'schema_base_url': Config.SCHEMA_BASE_URL
    }), 200

# FHIR CRUD Endpoints

# CREATE - POST /fhir/resources
@app.route('/fhir/resources', methods=['POST'])
@handle_errors
def create_resource():
    """
    Create a new FHIR resource
    
    Request body: FHIR resource JSON
    Expected format: {"resourceType": "Patient", ...}
    """
    try:
        resource = request.get_json()
        
        if not resource:
            return jsonify({'error': 'Request body must contain FHIR resource'}), 400
        
        resource_type = resource.get('resourceType')
        
        if not resource_type:
            return jsonify({'error': 'resourceType is required'}), 400
        
        if resource_type not in Config.SUPPORTED_RESOURCES:
            return jsonify({
                'error': f'Resource type {resource_type} not supported',
                'supported': Config.SUPPORTED_RESOURCES
            }), 400
        
        # Validate resource
        if not fhir_client.validate_resource(resource_type, resource):
            return jsonify({'error': 'Resource validation failed'}), 400
        
        # Create resource via FHIR API
        result = fhir_client.create_resource(resource_type, resource)
        
        if result:
            # Store in database
            connection = get_db_connection()
            if connection:
                try:
                    cursor = connection.cursor()
                    resource_id = result.get('id', 'unknown')
                    cursor.execute(
                        "INSERT INTO fhir_resources (resource_type, resource_id, data) VALUES (%s, %s, %s)",
                        (resource_type, resource_id, json.dumps(result))
                    )
                    connection.commit()
                    logger.info(f"Stored {resource_type}/{resource_id} in database")
                except Error as e:
                    logger.error(f"Database insert error: {str(e)}")
                finally:
                    cursor.close()
                    connection.close()
            
            return jsonify(result), 201
        else:
            return jsonify({'error': 'Failed to create resource in FHIR API'}), 500
            
    except Exception as e:
        logger.error(f"Error creating resource: {str(e)}")
        return jsonify({'error': str(e)}), 500

# READ - GET /fhir/resources/<resource_type>/<resource_id>
@app.route('/fhir/resources/<resource_type>/<resource_id>', methods=['GET'])
@handle_errors
def read_resource(resource_type, resource_id):
    """
    Retrieve a FHIR resource by type and ID
    """
    try:
        if resource_type not in Config.SUPPORTED_RESOURCES:
            return jsonify({
                'error': f'Resource type {resource_type} not supported',
                'supported': Config.SUPPORTED_RESOURCES
            }), 400
        
        # Try to get from FHIR API
        result = fhir_client.get_resource(resource_type, resource_id)
        
        if result:
            return jsonify(result), 200
        else:
            return jsonify({'error': f'{resource_type}/{resource_id} not found'}), 404
            
    except Exception as e:
        logger.error(f"Error reading resource: {str(e)}")
        return jsonify({'error': str(e)}), 500

# SEARCH - GET /fhir/resources/<resource_type>
@app.route('/fhir/resources/<resource_type>', methods=['GET'])
@handle_errors
def search_resources(resource_type):
    """
    Search for FHIR resources with query parameters
    
    Example: /fhir/resources/Patient?family=Smith&given=John
    """
    try:
        if resource_type not in Config.SUPPORTED_RESOURCES:
            return jsonify({
                'error': f'Resource type {resource_type} not supported',
                'supported': Config.SUPPORTED_RESOURCES
            }), 400
        
        # Get search parameters from query string
        search_params = request.args.to_dict()
        
        # Search resources
        result = fhir_client.search_resources(resource_type, search_params)
        
        if result:
            return jsonify(result), 200
        else:
            return jsonify({'error': 'Search failed'}), 500
            
    except Exception as e:
        logger.error(f"Error searching resources: {str(e)}")
        return jsonify({'error': str(e)}), 500

# UPDATE - PUT /fhir/resources/<resource_type>/<resource_id>
@app.route('/fhir/resources/<resource_type>/<resource_id>', methods=['PUT'])
@handle_errors
def update_resource(resource_type, resource_id):
    """
    Update an existing FHIR resource
    
    Request body: Updated FHIR resource JSON
    """
    try:
        resource = request.get_json()
        
        if not resource:
            return jsonify({'error': 'Request body must contain FHIR resource'}), 400
        
        if resource_type not in Config.SUPPORTED_RESOURCES:
            return jsonify({
                'error': f'Resource type {resource_type} not supported',
                'supported': Config.SUPPORTED_RESOURCES
            }), 400
        
        # Ensure ID matches
        resource['id'] = resource_id
        resource['resourceType'] = resource_type
        
        # Validate resource
        if not fhir_client.validate_resource(resource_type, resource):
            return jsonify({'error': 'Resource validation failed'}), 400
        
        # Update resource via FHIR API
        result = fhir_client.update_resource(resource_type, resource_id, resource)
        
        if result:
            # Update in database
            connection = get_db_connection()
            if connection:
                try:
                    cursor = connection.cursor()
                    cursor.execute(
                        "UPDATE fhir_resources SET data = %s WHERE resource_type = %s AND resource_id = %s",
                        (json.dumps(result), resource_type, resource_id)
                    )
                    connection.commit()
                    logger.info(f"Updated {resource_type}/{resource_id} in database")
                except Error as e:
                    logger.error(f"Database update error: {str(e)}")
                finally:
                    cursor.close()
                    connection.close()
            
            return jsonify(result), 200
        else:
            return jsonify({'error': 'Failed to update resource in FHIR API'}), 500
            
    except Exception as e:
        logger.error(f"Error updating resource: {str(e)}")
        return jsonify({'error': str(e)}), 500

# DELETE - DELETE /fhir/resources/<resource_type>/<resource_id>
@app.route('/fhir/resources/<resource_type>/<resource_id>', methods=['DELETE'])
@handle_errors
def delete_resource(resource_type, resource_id):
    """
    Delete a FHIR resource
    """
    try:
        if resource_type not in Config.SUPPORTED_RESOURCES:
            return jsonify({
                'error': f'Resource type {resource_type} not supported',
                'supported': Config.SUPPORTED_RESOURCES
            }), 400
        
        # Delete via FHIR API
        success = fhir_client.delete_resource(resource_type, resource_id)
        
        if success:
            # Delete from database
            connection = get_db_connection()
            if connection:
                try:
                    cursor = connection.cursor()
                    cursor.execute(
                        "DELETE FROM fhir_resources WHERE resource_type = %s AND resource_id = %s",
                        (resource_type, resource_id)
                    )
                    connection.commit()
                    logger.info(f"Deleted {resource_type}/{resource_id} from database")
                except Error as e:
                    logger.error(f"Database delete error: {str(e)}")
                finally:
                    cursor.close()
                    connection.close()
            
            return jsonify({'message': f'Successfully deleted {resource_type}/{resource_id}'}), 204
        else:
            return jsonify({'error': 'Failed to delete resource from FHIR API'}), 500
            
    except Exception as e:
        logger.error(f"Error deleting resource: {str(e)}")
        return jsonify({'error': str(e)}), 500

# Supported resources endpoint
@app.route('/fhir/supported-resources', methods=['GET'])
@handle_errors
def get_supported_resources():
    """Get list of supported FHIR resources"""
    return jsonify({
        'supported_resources': Config.SUPPORTED_RESOURCES,
        'count': len(Config.SUPPORTED_RESOURCES)
    }), 200

# Error handlers
@app.errorhandler(404)
def not_found(error):
    return jsonify({'error': 'Endpoint not found'}), 404

@app.errorhandler(405)
def method_not_allowed(error):
    return jsonify({'error': 'Method not allowed'}), 405

@app.errorhandler(500)
def internal_error(error):
    logger.error(f"Internal server error: {str(error)}")
    return jsonify({'error': 'Internal server error'}), 500

if __name__ == '__main__':
    logger.info(f"Starting FHIR API Server")
    logger.info(f"FHIR Base URL: {Config.FHIR_BASE_URL}")
    logger.info(f"Schema Base URL: {Config.SCHEMA_BASE_URL}")
    logger.info(f"Supported Resources: {', '.join(Config.SUPPORTED_RESOURCES)}")
    
    app.run(
        host=Config.HOST,
        port=Config.PORT,
        debug=Config.DEBUG
    )
