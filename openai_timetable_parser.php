<?php
require_once "functions.php";

function bvassist_openai_api_key(): string
{
    $candidates = [
        getenv('OPENAI_API_KEY'),
        $_ENV['OPENAI_API_KEY'] ?? null,
        $_SERVER['OPENAI_API_KEY'] ?? null,
    ];

    foreach ($candidates as $candidate) {
        if (is_string($candidate) && trim($candidate) !== '') {
            return trim($candidate);
        }
    }

    return '';
}

function bvassist_openai_timetable_model(): string
{
    $candidates = [
        getenv('OPENAI_TIMETABLE_MODEL'),
        $_ENV['OPENAI_TIMETABLE_MODEL'] ?? null,
        $_SERVER['OPENAI_TIMETABLE_MODEL'] ?? null,
    ];

    foreach ($candidates as $candidate) {
        if (is_string($candidate) && trim($candidate) !== '') {
            return trim($candidate);
        }
    }

    return 'gpt-4o';
}

function bvassist_ensure_timetable_entries_table(mysqli $conn): bool
{
    $sql = "CREATE TABLE IF NOT EXISTS `timetable_entries` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `timetable_id` INT UNSIGNED NOT NULL,
        `timetable_title` VARCHAR(255) NOT NULL,
        `timetable_type` VARCHAR(50) NOT NULL,
        `day` VARCHAR(40) NOT NULL,
        `time_slot` VARCHAR(100) NOT NULL,
        `subject` VARCHAR(255) NOT NULL,
        `room` VARCHAR(120) NOT NULL DEFAULT '',
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `idx_timetable_entries_timetable_id` (`timetable_id`),
        KEY `idx_timetable_entries_day` (`day`),
        KEY `idx_timetable_entries_created_at` (`created_at`),
        CONSTRAINT `fk_timetable_entries_timetable`
            FOREIGN KEY (`timetable_id`) REFERENCES `timetables`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    return mysqli_query($conn, $sql) !== false;
}

function bvassist_has_timetable_entries_table(mysqli $conn): bool
{
    $result = mysqli_query($conn, "SHOW TABLES LIKE 'timetable_entries'");
    return $result !== false && mysqli_num_rows($result) > 0;
}

function bvassist_ensure_timetables_assignment_schema(mysqli $conn): bool
{
    $userIdColumn = mysqli_query($conn, "SHOW COLUMNS FROM `timetables` LIKE 'user_id'");
    if (!$userIdColumn || mysqli_num_rows($userIdColumn) === 0) {
        return false;
    }

    return true;
}

function bvassist_detect_file_mime(string $filePath): string
{
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = $finfo ? (string) finfo_file($finfo, $filePath) : '';
    if ($finfo) {
        finfo_close($finfo);
    }

    if ($mime !== '') {
        return $mime;
    }

    $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    return match ($extension) {
        'pdf' => 'application/pdf',
        'jpg', 'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
        default => 'application/octet-stream',
    };
}

function bvassist_convert_pdf_to_png_preview(string $pdfPath): ?array
{
    if (!class_exists('Imagick')) {
        return null;
    }

    try {
        $imagick = new Imagick();
        $imagick->setResolution(200, 200);
        $imagick->readImage($pdfPath . '[0]');
        $imagick->setImageFormat('png');

        $tempBase = tempnam(sys_get_temp_dir(), 'bv_tt_');
        if ($tempBase === false) {
            $imagick->clear();
            $imagick->destroy();
            return null;
        }

        @unlink($tempBase);
        $pngPath = $tempBase . '.png';
        $imagick->writeImage($pngPath);
        $imagick->clear();
        $imagick->destroy();

        if (!is_file($pngPath)) {
            return null;
        }

        return [
            'path' => $pngPath,
            'mime' => 'image/png',
            'cleanup' => true,
        ];
    } catch (Throwable $exception) {
        return null;
    }
}

function bvassist_timetable_prompt(): string
{
    return "Extract timetable rows from the provided file. Return each valid class row with day, time, subject, and room. Ignore Tuesday entirely. If room is missing, use an empty string. Normalize short day names to full names. Skip decorative text, legends, and empty cells.";
}

function bvassist_timetable_response_schema(): array
{
    return [
        'type' => 'object',
        'properties' => [
            'entries' => [
                'type' => 'array',
                'items' => [
                    'type' => 'object',
                    'properties' => [
                        'day' => ['type' => 'string'],
                        'time' => ['type' => 'string'],
                        'subject' => ['type' => 'string'],
                        'room' => ['type' => 'string'],
                    ],
                    'required' => ['day', 'time', 'subject', 'room'],
                    'additionalProperties' => false,
                ],
            ],
        ],
        'required' => ['entries'],
        'additionalProperties' => false,
    ];
}

function bvassist_prepare_timetable_input(string $filePath): array
{
    $mime = bvassist_detect_file_mime($filePath);
    $warning = '';
    $cleanupPath = null;

    if ($mime === 'application/pdf') {
        $preview = bvassist_convert_pdf_to_png_preview($filePath);
        if ($preview !== null) {
            $cleanupPath = $preview['cleanup'] ? $preview['path'] : null;
            $base64 = base64_encode((string) file_get_contents($preview['path']));

            return [
                'ok' => true,
                'content' => [
                    [
                        'type' => 'input_image',
                        'image_url' => 'data:' . $preview['mime'] . ';base64,' . $base64,
                    ],
                ],
                'warning' => $warning,
                'cleanup_path' => $cleanupPath,
            ];
        }

        $warning = 'PDF preview conversion is unavailable on this server, so the original PDF was sent directly to OpenAI.';
        $base64 = base64_encode((string) file_get_contents($filePath));

        return [
            'ok' => true,
            'content' => [
                [
                    'type' => 'input_file',
                    'filename' => basename($filePath),
                    'file_data' => 'data:application/pdf;base64,' . $base64,
                ],
            ],
            'warning' => $warning,
            'cleanup_path' => null,
        ];
    }

    if (!str_starts_with($mime, 'image/')) {
        return [
            'ok' => false,
            'error' => 'The uploaded timetable must be a PDF or image file.',
        ];
    }

    $base64 = base64_encode((string) file_get_contents($filePath));

    return [
        'ok' => true,
        'content' => [
            [
                'type' => 'input_image',
                'image_url' => 'data:' . $mime . ';base64,' . $base64,
            ],
        ],
        'warning' => '',
        'cleanup_path' => null,
    ];
}

function bvassist_call_openai_responses_api(array $payload, string $apiKey): array
{
    if (!function_exists('curl_init')) {
        return ['ok' => false, 'error' => 'The PHP cURL extension is not enabled.'];
    }

    $ch = curl_init('https://api.openai.com/v1/responses');
    if ($ch === false) {
        return ['ok' => false, 'error' => 'Could not initialize cURL.'];
    }

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_TIMEOUT => 90,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey,
        ],
        CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_SLASHES),
    ]);

    $rawResponse = curl_exec($ch);
    $curlError = curl_error($ch);
    $statusCode = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);

    if ($rawResponse === false) {
        return ['ok' => false, 'error' => 'OpenAI request failed: ' . $curlError];
    }

    $decoded = json_decode($rawResponse, true);
    if (!is_array($decoded)) {
        return ['ok' => false, 'error' => 'OpenAI returned an unreadable response.'];
    }

    if ($statusCode >= 400) {
        $message = $decoded['error']['message'] ?? 'OpenAI returned an error.';
        return ['ok' => false, 'error' => (string) $message];
    }

    return ['ok' => true, 'data' => $decoded];
}

function bvassist_extract_output_text(array $response): string
{
    if (!empty($response['output_text']) && is_string($response['output_text'])) {
        return trim($response['output_text']);
    }

    foreach (($response['output'] ?? []) as $outputItem) {
        foreach (($outputItem['content'] ?? []) as $contentItem) {
            if (!empty($contentItem['text']) && is_string($contentItem['text'])) {
                return trim($contentItem['text']);
            }
        }
    }

    return '';
}

function bvassist_strip_json_wrappers(string $text): string
{
    $text = trim($text);
    $text = preg_replace('/^```json\s*/i', '', $text);
    $text = preg_replace('/^```\s*/', '', $text);
    $text = preg_replace('/\s*```$/', '', $text);

    return trim((string) $text);
}

function bvassist_normalize_day(string $day): string
{
    $normalized = strtolower(trim($day));

    return match ($normalized) {
        'mon', 'monday' => 'Monday',
        'tue', 'tues', 'tuesday' => 'Tuesday',
        'wed', 'wednesday' => 'Wednesday',
        'thu', 'thur', 'thurs', 'thursday' => 'Thursday',
        'fri', 'friday' => 'Friday',
        'sat', 'saturday' => 'Saturday',
        'sun', 'sunday' => 'Sunday',
        default => trim($day),
    };
}

function bvassist_normalize_timetable_entries(array $decoded): array
{
    $entries = $decoded['entries'] ?? $decoded;
    if (!is_array($entries)) {
        return [];
    }

    $normalizedEntries = [];
    $seen = [];

    foreach ($entries as $entry) {
        if (!is_array($entry)) {
            continue;
        }

        $day = bvassist_normalize_day((string) ($entry['day'] ?? ''));
        $time = trim((string) ($entry['time'] ?? ''));
        $subject = trim((string) ($entry['subject'] ?? ''));
        $room = trim((string) ($entry['room'] ?? ''));

        if ($day === '' || $time === '' || $subject === '') {
            continue;
        }

        if (strcasecmp($day, 'Tuesday') === 0) {
            continue;
        }

        $dedupeKey = strtolower($day . '|' . $time . '|' . $subject . '|' . $room);
        if (isset($seen[$dedupeKey])) {
            continue;
        }

        $seen[$dedupeKey] = true;
        $normalizedEntries[] = [
            'day' => $day,
            'time' => $time,
            'subject' => $subject,
            'room' => $room,
        ];
    }

    return $normalizedEntries;
}

function bvassist_store_timetable_entries(
    mysqli $conn,
    int $timetableId,
    string $title,
    string $type,
    array $entries
): bool {
    if (!bvassist_ensure_timetable_entries_table($conn)) {
        return false;
    }

    mysqli_begin_transaction($conn);

    $deleteStmt = mysqli_prepare($conn, "DELETE FROM timetable_entries WHERE timetable_id = ?");
    if (!$deleteStmt) {
        mysqli_rollback($conn);
        return false;
    }

    mysqli_stmt_bind_param($deleteStmt, 'i', $timetableId);
    if (!mysqli_stmt_execute($deleteStmt)) {
        mysqli_stmt_close($deleteStmt);
        mysqli_rollback($conn);
        return false;
    }
    mysqli_stmt_close($deleteStmt);

    $insertStmt = mysqli_prepare(
        $conn,
        "INSERT INTO timetable_entries (timetable_id, timetable_title, timetable_type, day, time_slot, subject, room) VALUES (?, ?, ?, ?, ?, ?, ?)"
    );

    if (!$insertStmt) {
        mysqli_rollback($conn);
        return false;
    }

    foreach ($entries as $entry) {
        $day = $entry['day'];
        $time = $entry['time'];
        $subject = $entry['subject'];
        $room = $entry['room'];

        mysqli_stmt_bind_param($insertStmt, 'issssss', $timetableId, $title, $type, $day, $time, $subject, $room);
        if (!mysqli_stmt_execute($insertStmt)) {
            mysqli_stmt_close($insertStmt);
            mysqli_rollback($conn);
            return false;
        }
    }

    mysqli_stmt_close($insertStmt);
    mysqli_commit($conn);

    return true;
}

function bvassist_fetch_timetable_entries_by_timetable_ids(mysqli $conn, array $timetableIds): array
{
    if (empty($timetableIds) || !bvassist_has_timetable_entries_table($conn)) {
        return [];
    }

    $filteredIds = [];
    foreach ($timetableIds as $timetableId) {
        $timetableId = (int) $timetableId;
        if ($timetableId > 0) {
            $filteredIds[] = $timetableId;
        }
    }

    $filteredIds = array_values(array_unique($filteredIds));
    if (empty($filteredIds)) {
        return [];
    }

    $placeholders = implode(', ', array_fill(0, count($filteredIds), '?'));
    $types = str_repeat('i', count($filteredIds));
    $sql = "SELECT timetable_id, day, time_slot, subject, room
            FROM timetable_entries
            WHERE timetable_id IN ({$placeholders})
            ORDER BY FIELD(day, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'), time_slot ASC, id ASC";

    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        return [];
    }

    mysqli_stmt_bind_param($stmt, $types, ...$filteredIds);
    if (!mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        return [];
    }

    $result = mysqli_stmt_get_result($stmt);
    $groupedEntries = [];

    while ($result && ($row = mysqli_fetch_assoc($result))) {
        $groupedEntries[(int) $row['timetable_id']][] = $row;
    }

    mysqli_stmt_close($stmt);

    return $groupedEntries;
}

function bvassist_extract_timetable_with_ai(
    mysqli $conn,
    int $timetableId,
    string $title,
    string $type,
    string $filePath
): array {
    $apiKey = bvassist_openai_api_key();
    if ($apiKey === '') {
        return ['ok' => false, 'error' => 'OPENAI_API_KEY is not configured.'];
    }

    if (!is_file($filePath)) {
        return ['ok' => false, 'error' => 'Uploaded timetable file could not be found for AI parsing.'];
    }

    $preparedInput = bvassist_prepare_timetable_input($filePath);
    if (!$preparedInput['ok']) {
        return ['ok' => false, 'error' => $preparedInput['error'] ?? 'Could not prepare the timetable file for AI parsing.'];
    }

    $payload = [
        'model' => bvassist_openai_timetable_model(),
        'input' => [
            [
                'role' => 'system',
                'content' => bvassist_timetable_prompt(),
            ],
            [
                'role' => 'user',
                'content' => array_merge(
                    [
                        [
                            'type' => 'input_text',
                            'text' => 'Extract timetable entries as structured JSON.',
                        ],
                    ],
                    $preparedInput['content']
                ),
            ],
        ],
        'text' => [
            'format' => [
                'type' => 'json_schema',
                'name' => 'timetable_entries',
                'schema' => bvassist_timetable_response_schema(),
                'strict' => true,
            ],
        ],
    ];

    $apiResponse = bvassist_call_openai_responses_api($payload, $apiKey);

    if (!empty($preparedInput['cleanup_path']) && is_file($preparedInput['cleanup_path'])) {
        @unlink($preparedInput['cleanup_path']);
    }

    if (!$apiResponse['ok']) {
        return ['ok' => false, 'error' => $apiResponse['error'] ?? 'OpenAI did not return a usable result.'];
    }

    $outputText = bvassist_extract_output_text($apiResponse['data']);
    if ($outputText === '') {
        return ['ok' => false, 'error' => 'OpenAI returned an empty timetable result.'];
    }

    $decoded = json_decode(bvassist_strip_json_wrappers($outputText), true);
    if (!is_array($decoded)) {
        return ['ok' => false, 'error' => 'OpenAI returned invalid JSON for the timetable.'];
    }

    $entries = bvassist_normalize_timetable_entries($decoded);
    if (empty($entries)) {
        return ['ok' => false, 'error' => 'No timetable rows could be extracted from the uploaded file.'];
    }

    if (!bvassist_store_timetable_entries($conn, $timetableId, $title, $type, $entries)) {
        return ['ok' => false, 'error' => 'The extracted timetable rows could not be stored in MySQL.'];
    }

    return [
        'ok' => true,
        'count' => count($entries),
        'warning' => $preparedInput['warning'] ?? '',
        'raw' => $decoded,
    ];
}
