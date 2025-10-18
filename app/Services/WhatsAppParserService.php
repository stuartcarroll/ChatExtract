<?php

namespace App\Services;

use Carbon\Carbon;
use Exception;

class WhatsAppParserService
{
    /**
     * Parse a WhatsApp chat export file.
     *
     * @param string $filePath Path to the WhatsApp export .txt file
     * @return array Array of parsed messages
     * @throws Exception
     */
    public function parseFile(string $filePath): array
    {
        if (!file_exists($filePath)) {
            throw new Exception("File not found: {$filePath}");
        }

        $content = file_get_contents($filePath);

        if ($content === false) {
            throw new Exception("Failed to read file: {$filePath}");
        }

        return $this->parseContent($content);
    }

    /**
     * Parse WhatsApp chat content.
     *
     * @param string $content The content of the WhatsApp export
     * @return array Array of parsed messages
     */
    public function parseContent(string $content): array
    {
        $lines = explode("\n", $content);
        $messages = [];
        $currentMessage = null;

        foreach ($lines as $line) {
            $line = trim($line);

            if (empty($line)) {
                continue;
            }

            // Try to parse as a new message
            $parsed = $this->parseMessageLine($line);

            if ($parsed !== null) {
                // Save previous message if exists
                if ($currentMessage !== null) {
                    $messages[] = $currentMessage;
                }

                $currentMessage = $parsed;
            } else {
                // This is a continuation of the previous message (multi-line)
                if ($currentMessage !== null) {
                    $currentMessage['content'] .= "\n" . $line;
                }
            }
        }

        // Add the last message
        if ($currentMessage !== null) {
            $messages[] = $currentMessage;
        }

        // Split messages that contain embedded media into separate messages
        $messages = $this->splitEmbeddedMedia($messages);

        return $messages;
    }

    /**
     * Split messages that have embedded media lines into separate messages.
     * For example, a message followed by "[timestamp] Name: <attached: file.opus>" should be two messages.
     *
     * @param array $messages
     * @return array
     */
    protected function splitEmbeddedMedia(array $messages): array
    {
        $splitMessages = [];

        foreach ($messages as $message) {
            $content = $message['content'];

            // Look for embedded message lines with media attachments
            // Pattern: newline followed by timestamp/participant and media attachment
            $pattern = '/\n(‎?\[?\d{1,2}\/\d{1,2}\/\d{2,4}[^\n]*?:\s*‎?<attached:[^>]+>)/u';

            if (preg_match($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
                // Split the content
                $firstPart = substr($content, 0, $matches[0][1]); // Before the embedded line
                $secondPart = $matches[1][0]; // The embedded media line

                // Create first message with the text before the media
                if (trim($firstPart)) {
                    $splitMessages[] = [
                        'timestamp' => $message['timestamp'],
                        'participant' => $message['participant'],
                        'content' => trim($firstPart),
                        'is_system_message' => $message['is_system_message'],
                        'has_media' => false,
                        'media_info' => null,
                    ];
                }

                // Parse the embedded media line as a separate message
                $embeddedLine = ltrim($secondPart, "\n\r‎");
                $parsedEmbedded = $this->parseMessageLine($embeddedLine);

                if ($parsedEmbedded !== null) {
                    $splitMessages[] = $parsedEmbedded;
                } else {
                    // If parsing failed, add it as part of the original message
                    $splitMessages[] = $message;
                }
            } else {
                // No embedded media, keep as-is
                $splitMessages[] = $message;
            }
        }

        return $splitMessages;
    }

    /**
     * Parse a single message line.
     *
     * @param string $line The line to parse
     * @return array|null Parsed message data or null if not a message line
     */
    protected function parseMessageLine(string $line): ?array
    {
        // Try multiple date formats
        $patterns = [
            // DD/MM/YYYY, HH:MM - Participant: Message
            '/^(\d{1,2}\/\d{1,2}\/\d{2,4}),?\s+(\d{1,2}:\d{2})\s*[-–]\s*([^:]+?):\s*(.*)$/u',

            // DD/MM/YY, HH:MM AM/PM - Participant: Message
            '/^(\d{1,2}\/\d{1,2}\/\d{2,4}),?\s+(\d{1,2}:\d{2}\s*(?:AM|PM|am|pm))\s*[-–]\s*([^:]+?):\s*(.*)$/u',

            // [DD/MM/YYYY, HH:MM:SS] Participant: Message
            '/^\[(\d{1,2}\/\d{1,2}\/\d{2,4}),?\s+(\d{1,2}:\d{2}(?::\d{2})?)\]\s*([^:]+?):\s*(.*)$/u',

            // DD.MM.YY, HH:MM - Participant: Message (European format)
            '/^(\d{1,2}\.\d{1,2}\.\d{2,4}),?\s+(\d{1,2}:\d{2})\s*[-–]\s*([^:]+?):\s*(.*)$/u',

            // YYYY-MM-DD HH:MM:SS - Participant: Message
            '/^(\d{4}-\d{2}-\d{2})\s+(\d{2}:\d{2}:\d{2})\s*[-–]\s*([^:]+?):\s*(.*)$/u',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $line, $matches)) {
                try {
                    $dateStr = $matches[1];
                    $timeStr = $matches[2];
                    $participant = trim($matches[3]);
                    $content = $matches[4];

                    // Parse the date and time
                    $timestamp = $this->parseDateTime($dateStr, $timeStr);

                    // Detect if this is a system message
                    $isSystemMessage = $this->isSystemMessage($participant, $content);

                    // Detect media attachments
                    $mediaInfo = $this->detectMedia($content);

                    return [
                        'timestamp' => $timestamp,
                        'participant' => $participant,
                        'content' => $content,
                        'is_system_message' => $isSystemMessage,
                        'has_media' => $mediaInfo !== null,
                        'media_type' => $mediaInfo['type'] ?? null,
                        'media_filename' => $mediaInfo['filename'] ?? null,
                    ];
                } catch (Exception $e) {
                    // If parsing fails, continue to next pattern
                    continue;
                }
            }
        }

        return null;
    }

    /**
     * Parse date and time string into Carbon instance.
     *
     * @param string $dateStr Date string
     * @param string $timeStr Time string
     * @return Carbon
     * @throws Exception
     */
    protected function parseDateTime(string $dateStr, string $timeStr): Carbon
    {
        // Try various date formats
        $dateFormats = [
            'd/m/Y',
            'd/m/y',
            'd.m.Y',
            'd.m.y',
            'm/d/Y',
            'm/d/y',
            'Y-m-d',
        ];

        // Try various time formats
        $timeFormats = [
            'H:i',
            'h:i A',
            'h:i a',
            'H:i:s',
        ];

        foreach ($dateFormats as $dateFormat) {
            foreach ($timeFormats as $timeFormat) {
                try {
                    $dateTimeStr = $dateStr . ' ' . $timeStr;
                    $format = $dateFormat . ' ' . $timeFormat;
                    $carbon = Carbon::createFromFormat($format, $dateTimeStr);

                    if ($carbon !== false) {
                        return $carbon;
                    }
                } catch (Exception $e) {
                    continue;
                }
            }
        }

        throw new Exception("Failed to parse date/time: {$dateStr} {$timeStr}");
    }

    /**
     * Detect if a message is a system message.
     *
     * @param string $participant Participant name
     * @param string $content Message content
     * @return bool
     */
    protected function isSystemMessage(string $participant, string $content): bool
    {
        // System message indicators
        $systemIndicators = [
            'changed the subject',
            'changed this group\'s icon',
            'You created group',
            'added',
            'left',
            'removed',
            'joined using this group\'s invite link',
            'changed their phone number',
            'changed the group description',
            'Messages and calls are end-to-end encrypted',
            'security code changed',
            'missed voice call',
            'missed video call',
            'This message was deleted',
        ];

        foreach ($systemIndicators as $indicator) {
            if (stripos($content, $indicator) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Detect media attachments in a message.
     *
     * @param string $content Message content
     * @return array|null Media information or null if no media detected
     */
    protected function detectMedia(string $content): ?array
    {
        $mediaPatterns = [
            'image' => [
                '/\<attached:\s*(.+\.(?:jpg|jpeg|png|gif|webp))\>/i',
                '/image omitted/i',
                '/IMG[-_]\d+\.(?:jpg|jpeg|png|gif|webp)/i',
            ],
            'video' => [
                '/\<attached:\s*(.+\.(?:mp4|avi|mov|mkv|webm))\>/i',
                '/video omitted/i',
                '/VID[-_]\d+\.(?:mp4|avi|mov|mkv|webm)/i',
            ],
            'audio' => [
                '/\<attached:\s*(.+\.(?:mp3|ogg|opus|m4a|aac))\>/i',
                '/audio omitted/i',
                '/PTT[-_]\d+\.(?:mp3|ogg|opus|m4a|aac)/i',
            ],
            'document' => [
                '/\<attached:\s*(.+\.(?:pdf|doc|docx|xls|xlsx|txt))\>/i',
                '/document omitted/i',
                '/\.(pdf|doc|docx|xls|xlsx|txt)\s*$/i',
            ],
        ];

        foreach ($mediaPatterns as $type => $patterns) {
            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $content, $matches)) {
                    return [
                        'type' => $type,
                        'filename' => $matches[1] ?? null,
                    ];
                }
            }
        }

        return null;
    }

    /**
     * Get statistics about the parsed chat.
     *
     * @param array $messages Array of parsed messages
     * @return array Statistics
     */
    public function getStatistics(array $messages): array
    {
        $stats = [
            'total_messages' => count($messages),
            'system_messages' => 0,
            'user_messages' => 0,
            'media_messages' => 0,
            'participants' => [],
            'date_range' => [
                'start' => null,
                'end' => null,
            ],
        ];

        foreach ($messages as $message) {
            if ($message['is_system_message']) {
                $stats['system_messages']++;
            } else {
                $stats['user_messages']++;
            }

            if ($message['has_media']) {
                $stats['media_messages']++;
            }

            // Count messages per participant
            $participant = $message['participant'];
            if (!isset($stats['participants'][$participant])) {
                $stats['participants'][$participant] = 0;
            }
            $stats['participants'][$participant]++;

            // Track date range
            if ($stats['date_range']['start'] === null || $message['timestamp']->lt($stats['date_range']['start'])) {
                $stats['date_range']['start'] = $message['timestamp'];
            }
            if ($stats['date_range']['end'] === null || $message['timestamp']->gt($stats['date_range']['end'])) {
                $stats['date_range']['end'] = $message['timestamp'];
            }
        }

        return $stats;
    }
}
