import logging
import json
from flask import Flask, request, jsonify
from functools import wraps
from config import Config
from fhir_client import FHIRClient
from cdis_connector import CDISConnector
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
cdis_connector = CDISConnector(fhir_client)

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


def ensure_cdis_tables(connection):
    """Create CDIS persistence tables if they do not exist."""
    cursor = connection.cursor()
    cursor.execute(
        """
        CREATE TABLE IF NOT EXISTS cdis_jobs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            job_type VARCHAR(20) NOT NULL,
            status VARCHAR(20) NOT NULL,
            requested_by VARCHAR(255),
            payload LONGTEXT,
            result LONGTEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_cdis_jobs_type (job_type),
            INDEX idx_cdis_jobs_status (status),
            INDEX idx_cdis_jobs_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        """
    )
    cursor.execute(
        """
        CREATE TABLE IF NOT EXISTS cdis_adjudications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            job_id INT,
            patient_id VARCHAR(255) NOT NULL,
            redcap_field VARCHAR(255) NOT NULL,
            resource_type VARCHAR(50),
            resource_id VARCHAR(255),
            proposed_value LONGTEXT,
            selected_value LONGTEXT,
            status VARCHAR(20) NOT NULL DEFAULT 'pending',
            adjudicated_by VARCHAR(255),
            adjudicated_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_cdis_adj_job (job_id),
            INDEX idx_cdis_adj_patient (patient_id),
            INDEX idx_cdis_adj_status (status),
            CONSTRAINT fk_cdis_adj_job FOREIGN KEY (job_id) REFERENCES cdis_jobs(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        """
    )
    cursor.close()


def create_cdis_job(job_type, payload):
    connection = get_db_connection()
    if not connection:
        return None

    cursor = None
    try:
        ensure_cdis_tables(connection)
        cursor = connection.cursor()
        requested_by = request.headers.get('X-CDIS-User', request.remote_addr)
        cursor.execute(
            "INSERT INTO cdis_jobs (job_type, status, requested_by, payload) VALUES (%s, %s, %s, %s)",
            (job_type, 'running', requested_by, json.dumps(payload))
        )
        connection.commit()
        return cursor.lastrowid
    except Error as e:
        logger.error(f"Unable to create CDIS job: {str(e)}")
        return None
    finally:
        if cursor:
            cursor.close()
        connection.close()


def update_cdis_job(job_id, status, result):
    if not job_id:
        return

    connection = get_db_connection()
    if not connection:
        return

    cursor = None
    try:
        ensure_cdis_tables(connection)
        cursor = connection.cursor()
        cursor.execute(
            "UPDATE cdis_jobs SET status = %s, result = %s WHERE id = %s",
            (status, json.dumps(result), job_id)
        )
        connection.commit()
    except Error as e:
        logger.error(f"Unable to update CDIS job {job_id}: {str(e)}")
    finally:
        if cursor:
            cursor.close()
        connection.close()


def persist_adjudication_candidates(job_id, patient_id, pull_result):
    connection = get_db_connection()
    if not connection:
        return

    cursor = None
    try:
        ensure_cdis_tables(connection)
        cursor = connection.cursor()
        for item in pull_result.get('results', []):
            values = item.get('values', [])
            resource_ids = item.get('resource_ids', [])
            proposed_value = values[0] if values else None
            resource_id = resource_ids[0] if resource_ids else None

            cursor.execute(
                """
                INSERT INTO cdis_adjudications (
                    job_id, patient_id, redcap_field, resource_type, resource_id, proposed_value, status
                ) VALUES (%s, %s, %s, %s, %s, %s, %s)
                """,
                (
                    job_id,
                    patient_id,
                    item.get('redcap_field', ''),
                    item.get('resource_type'),
                    resource_id,
                    None if proposed_value is None else json.dumps(proposed_value),
                    'pending',
                )
            )

        connection.commit()
    except Error as e:
        logger.error(f"Unable to persist adjudication candidates: {str(e)}")
    finally:
        if cursor:
            cursor.close()
        connection.close()


def fetch_adjudications(status=None, patient_id=None, job_id=None):
    connection = get_db_connection()
    if not connection:
        return []

    cursor = None
    try:
        ensure_cdis_tables(connection)
        cursor = connection.cursor(dictionary=True)
        sql = "SELECT * FROM cdis_adjudications WHERE 1=1"
        params = []

        if status:
            sql += " AND status = %s"
            params.append(status)
        if patient_id:
            sql += " AND patient_id = %s"
            params.append(patient_id)
        if job_id:
            sql += " AND job_id = %s"
            params.append(job_id)

        sql += " ORDER BY created_at DESC"
        cursor.execute(sql, tuple(params))
        return cursor.fetchall()
    except Error as e:
        logger.error(f"Unable to fetch adjudications: {str(e)}")
        return []
    finally:
        if cursor:
            cursor.close()
        connection.close()


def update_adjudication_status(adjudication_id, status, selected_value=None):
    connection = get_db_connection()
    if not connection:
        return False

    cursor = None
    try:
        ensure_cdis_tables(connection)
        cursor = connection.cursor()
        adjudicated_by = request.headers.get('X-CDIS-User', request.remote_addr)

        cursor.execute(
            """
            UPDATE cdis_adjudications
            SET status = %s,
                selected_value = %s,
                adjudicated_by = %s,
                adjudicated_at = CURRENT_TIMESTAMP
            WHERE id = %s
            """,
            (
                status,
                None if selected_value is None else json.dumps(selected_value),
                adjudicated_by,
                adjudication_id,
            )
        )
        connection.commit()
        return cursor.rowcount > 0
    except Error as e:
        logger.error(f"Unable to update adjudication {adjudication_id}: {str(e)}")
        return False
    finally:
        if cursor:
            cursor.close()
        connection.close()

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


# CDIS endpoints
@app.route('/cdis/fields', methods=['GET'])
@handle_errors
def cdis_fields():
    """Search available EHR/FHIR field catalog entries for mapping."""
    query = request.args.get('query', '')
    fields = cdis_connector.search_field_catalog(query)
    return jsonify({
        'query': query,
        'count': len(fields),
        'fields': fields,
    }), 200


@app.route('/cdis/mapping-helper', methods=['GET'])
@handle_errors
def cdis_mapping_helper():
    """Return field mapping suggestions and sample values for a patient."""
    patient_id = request.args.get('patient_id', '').strip()
    if not patient_id:
        return jsonify({'error': 'patient_id is required'}), 400

    resource_type = request.args.get('resource_type', '').strip() or None
    field_query = request.args.get('field_query', '').strip()
    limit = request.args.get('limit', 25, type=int)

    result = cdis_connector.mapping_helper(
        patient_id=patient_id,
        resource_type=resource_type,
        field_query=field_query,
        limit=limit,
    )
    return jsonify(result), 200


@app.route('/cdis/cdp/pull', methods=['POST'])
@handle_errors
def cdis_clinical_data_pull():
    """CDP workflow: pull mapped values for one patient and return adjudication candidates."""
    payload = request.get_json() or {}

    patient_id = str(payload.get('patient_id', '')).strip()
    mappings = payload.get('mappings', [])
    start_date = payload.get('start_date')
    end_date = payload.get('end_date')

    if not patient_id:
        return jsonify({'error': 'patient_id is required'}), 400
    if not isinstance(mappings, list) or not mappings:
        return jsonify({'error': 'mappings must be a non-empty list'}), 400

    job_id = create_cdis_job('CDP', payload)

    result = cdis_connector.clinical_data_pull(
        patient_id=patient_id,
        mappings=mappings,
        start_date=start_date,
        end_date=end_date,
    )
    result['job_id'] = job_id

    persist_adjudication_candidates(job_id, patient_id, result)
    update_cdis_job(job_id, 'completed', result)
    return jsonify(result), 200


@app.route('/cdis/cdm/extract', methods=['POST'])
@handle_errors
def cdis_clinical_data_mart():
    """CDM workflow: extract longitudinal resources for a patient cohort."""
    payload = request.get_json() or {}

    patient_ids = payload.get('patient_ids', [])
    resource_types = payload.get('resource_types', Config.SUPPORTED_RESOURCES)
    start_date = payload.get('start_date')
    end_date = payload.get('end_date')

    if not isinstance(patient_ids, list) or not patient_ids:
        return jsonify({'error': 'patient_ids must be a non-empty list'}), 400

    if not isinstance(resource_types, list) or not resource_types:
        return jsonify({'error': 'resource_types must be a non-empty list'}), 400

    job_id = create_cdis_job('CDM', payload)

    result = cdis_connector.clinical_data_mart(
        patient_ids=[str(pid).strip() for pid in patient_ids if str(pid).strip()],
        resource_types=[rt for rt in resource_types if rt in Config.SUPPORTED_RESOURCES],
        start_date=start_date,
        end_date=end_date,
    )
    result['job_id'] = job_id

    update_cdis_job(job_id, 'completed', result)
    return jsonify(result), 200


@app.route('/cdis/adjudications', methods=['GET'])
@handle_errors
def cdis_list_adjudications():
    """List CDIS adjudication items with optional filters."""
    status = request.args.get('status', '').strip() or None
    patient_id = request.args.get('patient_id', '').strip() or None
    job_id = request.args.get('job_id', type=int)

    items = fetch_adjudications(status=status, patient_id=patient_id, job_id=job_id)
    return jsonify({
        'count': len(items),
        'items': items,
    }), 200


@app.route('/cdis/adjudications/<int:adjudication_id>/accept', methods=['POST'])
@handle_errors
def cdis_accept_adjudication(adjudication_id):
    """Accept an adjudication item and optionally override selected value."""
    payload = request.get_json(silent=True) or {}
    selected_value = payload.get('selected_value')

    updated = update_adjudication_status(
        adjudication_id=adjudication_id,
        status='accepted',
        selected_value=selected_value,
    )

    if not updated:
        return jsonify({'error': 'Adjudication not found'}), 404

    return jsonify({'message': 'Adjudication accepted', 'id': adjudication_id}), 200


@app.route('/cdis/adjudications/<int:adjudication_id>/reject', methods=['POST'])
@handle_errors
def cdis_reject_adjudication(adjudication_id):
    """Reject an adjudication item."""
    updated = update_adjudication_status(
        adjudication_id=adjudication_id,
        status='rejected',
        selected_value=None,
    )

    if not updated:
        return jsonify({'error': 'Adjudication not found'}), 404

    return jsonify({'message': 'Adjudication rejected', 'id': adjudication_id}), 200

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
