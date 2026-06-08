import logging
from datetime import datetime
from typing import Any, Dict, List, Optional

from config import Config
from fhir_client import FHIRClient

logger = logging.getLogger(__name__)


class CDISConnector:
    """Lightweight CDIS-style connector with CDP and CDM workflows over FHIR."""

    FIELD_CATALOG = [
        {
            "resource_type": "Patient",
            "field_key": "patient.family",
            "label": "Patient Family Name",
            "fhir_path": "name.0.family",
        },
        {
            "resource_type": "Patient",
            "field_key": "patient.given",
            "label": "Patient Given Name",
            "fhir_path": "name.0.given.0",
        },
        {
            "resource_type": "Patient",
            "field_key": "patient.gender",
            "label": "Patient Gender",
            "fhir_path": "gender",
        },
        {
            "resource_type": "Patient",
            "field_key": "patient.birthDate",
            "label": "Patient Birth Date",
            "fhir_path": "birthDate",
        },
        {
            "resource_type": "Observation",
            "field_key": "observation.value",
            "label": "Observation Value",
            "fhir_path": "valueString",
        },
        {
            "resource_type": "Observation",
            "field_key": "observation.code",
            "label": "Observation Code Text",
            "fhir_path": "code.text",
        },
        {
            "resource_type": "Condition",
            "field_key": "condition.code",
            "label": "Condition Text",
            "fhir_path": "code.text",
        },
        {
            "resource_type": "MedicationRequest",
            "field_key": "medicationrequest.medication",
            "label": "Medication Request",
            "fhir_path": "medicationCodeableConcept.text",
        },
        {
            "resource_type": "Procedure",
            "field_key": "procedure.code",
            "label": "Procedure Text",
            "fhir_path": "code.text",
        },
        {
            "resource_type": "AllergyIntolerance",
            "field_key": "allergy.code",
            "label": "Allergy Substance",
            "fhir_path": "code.text",
        },
        {
            "resource_type": "DiagnosticReport",
            "field_key": "diagnosticreport.conclusion",
            "label": "Diagnostic Conclusion",
            "fhir_path": "conclusion",
        },
        {
            "resource_type": "Immunization",
            "field_key": "immunization.vaccine",
            "label": "Immunization Vaccine",
            "fhir_path": "vaccineCode.text",
        },
    ]

    def __init__(self, fhir_client: Optional[FHIRClient] = None):
        self.fhir_client = fhir_client or FHIRClient()

    def search_field_catalog(self, query: str = "") -> List[Dict[str, str]]:
        query = (query or "").strip().lower()
        if not query:
            return self.FIELD_CATALOG

        return [
            field
            for field in self.FIELD_CATALOG
            if query in field["field_key"].lower()
            or query in field["label"].lower()
            or query in field["resource_type"].lower()
        ]

    def mapping_helper(
        self,
        patient_id: str,
        resource_type: Optional[str] = None,
        field_query: str = "",
        limit: int = 25,
    ) -> Dict[str, Any]:
        """Return field catalog suggestions and sample values for a patient."""
        fields = self.search_field_catalog(field_query)

        if resource_type:
            fields = [f for f in fields if f["resource_type"] == resource_type]

        samples = []
        for field in fields[:limit]:
            sample_resource = self._fetch_sample_resource(patient_id, field["resource_type"])
            sample_value = self._extract_path(sample_resource or {}, field["fhir_path"])
            samples.append(
                {
                    "field": field,
                    "sample_value": sample_value,
                    "sample_resource_id": (sample_resource or {}).get("id"),
                }
            )

        return {
            "patient_id": patient_id,
            "resource_type": resource_type,
            "field_query": field_query,
            "matches": samples,
            "count": len(samples),
        }

    def clinical_data_pull(
        self,
        patient_id: str,
        mappings: List[Dict[str, Any]],
        start_date: Optional[str] = None,
        end_date: Optional[str] = None,
    ) -> Dict[str, Any]:
        """
        Pull mapped EHR values for one participant.

        mappings item shape:
        {
          "redcap_field": "bp_systolic",
          "resource_type": "Observation",
          "fhir_path": "valueString",
          "strategy": "latest"  # earliest|latest|all|first
        }
        """
        resolved = []

        for mapping in mappings:
            resource_type = mapping.get("resource_type")
            if not resource_type:
                continue

            if resource_type == "Patient":
                resources = [self.fhir_client.get_resource("Patient", patient_id)]
            else:
                resources = self._search_patient_resources(
                    patient_id,
                    resource_type,
                    start_date=start_date,
                    end_date=end_date,
                )

            resources = [r for r in resources if r]
            chosen = self._pick_resources(resources, mapping.get("strategy", "latest"))
            values = [self._extract_path(r, mapping.get("fhir_path", "id")) for r in chosen]

            resolved.append(
                {
                    "redcap_field": mapping.get("redcap_field"),
                    "resource_type": resource_type,
                    "fhir_path": mapping.get("fhir_path", "id"),
                    "strategy": mapping.get("strategy", "latest"),
                    "values": values,
                    "resource_ids": [r.get("id") for r in chosen],
                }
            )

        return {
            "patient_id": patient_id,
            "window": {"start_date": start_date, "end_date": end_date},
            "results": resolved,
            "count": len(resolved),
        }

    def clinical_data_mart(
        self,
        patient_ids: List[str],
        resource_types: List[str],
        start_date: Optional[str] = None,
        end_date: Optional[str] = None,
    ) -> Dict[str, Any]:
        """Extract a longitudinal data mart for a cohort and resource set."""
        rows = []

        for patient_id in patient_ids:
            for resource_type in resource_types:
                if resource_type == "Patient":
                    patient = self.fhir_client.get_resource("Patient", patient_id)
                    if patient:
                        rows.append(self._row_from_resource(patient_id, patient))
                    continue

                resources = self._search_patient_resources(
                    patient_id,
                    resource_type,
                    start_date=start_date,
                    end_date=end_date,
                )
                rows.extend(self._row_from_resource(patient_id, r) for r in resources)

        return {
            "cohort_size": len(patient_ids),
            "resource_types": resource_types,
            "window": {"start_date": start_date, "end_date": end_date},
            "row_count": len(rows),
            "rows": rows,
        }

    def _fetch_sample_resource(self, patient_id: str, resource_type: str) -> Optional[Dict[str, Any]]:
        if resource_type == "Patient":
            return self.fhir_client.get_resource("Patient", patient_id)

        resources = self._search_patient_resources(patient_id, resource_type)
        if not resources:
            return None

        picked = self._pick_resources(resources, "latest")
        return picked[0] if picked else None

    def _search_patient_resources(
        self,
        patient_id: str,
        resource_type: str,
        start_date: Optional[str] = None,
        end_date: Optional[str] = None,
    ) -> List[Dict[str, Any]]:
        subject_ref = f"Patient/{patient_id}"
        params = {"subject": subject_ref}

        # Some resources use "patient" rather than "subject" as search parameter.
        bundle = self.fhir_client.search_resources(resource_type, params)
        entries = (bundle or {}).get("entry", [])

        if not entries:
            bundle = self.fhir_client.search_resources(resource_type, {"patient": subject_ref})
            entries = (bundle or {}).get("entry", [])

        resources = [e.get("resource") for e in entries if isinstance(e.get("resource"), dict)]

        if start_date or end_date:
            resources = [
                r
                for r in resources
                if self._resource_in_window(r, start_date=start_date, end_date=end_date)
            ]

        return resources

    def _resource_in_window(
        self,
        resource: Dict[str, Any],
        start_date: Optional[str],
        end_date: Optional[str],
    ) -> bool:
        event = self._resource_event_datetime(resource)
        if not event:
            return True

        event_dt = self._safe_datetime(event)
        if not event_dt:
            return True

        start_dt = self._safe_datetime(start_date) if start_date else None
        end_dt = self._safe_datetime(end_date) if end_date else None

        if start_dt and event_dt < start_dt:
            return False
        if end_dt and event_dt > end_dt:
            return False

        return True

    def _pick_resources(self, resources: List[Dict[str, Any]], strategy: str) -> List[Dict[str, Any]]:
        if not resources:
            return []

        keyed = sorted(resources, key=lambda r: self._resource_event_datetime(r) or "")

        if strategy == "earliest":
            return keyed[:1]
        if strategy == "first":
            return resources[:1]
        if strategy == "all":
            return keyed

        # default and "latest"
        return keyed[-1:]

    def _resource_event_datetime(self, resource: Dict[str, Any]) -> Optional[str]:
        candidates = [
            "effectiveDateTime",
            "issued",
            "authoredOn",
            "onsetDateTime",
            "performedDateTime",
            "occurrenceDateTime",
            "meta.lastUpdated",
            "period.start",
        ]

        for path in candidates:
            value = self._extract_path(resource, path)
            if value:
                return str(value)
        return None

    def _safe_datetime(self, value: Optional[str]) -> Optional[datetime]:
        if not value:
            return None
        normalized = value.strip().replace("Z", "+00:00")
        if len(normalized) == 10:
            normalized += "T00:00:00"
        try:
            return datetime.fromisoformat(normalized)
        except ValueError:
            logger.debug("Unable to parse datetime value: %s", value)
            return None

    def _row_from_resource(self, patient_id: str, resource: Dict[str, Any]) -> Dict[str, Any]:
        return {
            "patient_id": patient_id,
            "resource_type": resource.get("resourceType"),
            "resource_id": resource.get("id"),
            "event_datetime": self._resource_event_datetime(resource),
            "summary": self._resource_summary(resource),
            "resource": resource,
        }

    def _resource_summary(self, resource: Dict[str, Any]) -> str:
        resource_type = resource.get("resourceType")
        if resource_type == "Patient":
            family = self._extract_path(resource, "name.0.family") or ""
            given = self._extract_path(resource, "name.0.given.0") or ""
            summary = f"{given} {family}".strip()
            return summary or (resource.get("id") or "Patient")

        for path in ["code.text", "medicationCodeableConcept.text", "conclusion", "id"]:
            value = self._extract_path(resource, path)
            if value:
                return str(value)

        return resource_type or "Resource"

    def _extract_path(self, data: Dict[str, Any], path: str) -> Any:
        current: Any = data
        for part in path.split("."):
            if isinstance(current, list):
                try:
                    index = int(part)
                    current = current[index]
                except (ValueError, IndexError):
                    return None
            elif isinstance(current, dict):
                if part not in current:
                    return None
                current = current[part]
            else:
                return None
        return current
