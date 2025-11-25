<?php

class CalendarFeedController
{
    public function feed(): void
    {
        // Simple authenticated ICS feed for current user (initial version)
        if (!Auth::check()) {
            http_response_code(401);
            echo 'Unauthorized';
            return;
        }

        $user = Auth::user();
        $userId = (int)$user['id'];

        header('Content-Type: text/calendar; charset=utf-8');
        header('Content-Disposition: inline; filename="calendar.ics"');

        $db = Database::getInstance();
        // Fetch jobs for next 90 days for this account
        $rows = $db->fetchAll("SELECT j.* , c.name AS customer_name, s.name AS service_name
                               FROM jobs j
                               LEFT JOIN customers c ON c.id = j.customer_id
                               LEFT JOIN services s ON s.id = j.service_id
                               WHERE date(j.start_at) >= date('now','-30 day') AND date(j.start_at) <= date('now','+90 day')");

        $lines = [];
        $lines[] = 'BEGIN:VCALENDAR';
        $lines[] = 'VERSION:2.0';
        $lines[] = 'PRODID:-//Kure Temizlik//Calendar//TR';
        $lines[] = 'CALSCALE:GREGORIAN';
        $tz = 'Europe/Istanbul';
        foreach ($rows as $row) {
            $uid = 'job-' . $row['id'] . '@kure';
            $start = gmdate('Ymd\THis\Z', strtotime($row['start_at']));
            $end = gmdate('Ymd\THis\Z', strtotime($row['end_at'] ?: $row['start_at']));
            $summary = ($row['service_name'] ? ($row['service_name'] . ' - ') : '') . ($row['customer_name'] ?? '');
            $desc = trim(($row['notes'] ?? ''));
            $status = ($row['status'] ?? 'SCHEDULED');
            $lines[] = 'BEGIN:VEVENT';
            $lines[] = 'UID:' . $uid;
            $lines[] = 'DTSTART:' . $start;
            $lines[] = 'DTEND:' . $end;
            $lines[] = 'SUMMARY:' . $this->escapeIcs($summary);
            if ($desc !== '') {
                $lines[] = 'DESCRIPTION:' . $this->escapeIcs($desc);
            }
            $lines[] = 'STATUS:' . ($status === 'DONE' ? 'CONFIRMED' : ($status === 'CANCELLED' ? 'CANCELLED' : 'TENTATIVE'));
            // optional default alarm 30 minutes before
            $lines[] = 'BEGIN:VALARM';
            $lines[] = 'TRIGGER:-PT30M';
            $lines[] = 'ACTION:DISPLAY';
            $lines[] = 'DESCRIPTION:Reminder';
            $lines[] = 'END:VALARM';
            $lines[] = 'END:VEVENT';
        }
        $lines[] = 'END:VCALENDAR';
        echo implode("\r\n", $lines);
    }

    private function escapeIcs(string $text): string
    {
        $text = str_replace(['\\', ';', ',', "\n"], ['\\\\', '\\;', '\\,', '\\n'], $text);
        return $text;
    }
}


