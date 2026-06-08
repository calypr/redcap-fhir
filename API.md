# API Usage Guide

Base URL (local): `http://localhost:5000`

## Authentication

Authentication is **not currently enabled** for the API endpoints in `src/app.py`.
Run only in trusted/internal environments until an auth layer is added.

## Rate limiting

Rate limiting is **not currently implemented**.
Use an API gateway/reverse proxy if request throttling is required.

## Content type

Use JSON payloads:

```http
Content-Type: application/json
```

## Supported resource types

`GET /fhir/supported-resources`

### Example request

```bash
curl http://localhost:5000/fhir/supported-resources
```

### Example response

```json
{
  "supported_resources": ["Patient", "Observation", "Condition"],
  "count": 10
}
```

## Health endpoint

`GET /health`

### Example request

```bash
curl http://localhost:5000/health
```

### Example response

```json
{
  "status": "healthy",
  "fhir_base_url": "https://google-fhir.fhir-aggregator.org/",
  "schema_base_url": "https://hl7.org/fhir/R5"
}
```

## CRUD endpoint reference

### Create resource

`POST /fhir/resources`

### Example request

```bash
curl -X POST http://localhost:5000/fhir/resources \
  -H "Content-Type: application/json" \
  -d '{
    "resourceType": "Patient",
    "name": [{"family": "Doe", "given": ["Jane"]}],
    "gender": "female"
  }'
```

### Example success response (201)

```json
{
  "resourceType": "Patient",
  "id": "example-id",
  "name": [{"family": "Doe", "given": ["Jane"]}],
  "gender": "female"
}
```

### Read resource

`GET /fhir/resources/{resourceType}/{id}`

### Example request

```bash
curl http://localhost:5000/fhir/resources/Patient/example-id
```

### Example success response (200)

```json
{
  "resourceType": "Patient",
  "id": "example-id"
}
```

### Search resources

`GET /fhir/resources/{resourceType}`

### Example request

```bash
curl "http://localhost:5000/fhir/resources/Patient?family=Doe&given=Jane"
```

### Example success response (200)

```json
{
  "resourceType": "Bundle",
  "type": "searchset",
  "entry": []
}
```

### Update resource

`PUT /fhir/resources/{resourceType}/{id}`

### Example request

```bash
curl -X PUT http://localhost:5000/fhir/resources/Patient/example-id \
  -H "Content-Type: application/json" \
  -d '{
    "name": [{"family": "Doe", "given": ["Janet"]}],
    "gender": "female"
  }'
```

### Example success response (200)

```json
{
  "resourceType": "Patient",
  "id": "example-id",
  "name": [{"family": "Doe", "given": ["Janet"]}],
  "gender": "female"
}
```

### Delete resource

`DELETE /fhir/resources/{resourceType}/{id}`

### Example request

```bash
curl -X DELETE http://localhost:5000/fhir/resources/Patient/example-id
```

### Example success response (204)

No response body.

## Error handling

The API returns JSON errors.

### Validation error (400)

```json
{
  "error": "resourceType is required"
}
```

### Unsupported resource type (400)

```json
{
  "error": "Resource type ExampleType not supported",
  "supported": ["Patient", "Observation"]
}
```

### Not found (404)

```json
{
  "error": "Patient/example-id not found"
}
```

### Method not allowed (405)

```json
{
  "error": "Method not allowed"
}
```

### Internal server error (500)

```json
{
  "error": "Internal server error"
}
```

## CDIS connector endpoints

These endpoints implement CDIS-style workflows for field discovery, mapping assistance, clinical data pull (CDP), and clinical data mart extraction (CDM).

### Field catalog

`GET /cdis/fields?query={text}`

Example:

```bash
curl "http://localhost:5000/cdis/fields?query=glucose"
```

### Mapping helper

`GET /cdis/mapping-helper?patient_id={id}&resource_type={type}&field_query={text}&limit={n}`

Example:

```bash
curl "http://localhost:5000/cdis/mapping-helper?patient_id=123&resource_type=Observation&field_query=value"
```

### Clinical Data Pull (CDP)

`POST /cdis/cdp/pull`

Example:

```bash
curl -X POST http://localhost:5000/cdis/cdp/pull \
  -H "Content-Type: application/json" \
  -d '{
    "patient_id": "123",
    "start_date": "2025-01-01",
    "end_date": "2025-12-31",
    "mappings": [
      {
        "redcap_field": "patient_last_name",
        "resource_type": "Patient",
        "fhir_path": "name.0.family",
        "strategy": "latest"
      },
      {
        "redcap_field": "latest_observation",
        "resource_type": "Observation",
        "fhir_path": "valueString",
        "strategy": "latest"
      }
    ]
  }'
```

### Clinical Data Mart (CDM)

`POST /cdis/cdm/extract`

Example:

```bash
curl -X POST http://localhost:5000/cdis/cdm/extract \
  -H "Content-Type: application/json" \
  -d '{
    "patient_ids": ["123", "456"],
    "resource_types": ["Patient", "Observation", "Condition"],
    "start_date": "2025-01-01",
    "end_date": "2025-12-31"
  }'
```

## CDIS persistence and adjudication

CDP and CDM requests now create a persisted job record and return `job_id` in responses.

### List adjudication items

`GET /cdis/adjudications?status={pending|accepted|rejected}&patient_id={id}&job_id={id}`

Example:

```bash
curl "http://localhost:5000/cdis/adjudications?status=pending"
```

### Accept adjudication

`POST /cdis/adjudications/{id}/accept`

Example:

```bash
curl -X POST http://localhost:5000/cdis/adjudications/1/accept \
  -H "Content-Type: application/json" \
  -d '{"selected_value": "override-value"}'
```

### Reject adjudication

`POST /cdis/adjudications/{id}/reject`

Example:

```bash
curl -X POST http://localhost:5000/cdis/adjudications/1/reject
```
