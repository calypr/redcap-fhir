<?php
declare(strict_types=1);

http_response_code(200);

$apiBaseUrl = rtrim(getenv('FHIR_API_URL') ?: 'http://fhir-api:5000', '/');

$resourceDefinitions = [
    'Patient' => [
        ['name' => 'id', 'label' => 'FHIR ID', 'type' => 'text'],
        ['name' => 'family', 'label' => 'Family Name', 'type' => 'text'],
        ['name' => 'given', 'label' => 'Given Name', 'type' => 'text'],
        ['name' => 'gender', 'label' => 'Gender', 'type' => 'select', 'options' => ['male', 'female', 'other', 'unknown']],
        ['name' => 'birthDate', 'label' => 'Birth Date', 'type' => 'date'],
    ],
    'Observation' => [
        ['name' => 'id', 'label' => 'FHIR ID', 'type' => 'text'],
        ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => ['registered', 'preliminary', 'final', 'amended']],
        ['name' => 'code', 'label' => 'Observation Label', 'type' => 'text'],
        ['name' => 'subject', 'label' => 'Subject Reference', 'type' => 'text', 'placeholder' => 'Patient/123'],
        ['name' => 'effectiveDateTime', 'label' => 'Effective Date/Time', 'type' => 'datetime-local'],
        ['name' => 'valueString', 'label' => 'Value', 'type' => 'text'],
    ],
    'Condition' => [
        ['name' => 'id', 'label' => 'FHIR ID', 'type' => 'text'],
        ['name' => 'clinicalStatus', 'label' => 'Clinical Status', 'type' => 'select', 'options' => ['active', 'recurrence', 'relapse', 'inactive', 'remission', 'resolved']],
        ['name' => 'code', 'label' => 'Condition', 'type' => 'text'],
        ['name' => 'subject', 'label' => 'Subject Reference', 'type' => 'text', 'placeholder' => 'Patient/123'],
        ['name' => 'onsetDateTime', 'label' => 'Onset Date/Time', 'type' => 'datetime-local'],
        ['name' => 'notes', 'label' => 'Notes', 'type' => 'textarea'],
    ],
    'MedicationStatement' => [
        ['name' => 'id', 'label' => 'FHIR ID', 'type' => 'text'],
        ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => ['recorded', 'active', 'completed', 'entered-in-error', 'intended', 'stopped', 'on-hold', 'unknown', 'not-taken']],
        ['name' => 'medication', 'label' => 'Medication', 'type' => 'text'],
        ['name' => 'subject', 'label' => 'Subject Reference', 'type' => 'text', 'placeholder' => 'Patient/123'],
        ['name' => 'effectiveDateTime', 'label' => 'Effective Date/Time', 'type' => 'datetime-local'],
        ['name' => 'notes', 'label' => 'Notes', 'type' => 'textarea'],
    ],
    'Procedure' => [
        ['name' => 'id', 'label' => 'FHIR ID', 'type' => 'text'],
        ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => ['preparation', 'in-progress', 'not-done', 'on-hold', 'stopped', 'completed', 'entered-in-error', 'unknown']],
        ['name' => 'code', 'label' => 'Procedure', 'type' => 'text'],
        ['name' => 'subject', 'label' => 'Subject Reference', 'type' => 'text', 'placeholder' => 'Patient/123'],
        ['name' => 'performedDateTime', 'label' => 'Performed Date/Time', 'type' => 'datetime-local'],
        ['name' => 'notes', 'label' => 'Notes', 'type' => 'textarea'],
    ],
    'AllergyIntolerance' => [
        ['name' => 'id', 'label' => 'FHIR ID', 'type' => 'text'],
        ['name' => 'clinicalStatus', 'label' => 'Clinical Status', 'type' => 'select', 'options' => ['active', 'inactive', 'resolved']],
        ['name' => 'code', 'label' => 'Substance', 'type' => 'text'],
        ['name' => 'patient', 'label' => 'Patient Reference', 'type' => 'text', 'placeholder' => 'Patient/123'],
        ['name' => 'criticality', 'label' => 'Criticality', 'type' => 'select', 'options' => ['low', 'high', 'unable-to-assess']],
        ['name' => 'notes', 'label' => 'Notes', 'type' => 'textarea'],
    ],
    'DiagnosticReport' => [
        ['name' => 'id', 'label' => 'FHIR ID', 'type' => 'text'],
        ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => ['registered', 'partial', 'preliminary', 'final', 'amended', 'corrected', 'appended', 'cancelled', 'entered-in-error', 'unknown']],
        ['name' => 'code', 'label' => 'Report Name', 'type' => 'text'],
        ['name' => 'subject', 'label' => 'Subject Reference', 'type' => 'text', 'placeholder' => 'Patient/123'],
        ['name' => 'effectiveDateTime', 'label' => 'Effective Date/Time', 'type' => 'datetime-local'],
        ['name' => 'conclusion', 'label' => 'Conclusion', 'type' => 'textarea'],
    ],
    'Encounter' => [
        ['name' => 'id', 'label' => 'FHIR ID', 'type' => 'text'],
        ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => ['planned', 'in-progress', 'on-hold', 'discharged', 'completed', 'cancelled', 'discontinued', 'entered-in-error', 'unknown']],
        ['name' => 'class', 'label' => 'Class Code', 'type' => 'text', 'placeholder' => 'AMB'],
        ['name' => 'subject', 'label' => 'Subject Reference', 'type' => 'text', 'placeholder' => 'Patient/123'],
        ['name' => 'start', 'label' => 'Start Date/Time', 'type' => 'datetime-local'],
        ['name' => 'end', 'label' => 'End Date/Time', 'type' => 'datetime-local'],
    ],
    'Immunization' => [
        ['name' => 'id', 'label' => 'FHIR ID', 'type' => 'text'],
        ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => ['completed', 'entered-in-error', 'not-done']],
        ['name' => 'vaccineCode', 'label' => 'Vaccine', 'type' => 'text'],
        ['name' => 'patient', 'label' => 'Patient Reference', 'type' => 'text', 'placeholder' => 'Patient/123'],
        ['name' => 'occurrenceDateTime', 'label' => 'Occurrence Date/Time', 'type' => 'datetime-local'],
        ['name' => 'lotNumber', 'label' => 'Lot Number', 'type' => 'text'],
    ],
    'MedicationRequest' => [
        ['name' => 'id', 'label' => 'FHIR ID', 'type' => 'text'],
        ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => ['active', 'on-hold', 'ended', 'stopped', 'completed', 'cancelled', 'entered-in-error', 'draft', 'unknown']],
        ['name' => 'intent', 'label' => 'Intent', 'type' => 'select', 'options' => ['proposal', 'plan', 'order', 'original-order', 'reflex-order', 'filler-order', 'instance-order', 'option']],
        ['name' => 'medication', 'label' => 'Medication', 'type' => 'text'],
        ['name' => 'subject', 'label' => 'Subject Reference', 'type' => 'text', 'placeholder' => 'Patient/123'],
        ['name' => 'authoredOn', 'label' => 'Authored On', 'type' => 'datetime-local'],
    ],
    'ResearchStudy' => [
        ['name' => 'id', 'label' => 'FHIR ID', 'type' => 'text'],
        ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => ['active', 'administratively-completed', 'approved', 'closed-to-accrual', 'closed-to-accrual-and-intervention', 'completed', 'disapproved', 'in-review', 'temporarily-closed-to-accrual', 'temporarily-closed-to-accrual-and-intervention', 'withdrawn']],
        ['name' => 'title', 'label' => 'Title', 'type' => 'text'],
        ['name' => 'description', 'label' => 'Description', 'type' => 'textarea'],
        ['name' => 'start', 'label' => 'Start Date', 'type' => 'date'],
        ['name' => 'end', 'label' => 'End Date', 'type' => 'date'],
    ],
    'ResearchSubject' => [
        ['name' => 'id', 'label' => 'FHIR ID', 'type' => 'text'],
        ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => ['candidate', 'eligible', 'follow-up', 'ineligible', 'not-registered', 'off-study', 'on-study', 'on-study-intervention', 'on-study-observation', 'pending-on-study', 'potential-candidate', 'screening', 'withdrawn']],
        ['name' => 'study', 'label' => 'Study Reference', 'type' => 'text', 'placeholder' => 'ResearchStudy/123'],
        ['name' => 'individual', 'label' => 'Individual Reference', 'type' => 'text', 'placeholder' => 'Patient/123'],
        ['name' => 'assignedArm', 'label' => 'Assigned Arm', 'type' => 'text'],
        ['name' => 'actualArm', 'label' => 'Actual Arm', 'type' => 'text'],
    ],
    'Sample' => [
        ['name' => 'id', 'label' => 'FHIR ID', 'type' => 'text'],
        ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => ['available', 'unavailable', 'unsatisfactory', 'entered-in-error']],
        ['name' => 'subject', 'label' => 'Subject Reference', 'type' => 'text', 'placeholder' => 'Patient/123'],
        ['name' => 'type', 'label' => 'Sample Type', 'type' => 'text'],
        ['name' => 'collectedDateTime', 'label' => 'Collected Date/Time', 'type' => 'datetime-local'],
        ['name' => 'quantityValue', 'label' => 'Quantity', 'type' => 'text'],
    ],
];

function apiRequest(string $method, string $path, string $apiBaseUrl, ?array $payload = null, array $query = []): array
{
    $url = $apiBaseUrl . $path;
    if ($query) {
        $url .= '?' . http_build_query($query);
    }

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => ['Accept: application/json', 'Content-Type: application/json'],
        CURLOPT_TIMEOUT => 20,
    ]);

    if ($payload !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_SLASHES));
    }

    $body = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $error = curl_error($ch) ?: null;
    curl_close($ch);

    $decoded = null;
    if (is_string($body) && $body !== '') {
        $decoded = json_decode($body, true);
    }

    return [
        'status' => $status,
        'error' => $error,
        'body' => $body,
        'json' => is_array($decoded) ? $decoded : null,
    ];
}

function cleanDateTime(?string $value): ?string
{
    if (!$value) {
        return null;
    }

    return str_replace(' ', 'T', trim($value));
}

function buildResourcePayload(string $resourceType, array $input): array
{
    $resource = ['resourceType' => $resourceType];
    $id = trim((string) ($input['id'] ?? ''));
    if ($id !== '') {
        $resource['id'] = $id;
    }

    switch ($resourceType) {
        case 'Patient':
            $resource['name'] = [[
                'family' => trim((string) ($input['family'] ?? '')),
                'given' => [trim((string) ($input['given'] ?? ''))],
            ]];
            if (!empty($input['gender'])) {
                $resource['gender'] = $input['gender'];
            }
            if (!empty($input['birthDate'])) {
                $resource['birthDate'] = $input['birthDate'];
            }
            break;
        case 'Observation':
            $resource['status'] = $input['status'] ?: 'final';
            $resource['code'] = ['text' => trim((string) ($input['code'] ?? ''))];
            $resource['subject'] = ['reference' => trim((string) ($input['subject'] ?? ''))];
            if (!empty($input['effectiveDateTime'])) {
                $resource['effectiveDateTime'] = cleanDateTime($input['effectiveDateTime']);
            }
            if (!empty($input['valueString'])) {
                $resource['valueString'] = trim((string) $input['valueString']);
            }
            break;
        case 'Condition':
            $resource['clinicalStatus'] = ['coding' => [['code' => $input['clinicalStatus'] ?: 'active']]];
            $resource['code'] = ['text' => trim((string) ($input['code'] ?? ''))];
            $resource['subject'] = ['reference' => trim((string) ($input['subject'] ?? ''))];
            if (!empty($input['onsetDateTime'])) {
                $resource['onsetDateTime'] = cleanDateTime($input['onsetDateTime']);
            }
            if (!empty($input['notes'])) {
                $resource['note'] = [['text' => trim((string) $input['notes'])]];
            }
            break;
        case 'MedicationStatement':
            $resource['status'] = $input['status'] ?: 'active';
            $resource['medicationCodeableConcept'] = ['text' => trim((string) ($input['medication'] ?? ''))];
            $resource['subject'] = ['reference' => trim((string) ($input['subject'] ?? ''))];
            if (!empty($input['effectiveDateTime'])) {
                $resource['effectiveDateTime'] = cleanDateTime($input['effectiveDateTime']);
            }
            if (!empty($input['notes'])) {
                $resource['note'] = [['text' => trim((string) $input['notes'])]];
            }
            break;
        case 'Procedure':
            $resource['status'] = $input['status'] ?: 'completed';
            $resource['code'] = ['text' => trim((string) ($input['code'] ?? ''))];
            $resource['subject'] = ['reference' => trim((string) ($input['subject'] ?? ''))];
            if (!empty($input['performedDateTime'])) {
                $resource['performedDateTime'] = cleanDateTime($input['performedDateTime']);
            }
            if (!empty($input['notes'])) {
                $resource['note'] = [['text' => trim((string) $input['notes'])]];
            }
            break;
        case 'AllergyIntolerance':
            $resource['clinicalStatus'] = ['coding' => [['code' => $input['clinicalStatus'] ?: 'active']]];
            $resource['code'] = ['text' => trim((string) ($input['code'] ?? ''))];
            $resource['patient'] = ['reference' => trim((string) ($input['patient'] ?? ''))];
            if (!empty($input['criticality'])) {
                $resource['criticality'] = $input['criticality'];
            }
            if (!empty($input['notes'])) {
                $resource['note'] = [['text' => trim((string) $input['notes'])]];
            }
            break;
        case 'DiagnosticReport':
            $resource['status'] = $input['status'] ?: 'final';
            $resource['code'] = ['text' => trim((string) ($input['code'] ?? ''))];
            $resource['subject'] = ['reference' => trim((string) ($input['subject'] ?? ''))];
            if (!empty($input['effectiveDateTime'])) {
                $resource['effectiveDateTime'] = cleanDateTime($input['effectiveDateTime']);
            }
            if (!empty($input['conclusion'])) {
                $resource['conclusion'] = trim((string) $input['conclusion']);
            }
            break;
        case 'Encounter':
            $resource['status'] = $input['status'] ?: 'planned';
            $resource['class'] = ['code' => trim((string) ($input['class'] ?? 'AMB'))];
            $resource['subject'] = ['reference' => trim((string) ($input['subject'] ?? ''))];
            if (!empty($input['start']) || !empty($input['end'])) {
                $resource['period'] = [];
                if (!empty($input['start'])) {
                    $resource['period']['start'] = cleanDateTime($input['start']);
                }
                if (!empty($input['end'])) {
                    $resource['period']['end'] = cleanDateTime($input['end']);
                }
            }
            break;
        case 'Immunization':
            $resource['status'] = $input['status'] ?: 'completed';
            $resource['vaccineCode'] = ['text' => trim((string) ($input['vaccineCode'] ?? ''))];
            $resource['patient'] = ['reference' => trim((string) ($input['patient'] ?? ''))];
            if (!empty($input['occurrenceDateTime'])) {
                $resource['occurrenceDateTime'] = cleanDateTime($input['occurrenceDateTime']);
            }
            if (!empty($input['lotNumber'])) {
                $resource['lotNumber'] = trim((string) $input['lotNumber']);
            }
            break;
        case 'MedicationRequest':
            $resource['status'] = $input['status'] ?: 'active';
            $resource['intent'] = $input['intent'] ?: 'order';
            $resource['medicationCodeableConcept'] = ['text' => trim((string) ($input['medication'] ?? ''))];
            $resource['subject'] = ['reference' => trim((string) ($input['subject'] ?? ''))];
            if (!empty($input['authoredOn'])) {
                $resource['authoredOn'] = cleanDateTime($input['authoredOn']);
            }
            break;
        case 'ResearchStudy':
            $resource['status'] = $input['status'] ?: 'active';
            if (!empty($input['title'])) {
                $resource['title'] = trim((string) $input['title']);
            }
            if (!empty($input['description'])) {
                $resource['description'] = trim((string) $input['description']);
            }
            if (!empty($input['start']) || !empty($input['end'])) {
                $resource['period'] = [];
                if (!empty($input['start'])) {
                    $resource['period']['start'] = trim((string) $input['start']);
                }
                if (!empty($input['end'])) {
                    $resource['period']['end'] = trim((string) $input['end']);
                }
            }
            break;
        case 'ResearchSubject':
            $resource['status'] = $input['status'] ?: 'on-study';
            $resource['study'] = ['reference' => trim((string) ($input['study'] ?? ''))];
            $resource['individual'] = ['reference' => trim((string) ($input['individual'] ?? ''))];
            if (!empty($input['assignedArm'])) {
                $resource['assignedArm'] = trim((string) $input['assignedArm']);
            }
            if (!empty($input['actualArm'])) {
                $resource['actualArm'] = trim((string) $input['actualArm']);
            }
            break;
        case 'Sample':
            $resource['status'] = $input['status'] ?: 'available';
            $resource['subject'] = ['reference' => trim((string) ($input['subject'] ?? ''))];
            if (!empty($input['type'])) {
                $resource['type'] = ['text' => trim((string) $input['type'])];
            }
            if (!empty($input['collectedDateTime'])) {
                $resource['collection'] = ['collectedDateTime' => cleanDateTime($input['collectedDateTime'])];
            }
            if (!empty($input['quantityValue'])) {
                $resource['quantity'] = ['value' => trim((string) $input['quantityValue'])];
            }
            break;
    }

    return array_filter($resource, static fn($value) => $value !== null && $value !== '' && $value !== []);
}

function formValuesFromResource(string $resourceType, ?array $resource): array
{
    if (!$resource) {
        return [];
    }

    $values = ['id' => $resource['id'] ?? ''];

    switch ($resourceType) {
        case 'Patient':
            $name = $resource['name'][0] ?? [];
            $values['family'] = $name['family'] ?? '';
            $values['given'] = $name['given'][0] ?? '';
            $values['gender'] = $resource['gender'] ?? '';
            $values['birthDate'] = $resource['birthDate'] ?? '';
            break;
        case 'Observation':
            $values['status'] = $resource['status'] ?? '';
            $values['code'] = $resource['code']['text'] ?? '';
            $values['subject'] = $resource['subject']['reference'] ?? '';
            $values['effectiveDateTime'] = isset($resource['effectiveDateTime']) ? substr(str_replace('Z', '', $resource['effectiveDateTime']), 0, 16) : '';
            $values['valueString'] = $resource['valueString'] ?? '';
            break;
        case 'Condition':
            $values['clinicalStatus'] = $resource['clinicalStatus']['coding'][0]['code'] ?? '';
            $values['code'] = $resource['code']['text'] ?? '';
            $values['subject'] = $resource['subject']['reference'] ?? '';
            $values['onsetDateTime'] = isset($resource['onsetDateTime']) ? substr(str_replace('Z', '', $resource['onsetDateTime']), 0, 16) : '';
            $values['notes'] = $resource['note'][0]['text'] ?? '';
            break;
        case 'MedicationStatement':
            $values['status'] = $resource['status'] ?? '';
            $values['medication'] = $resource['medicationCodeableConcept']['text'] ?? '';
            $values['subject'] = $resource['subject']['reference'] ?? '';
            $values['effectiveDateTime'] = isset($resource['effectiveDateTime']) ? substr(str_replace('Z', '', $resource['effectiveDateTime']), 0, 16) : '';
            $values['notes'] = $resource['note'][0]['text'] ?? '';
            break;
        case 'Procedure':
            $values['status'] = $resource['status'] ?? '';
            $values['code'] = $resource['code']['text'] ?? '';
            $values['subject'] = $resource['subject']['reference'] ?? '';
            $values['performedDateTime'] = isset($resource['performedDateTime']) ? substr(str_replace('Z', '', $resource['performedDateTime']), 0, 16) : '';
            $values['notes'] = $resource['note'][0]['text'] ?? '';
            break;
        case 'AllergyIntolerance':
            $values['clinicalStatus'] = $resource['clinicalStatus']['coding'][0]['code'] ?? '';
            $values['code'] = $resource['code']['text'] ?? '';
            $values['patient'] = $resource['patient']['reference'] ?? '';
            $values['criticality'] = $resource['criticality'] ?? '';
            $values['notes'] = $resource['note'][0]['text'] ?? '';
            break;
        case 'DiagnosticReport':
            $values['status'] = $resource['status'] ?? '';
            $values['code'] = $resource['code']['text'] ?? '';
            $values['subject'] = $resource['subject']['reference'] ?? '';
            $values['effectiveDateTime'] = isset($resource['effectiveDateTime']) ? substr(str_replace('Z', '', $resource['effectiveDateTime']), 0, 16) : '';
            $values['conclusion'] = $resource['conclusion'] ?? '';
            break;
        case 'Encounter':
            $values['status'] = $resource['status'] ?? '';
            $values['class'] = $resource['class']['code'] ?? '';
            $values['subject'] = $resource['subject']['reference'] ?? '';
            $values['start'] = isset($resource['period']['start']) ? substr(str_replace('Z', '', $resource['period']['start']), 0, 16) : '';
            $values['end'] = isset($resource['period']['end']) ? substr(str_replace('Z', '', $resource['period']['end']), 0, 16) : '';
            break;
        case 'Immunization':
            $values['status'] = $resource['status'] ?? '';
            $values['vaccineCode'] = $resource['vaccineCode']['text'] ?? '';
            $values['patient'] = $resource['patient']['reference'] ?? '';
            $values['occurrenceDateTime'] = isset($resource['occurrenceDateTime']) ? substr(str_replace('Z', '', $resource['occurrenceDateTime']), 0, 16) : '';
            $values['lotNumber'] = $resource['lotNumber'] ?? '';
            break;
        case 'MedicationRequest':
            $values['status'] = $resource['status'] ?? '';
            $values['intent'] = $resource['intent'] ?? '';
            $values['medication'] = $resource['medicationCodeableConcept']['text'] ?? '';
            $values['subject'] = $resource['subject']['reference'] ?? '';
            $values['authoredOn'] = isset($resource['authoredOn']) ? substr(str_replace('Z', '', $resource['authoredOn']), 0, 16) : '';
            break;
        case 'ResearchStudy':
            $values['status'] = $resource['status'] ?? '';
            $values['title'] = $resource['title'] ?? '';
            $values['description'] = $resource['description'] ?? '';
            $values['start'] = $resource['period']['start'] ?? '';
            $values['end'] = $resource['period']['end'] ?? '';
            break;
        case 'ResearchSubject':
            $values['status'] = $resource['status'] ?? '';
            $values['study'] = $resource['study']['reference'] ?? '';
            $values['individual'] = $resource['individual']['reference'] ?? '';
            $values['assignedArm'] = $resource['assignedArm'] ?? '';
            $values['actualArm'] = $resource['actualArm'] ?? '';
            break;
        case 'Sample':
            $values['status'] = $resource['status'] ?? '';
            $values['subject'] = $resource['subject']['reference'] ?? '';
            $values['type'] = $resource['type']['text'] ?? '';
            $values['collectedDateTime'] = isset($resource['collection']['collectedDateTime']) ? substr(str_replace('Z', '', $resource['collection']['collectedDateTime']), 0, 16) : '';
            $values['quantityValue'] = isset($resource['quantity']['value']) ? (string) $resource['quantity']['value'] : '';
            break;
    }

    return $values;
}

function resourceSummary(array $resource): string
{
    $type = $resource['resourceType'] ?? 'Resource';
    switch ($type) {
        case 'Patient':
            $name = $resource['name'][0] ?? [];
            $display = trim(($name['given'][0] ?? '') . ' ' . ($name['family'] ?? ''));
            return $display !== '' ? $display : ($resource['id'] ?? 'Unnamed patient');
        case 'Observation':
        case 'Condition':
        case 'Procedure':
        case 'DiagnosticReport':
            return $resource['code']['text'] ?? ($resource['id'] ?? $type);
        case 'MedicationStatement':
        case 'MedicationRequest':
            return $resource['medicationCodeableConcept']['text'] ?? ($resource['id'] ?? $type);
        case 'AllergyIntolerance':
            return $resource['code']['text'] ?? ($resource['id'] ?? $type);
        case 'Encounter':
            return ($resource['class']['code'] ?? 'Encounter') . ' ' . ($resource['id'] ?? '');
        case 'Immunization':
            return $resource['vaccineCode']['text'] ?? ($resource['id'] ?? $type);
        case 'ResearchStudy':
            return $resource['title'] ?? ($resource['id'] ?? $type);
        case 'ResearchSubject':
            return $resource['individual']['reference'] ?? ($resource['id'] ?? $type);
        case 'Sample':
            return $resource['type']['text'] ?? ($resource['id'] ?? $type);
        default:
            return $resource['id'] ?? $type;
    }
}

function renderField(array $field, array $values): string
{
    $name = htmlspecialchars($field['name'], ENT_QUOTES, 'UTF-8');
    $label = htmlspecialchars($field['label'], ENT_QUOTES, 'UTF-8');
    $value = htmlspecialchars((string) ($values[$field['name']] ?? ''), ENT_QUOTES, 'UTF-8');
    $placeholder = htmlspecialchars((string) ($field['placeholder'] ?? ''), ENT_QUOTES, 'UTF-8');
    $type = $field['type'];

    if ($type === 'textarea') {
        return "<label><span>{$label}</span><textarea name=\"{$name}\" placeholder=\"{$placeholder}\">{$value}</textarea></label>";
    }

    if ($type === 'select') {
        $options = '<option value="">Select...</option>';
        foreach ($field['options'] as $option) {
            $selected = ($values[$field['name']] ?? '') === $option ? ' selected' : '';
            $escaped = htmlspecialchars($option, ENT_QUOTES, 'UTF-8');
            $options .= "<option value=\"{$escaped}\"{$selected}>{$escaped}</option>";
        }
        return "<label><span>{$label}</span><select name=\"{$name}\">{$options}</select></label>";
    }

    return "<label><span>{$label}</span><input type=\"{$type}\" name=\"{$name}\" value=\"{$value}\" placeholder=\"{$placeholder}\"></label>";
}

function formatQueueValue($value): string
{
    if ($value === null || $value === '') {
        return '';
    }

    if (is_string($value)) {
        $decoded = json_decode($value, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            if (is_scalar($decoded) || $decoded === null) {
                return (string) ($decoded ?? '');
            }
            return json_encode($decoded, JSON_UNESCAPED_SLASHES);
        }
    }

    if (is_scalar($value)) {
        return (string) $value;
    }

    return json_encode($value, JSON_UNESCAPED_SLASHES);
}

$supportedResponse = apiRequest('GET', '/fhir/supported-resources', $apiBaseUrl);
$supportedResources = $supportedResponse['json']['supported_resources'] ?? array_keys($resourceDefinitions);
$selectedResource = $_REQUEST['resourceType'] ?? ($supportedResources[0] ?? 'Patient');
$selectedResource = in_array($selectedResource, $supportedResources, true) ? $selectedResource : ($supportedResources[0] ?? 'Patient');

$flash = null;
$detailResource = null;
$searchResults = [];
$formValues = [];
$searchQuery = trim((string) ($_REQUEST['search'] ?? ''));
$detailId = trim((string) ($_REQUEST['detailId'] ?? ''));
$queueStatus = trim((string) ($_REQUEST['queue_status'] ?? 'pending'));
$queuePatientId = trim((string) ($_REQUEST['queue_patient_id'] ?? ''));
$queueJobId = trim((string) ($_REQUEST['queue_job_id'] ?? ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'create' || $action === 'update') {
        $payload = buildResourcePayload($selectedResource, $_POST);
        $resourceId = trim((string) ($_POST['id'] ?? ''));
        $method = $action === 'create' ? 'POST' : 'PUT';
        $path = $action === 'create'
            ? '/fhir/resources'
            : '/fhir/resources/' . rawurlencode($selectedResource) . '/' . rawurlencode($resourceId);
        $response = apiRequest($method, $path, $apiBaseUrl, $payload);
        $flash = [
            'tone' => ($response['status'] >= 200 && $response['status'] < 300) ? 'success' : 'error',
            'message' => ($response['status'] >= 200 && $response['status'] < 300)
                ? ucfirst($action) . ' successful.'
                : 'FHIR API request failed.',
            'response' => $response,
        ];
        $detailResource = $response['json'];
        $detailId = $detailResource['id'] ?? $resourceId;
        $formValues = formValuesFromResource($selectedResource, $detailResource ?: $payload);
    } elseif ($action === 'delete') {
        $resourceId = trim((string) ($_POST['id'] ?? ''));
        $response = apiRequest('DELETE', '/fhir/resources/' . rawurlencode($selectedResource) . '/' . rawurlencode($resourceId), $apiBaseUrl);
        $flash = [
            'tone' => ($response['status'] >= 200 && $response['status'] < 300) ? 'success' : 'error',
            'message' => ($response['status'] >= 200 && $response['status'] < 300)
                ? 'Delete successful.'
                : 'Delete failed.',
            'response' => $response,
        ];
        $detailId = '';
        $formValues = [];
    } elseif ($action === 'adjudication_accept' || $action === 'adjudication_reject') {
        $adjudicationId = (int) ($_POST['adjudication_id'] ?? 0);
        $selectedValue = trim((string) ($_POST['selected_value'] ?? ''));
        $queueStatus = trim((string) ($_POST['queue_status'] ?? $queueStatus));
        $queuePatientId = trim((string) ($_POST['queue_patient_id'] ?? $queuePatientId));
        $queueJobId = trim((string) ($_POST['queue_job_id'] ?? $queueJobId));

        if ($adjudicationId <= 0) {
            $flash = [
                'tone' => 'error',
                'message' => 'Invalid adjudication ID.',
                'response' => null,
            ];
        } else {
            $endpoint = $action === 'adjudication_accept'
                ? '/cdis/adjudications/' . $adjudicationId . '/accept'
                : '/cdis/adjudications/' . $adjudicationId . '/reject';

            $payload = $action === 'adjudication_accept' && $selectedValue !== ''
                ? ['selected_value' => $selectedValue]
                : [];

            $response = apiRequest('POST', $endpoint, $apiBaseUrl, $payload);
            $success = ($response['status'] ?? 0) >= 200 && ($response['status'] ?? 0) < 300;

            $flash = [
                'tone' => $success ? 'success' : 'error',
                'message' => $success
                    ? ($action === 'adjudication_accept' ? 'Adjudication accepted.' : 'Adjudication rejected.')
                    : 'Failed to update adjudication.',
                'response' => $response,
            ];
        }
    }
}

if ($detailId !== '') {
    $detailResponse = apiRequest('GET', '/fhir/resources/' . rawurlencode($selectedResource) . '/' . rawurlencode($detailId), $apiBaseUrl);
    if (($detailResponse['status'] ?? 0) === 200 && is_array($detailResponse['json'])) {
        $detailResource = $detailResponse['json'];
        $formValues = formValuesFromResource($selectedResource, $detailResource);
    } elseif (!$flash) {
        $flash = [
            'tone' => 'error',
            'message' => 'Resource could not be loaded.',
            'response' => $detailResponse,
        ];
    }
}

$query = [];
if ($searchQuery !== '') {
    $query['_id'] = $searchQuery;
}

$searchResponse = apiRequest('GET', '/fhir/resources/' . rawurlencode($selectedResource), $apiBaseUrl, null, $query);
if (($searchResponse['status'] ?? 0) === 200 && isset($searchResponse['json']['entry']) && is_array($searchResponse['json']['entry'])) {
    foreach ($searchResponse['json']['entry'] as $entry) {
        if (isset($entry['resource']) && is_array($entry['resource'])) {
            $searchResults[] = $entry['resource'];
        }
    }
}

if (!$formValues) {
    $formValues = formValuesFromResource($selectedResource, $detailResource);
}

$statusOptions = ['pending', 'accepted', 'rejected'];
if (!in_array($queueStatus, $statusOptions, true)) {
    $queueStatus = 'pending';
}

$queueQuery = ['status' => $queueStatus];
if ($queuePatientId !== '') {
    $queueQuery['patient_id'] = $queuePatientId;
}
if ($queueJobId !== '') {
    $queueQuery['job_id'] = $queueJobId;
}

$queueResponse = apiRequest('GET', '/cdis/adjudications', $apiBaseUrl, null, $queueQuery);
$adjudicationItems = [];
if (($queueResponse['status'] ?? 0) === 200 && isset($queueResponse['json']['items']) && is_array($queueResponse['json']['items'])) {
    $adjudicationItems = $queueResponse['json']['items'];
}

$pendingCount = 0;
$acceptedCount = 0;
$rejectedCount = 0;

$pendingResponse = apiRequest('GET', '/cdis/adjudications', $apiBaseUrl, null, ['status' => 'pending']);
if (($pendingResponse['status'] ?? 0) === 200) {
    $pendingCount = (int) (($pendingResponse['json']['count'] ?? 0));
}

$acceptedResponse = apiRequest('GET', '/cdis/adjudications', $apiBaseUrl, null, ['status' => 'accepted']);
if (($acceptedResponse['status'] ?? 0) === 200) {
    $acceptedCount = (int) (($acceptedResponse['json']['count'] ?? 0));
}

$rejectedResponse = apiRequest('GET', '/cdis/adjudications', $apiBaseUrl, null, ['status' => 'rejected']);
if (($rejectedResponse['status'] ?? 0) === 200) {
    $rejectedCount = (int) (($rejectedResponse['json']['count'] ?? 0));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>REDCap FHIR Workspace</title>
  <style>
    :root {
      --bg: #f3f5f9;
      --panel: #ffffff;
      --border: #d7deea;
      --text: #162133;
      --muted: #5c6b80;
      --accent: #0057b8;
      --accent-soft: #e8f1ff;
      --success: #157347;
      --success-soft: #e8f6ee;
      --error: #b42318;
      --error-soft: #fdecec;
    }
    * { box-sizing: border-box; }
    body {
      margin: 0;
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
      background: linear-gradient(180deg, #eef3fb 0%, var(--bg) 100%);
      color: var(--text);
    }
    .page {
      max-width: 1400px;
      margin: 0 auto;
      padding: 24px;
    }
    .hero {
      background: radial-gradient(circle at top left, #d9e8ff, #ffffff 60%);
      border: 1px solid var(--border);
      border-radius: 18px;
      padding: 24px;
      margin-bottom: 20px;
    }
    .hero h1 { margin: 0 0 8px; font-size: 32px; }
    .hero p { margin: 0; color: var(--muted); max-width: 880px; }
    .meta {
      display: flex;
      gap: 12px;
      flex-wrap: wrap;
      margin-top: 16px;
    }
    .chip {
      background: var(--accent-soft);
      color: var(--accent);
      padding: 8px 12px;
      border-radius: 999px;
      font-size: 14px;
    }
    .layout {
      display: grid;
      grid-template-columns: 300px 1fr 420px;
      gap: 20px;
    }
    .panel {
      background: var(--panel);
      border: 1px solid var(--border);
      border-radius: 18px;
      padding: 20px;
      box-shadow: 0 12px 32px rgba(17, 24, 39, 0.06);
    }
    .panel h2, .panel h3 { margin-top: 0; }
    .stack { display: grid; gap: 14px; }
    form.stack label { display: grid; gap: 6px; }
    input, select, textarea, button {
      font: inherit;
    }
    input, select, textarea {
      width: 100%;
      border: 1px solid var(--border);
      border-radius: 10px;
      padding: 10px 12px;
      background: #fff;
    }
    textarea { min-height: 110px; resize: vertical; }
    button {
      border: 0;
      border-radius: 10px;
      padding: 11px 14px;
      background: var(--accent);
      color: white;
      cursor: pointer;
      font-weight: 600;
    }
    button.secondary { background: #eff4fb; color: var(--text); }
    button.danger { background: var(--error); }
    .button-row {
      display: flex;
      gap: 10px;
      flex-wrap: wrap;
    }
    .resource-list {
      display: grid;
      gap: 10px;
      max-height: 720px;
      overflow: auto;
    }
    .resource-card {
      border: 1px solid var(--border);
      border-radius: 14px;
      padding: 14px;
      background: #fbfcff;
    }
    .resource-card strong, .resource-card small { display: block; }
    .resource-card small { color: var(--muted); margin: 4px 0 10px; }
    .notice {
      border-radius: 12px;
      padding: 12px 14px;
      margin-bottom: 16px;
    }
    .notice.success { background: var(--success-soft); color: var(--success); }
    .notice.error { background: var(--error-soft); color: var(--error); }
    pre {
      background: #0f172a;
      color: #e2e8f0;
      border-radius: 14px;
      padding: 14px;
      overflow: auto;
      font-size: 13px;
    }
    .muted { color: var(--muted); }
        .queue-toolbar {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 10px;
            margin-bottom: 14px;
        }
        .queue-list {
            display: grid;
            gap: 10px;
            max-height: 680px;
            overflow: auto;
        }
        .queue-item {
            border: 1px solid var(--border);
            border-radius: 14px;
            background: #fbfcff;
            padding: 14px;
            display: grid;
            gap: 10px;
        }
        .queue-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 10px;
            font-size: 14px;
        }
        .queue-grid div span {
            display: block;
            color: var(--muted);
            font-size: 12px;
            margin-bottom: 4px;
        }
        .queue-actions {
            display: grid;
            grid-template-columns: 1fr auto auto;
            gap: 10px;
            align-items: center;
        }
        .status-chip {
            font-size: 12px;
            border-radius: 999px;
            padding: 4px 8px;
            display: inline-block;
            border: 1px solid var(--border);
            background: #f8fafc;
        }
        .status-chip.pending { background: #fff8e1; border-color: #f7d999; }
        .status-chip.accepted { background: #e8f6ee; border-color: #a6d5b8; }
        .status-chip.rejected { background: #fdecec; border-color: #f0b6b1; }
        .inline-form {
            margin: 0;
            display: contents;
        }
        .inline-form input[type="text"] {
            margin: 0;
        }
        .panel-wide {
            margin-top: 20px;
        }
        @media (max-width: 1200px) {
            .queue-toolbar,
            .queue-grid,
            .queue-actions {
                grid-template-columns: 1fr;
            }
        }
    @media (max-width: 1200px) {
      .layout { grid-template-columns: 1fr; }
    }
  </style>
</head>
<body>
  <div class="page">
    <section class="hero">
      <h1>REDCap FHIR Workspace</h1>
      <p>Browse the configured FHIR Aggregator connection, inspect resource payloads, and create, update, or delete records using generated forms for the supported resource types.</p>
      <div class="meta">
        <div class="chip">FHIR API proxy: <?php echo htmlspecialchars($apiBaseUrl, ENT_QUOTES, 'UTF-8'); ?></div>
        <div class="chip">Supported resources: <?php echo count($supportedResources); ?></div>
      </div>
    </section>

    <div class="layout">
      <aside class="panel">
        <h2>Connection</h2>
        <form method="get" class="stack">
          <label>
            <span>Resource Type</span>
            <select name="resourceType" onchange="this.form.submit()">
              <?php foreach ($supportedResources as $resourceType): ?>
                <option value="<?php echo htmlspecialchars($resourceType, ENT_QUOTES, 'UTF-8'); ?>"<?php echo $resourceType === $selectedResource ? ' selected' : ''; ?>><?php echo htmlspecialchars($resourceType, ENT_QUOTES, 'UTF-8'); ?></option>
              <?php endforeach; ?>
            </select>
          </label>
          <label>
            <span>Find by FHIR ID</span>
            <input type="text" name="search" value="<?php echo htmlspecialchars($searchQuery, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Leave blank to browse the latest bundle">
          </label>
          <button type="submit">Browse</button>
        </form>
        <p class="muted">Browsing uses the existing Flask service inside the Docker network, which in turn calls the configured FHIR Aggregator base URL.</p>
      </aside>

      <main class="panel">
        <h2><?php echo htmlspecialchars($selectedResource, ENT_QUOTES, 'UTF-8'); ?> records</h2>
        <p class="muted">Select a record to load it into the generated form. If you provide an ID above, the page narrows results using the `_id` search parameter.</p>
        <div class="resource-list">
          <?php if (!$searchResults): ?>
            <div class="resource-card">
              <strong>No matching resources returned.</strong>
              <small>Try a different resource type, a different ID, or create a new record with the form.</small>
            </div>
          <?php endif; ?>
          <?php foreach ($searchResults as $resource): ?>
            <div class="resource-card">
              <strong><?php echo htmlspecialchars(resourceSummary($resource), ENT_QUOTES, 'UTF-8'); ?></strong>
              <small><?php echo htmlspecialchars(($resource['resourceType'] ?? $selectedResource) . '/' . ($resource['id'] ?? 'unknown'), ENT_QUOTES, 'UTF-8'); ?></small>
              <form method="get" class="button-row">
                <input type="hidden" name="resourceType" value="<?php echo htmlspecialchars($selectedResource, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="detailId" value="<?php echo htmlspecialchars((string) ($resource['id'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                <button type="submit" class="secondary">Load details</button>
              </form>
            </div>
          <?php endforeach; ?>
        </div>
      </main>

      <section class="panel">
        <h2>Generated CRUD Form</h2>
        <?php if ($flash): ?>
          <div class="notice <?php echo htmlspecialchars($flash['tone'], ENT_QUOTES, 'UTF-8'); ?>">
            <strong><?php echo htmlspecialchars($flash['message'], ENT_QUOTES, 'UTF-8'); ?></strong>
            <?php if (!empty($flash['response']['error'])): ?>
              <div><?php echo htmlspecialchars((string) $flash['response']['error'], ENT_QUOTES, 'UTF-8'); ?></div>
            <?php elseif (!empty($flash['response']['json']['error'])): ?>
              <div><?php echo htmlspecialchars((string) $flash['response']['json']['error'], ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>
          </div>
        <?php endif; ?>

        <form method="post" class="stack">
          <input type="hidden" name="resourceType" value="<?php echo htmlspecialchars($selectedResource, ENT_QUOTES, 'UTF-8'); ?>">
          <?php foreach (($resourceDefinitions[$selectedResource] ?? []) as $field): ?>
            <?php echo renderField($field, $formValues); ?>
          <?php endforeach; ?>
          <div class="button-row">
            <button type="submit" name="action" value="create">Create</button>
            <button type="submit" name="action" value="update" class="secondary">Update</button>
            <button type="submit" name="action" value="delete" class="danger">Delete</button>
          </div>
        </form>

        <h3>Current payload</h3>
        <pre><?php echo htmlspecialchars(json_encode($detailResource ?: buildResourcePayload($selectedResource, $formValues), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8'); ?></pre>
      </section>
    </div>

        <section class="panel panel-wide">
            <h2>Adjudication Queue</h2>
            <p class="muted">Review CDP values before final use. Filter by status, patient, or job ID, then accept or reject each queued mapping.</p>

            <div class="meta" style="margin-bottom: 14px;">
                <div class="chip">Pending: <?php echo $pendingCount; ?></div>
                <div class="chip">Accepted: <?php echo $acceptedCount; ?></div>
                <div class="chip">Rejected: <?php echo $rejectedCount; ?></div>
            </div>

            <form method="get" class="queue-toolbar">
                <select name="queue_status">
                    <?php foreach ($statusOptions as $statusOption): ?>
                        <option value="<?php echo htmlspecialchars($statusOption, ENT_QUOTES, 'UTF-8'); ?>"<?php echo $statusOption === $queueStatus ? ' selected' : ''; ?>><?php echo htmlspecialchars(ucfirst($statusOption), ENT_QUOTES, 'UTF-8'); ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="text" name="queue_patient_id" placeholder="Filter by patient ID" value="<?php echo htmlspecialchars($queuePatientId, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="text" name="queue_job_id" placeholder="Filter by job ID" value="<?php echo htmlspecialchars($queueJobId, ENT_QUOTES, 'UTF-8'); ?>">
                <button type="submit">Apply filters</button>

                <input type="hidden" name="resourceType" value="<?php echo htmlspecialchars($selectedResource, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="search" value="<?php echo htmlspecialchars($searchQuery, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="detailId" value="<?php echo htmlspecialchars($detailId, ENT_QUOTES, 'UTF-8'); ?>">
            </form>

            <div class="queue-list">
                <?php if (!$adjudicationItems): ?>
                    <div class="queue-item">
                        <strong>No adjudication items found for current filters.</strong>
                    </div>
                <?php endif; ?>

                <?php foreach ($adjudicationItems as $item): ?>
                    <div class="queue-item">
                        <div class="queue-grid">
                            <div><span>ID</span><?php echo (int) ($item['id'] ?? 0); ?></div>
                            <div><span>Job</span><?php echo htmlspecialchars((string) ($item['job_id'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
                            <div><span>Patient</span><?php echo htmlspecialchars((string) ($item['patient_id'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
                            <div><span>Status</span><span class="status-chip <?php echo htmlspecialchars((string) ($item['status'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars((string) ($item['status'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span></div>
                        </div>

                        <div class="queue-grid">
                            <div><span>REDCap Field</span><?php echo htmlspecialchars((string) ($item['redcap_field'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
                            <div><span>Resource Type</span><?php echo htmlspecialchars((string) ($item['resource_type'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
                            <div><span>Resource ID</span><?php echo htmlspecialchars((string) ($item['resource_id'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
                            <div><span>Proposed Value</span><?php echo htmlspecialchars(formatQueueValue($item['proposed_value'] ?? null), ENT_QUOTES, 'UTF-8'); ?></div>
                        </div>

                        <div class="queue-actions">
                            <form method="post" class="inline-form">
                                <input type="hidden" name="action" value="adjudication_accept">
                                <input type="hidden" name="adjudication_id" value="<?php echo (int) ($item['id'] ?? 0); ?>">
                                <input type="hidden" name="queue_status" value="<?php echo htmlspecialchars($queueStatus, ENT_QUOTES, 'UTF-8'); ?>">
                                <input type="hidden" name="queue_patient_id" value="<?php echo htmlspecialchars($queuePatientId, ENT_QUOTES, 'UTF-8'); ?>">
                                <input type="hidden" name="queue_job_id" value="<?php echo htmlspecialchars($queueJobId, ENT_QUOTES, 'UTF-8'); ?>">
                                <input type="text" name="selected_value" placeholder="Optional override value" value="<?php echo htmlspecialchars(formatQueueValue($item['selected_value'] ?? null), ENT_QUOTES, 'UTF-8'); ?>">
                                <button type="submit"<?php echo ($item['status'] ?? '') === 'accepted' ? ' disabled' : ''; ?>>Accept</button>
                            </form>

                            <form method="post" class="inline-form">
                                <input type="hidden" name="action" value="adjudication_reject">
                                <input type="hidden" name="adjudication_id" value="<?php echo (int) ($item['id'] ?? 0); ?>">
                                <input type="hidden" name="queue_status" value="<?php echo htmlspecialchars($queueStatus, ENT_QUOTES, 'UTF-8'); ?>">
                                <input type="hidden" name="queue_patient_id" value="<?php echo htmlspecialchars($queuePatientId, ENT_QUOTES, 'UTF-8'); ?>">
                                <input type="hidden" name="queue_job_id" value="<?php echo htmlspecialchars($queueJobId, ENT_QUOTES, 'UTF-8'); ?>">
                                <button type="submit" class="danger"<?php echo ($item['status'] ?? '') === 'rejected' ? ' disabled' : ''; ?>>Reject</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
  </div>
</body>
</html>
