<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class StoryDetectionService
{
    /**
     * Detect if a message contains a story.
     *
     * @param string $content The message content
     * @param array $context Optional context (previous messages, participant info)
     * @return array Detection result with is_story, confidence, and reasoning
     */
    public function detectStory(string $content, array $context = []): array
    {
        // First, try pattern-based detection
        $patternResult = $this->patternBasedDetection($content);

        // If pattern detection has high confidence, return it
        if ($patternResult['confidence'] >= 0.8) {
            return $patternResult;
        }

        // Try AI-based detection if configured
        $aiResult = $this->aiBasedDetection($content, $context);

        if ($aiResult !== null) {
            // Combine pattern and AI results for better accuracy
            return $this->combineResults($patternResult, $aiResult);
        }

        // Fall back to pattern-based result
        return $patternResult;
    }

    /**
     * Pattern-based story detection.
     *
     * @param string $content The message content
     * @return array Detection result
     */
    protected function patternBasedDetection(string $content): array
    {
        $score = 0;
        $indicators = [];

        // Check message length (stories tend to be longer)
        $wordCount = str_word_count($content);
        if ($wordCount > 50) {
            $score += 0.2;
            $indicators[] = "Long message ({$wordCount} words)";
        }

        // Check for past tense verbs (common in stories)
        $pastTensePatterns = [
            '/\b(was|were|had|did|went|came|saw|said|told|asked|thought|felt|knew|got|made|took|gave|found|left|brought|became|began|happened|seemed|appeared)\b/i',
        ];

        foreach ($pastTensePatterns as $pattern) {
            if (preg_match_all($pattern, $content, $matches)) {
                $count = count($matches[0]);
                $score += min(0.3, $count * 0.03);
                $indicators[] = "Past tense verbs ({$count} occurrences)";
                break;
            }
        }

        // Check for temporal markers
        $temporalMarkers = [
            'yesterday', 'last week', 'last month', 'last year', 'ago',
            'once', 'one day', 'one time', 'back when', 'remember when',
            'the other day', 'earlier', 'previously', 'before',
        ];

        foreach ($temporalMarkers as $marker) {
            if (stripos($content, $marker) !== false) {
                $score += 0.15;
                $indicators[] = "Temporal marker: '{$marker}'";
            }
        }

        // Check for storytelling phrases
        $storyPhrases = [
            'let me tell you', 'I remember', 'there was this time',
            'it happened', 'story time', 'so basically', 'long story short',
            'to make a long story short', 'believe it or not', 'you won\'t believe',
            'funny story', 'I\'ll never forget', 'I was like', 'he was like', 'she was like',
        ];

        foreach ($storyPhrases as $phrase) {
            if (stripos($content, $phrase) !== false) {
                $score += 0.2;
                $indicators[] = "Story phrase: '{$phrase}'";
            }
        }

        // Check for narrative structure (sequence of events)
        $sequenceMarkers = [
            'then', 'after', 'next', 'finally', 'eventually', 'suddenly',
            'first', 'second', 'later', 'meanwhile', 'afterwards',
        ];

        $sequenceCount = 0;
        foreach ($sequenceMarkers as $marker) {
            if (stripos($content, $marker) !== false) {
                $sequenceCount++;
            }
        }

        if ($sequenceCount >= 2) {
            $score += 0.2;
            $indicators[] = "Narrative sequence markers ({$sequenceCount} found)";
        }

        // Check for dialogue (quotes)
        if (preg_match_all('/["\'"].*?["\'"]/', $content, $matches)) {
            $quoteCount = count($matches[0]);
            $score += min(0.15, $quoteCount * 0.05);
            $indicators[] = "Dialogue/quotes ({$quoteCount} found)";
        }

        // Check for emotional language
        $emotionalWords = [
            'amazing', 'incredible', 'unbelievable', 'crazy', 'weird', 'funny',
            'embarrassing', 'awkward', 'scared', 'excited', 'surprised', 'shocked',
        ];

        foreach ($emotionalWords as $word) {
            if (stripos($content, $word) !== false) {
                $score += 0.1;
                $indicators[] = "Emotional language: '{$word}'";
                break;
            }
        }

        // Normalize score to 0-1 range
        $confidence = min(1.0, $score);

        $isStory = $confidence >= 0.5;

        return [
            'is_story' => $isStory,
            'confidence' => round($confidence, 2),
            'reasoning' => 'Pattern-based detection: ' . implode('; ', $indicators),
            'method' => 'pattern',
        ];
    }

    /**
     * AI-based story detection using Azure OpenAI or Claude.
     *
     * @param string $content The message content
     * @param array $context Optional context
     * @return array|null Detection result or null if AI is not configured
     */
    protected function aiBasedDetection(string $content, array $context = []): ?array
    {
        // Try Azure OpenAI first
        if (config('services.azure_openai.enabled')) {
            return $this->azureOpenAIDetection($content, $context);
        }

        // Try Claude API
        if (config('services.claude.enabled')) {
            return $this->claudeDetection($content, $context);
        }

        return null;
    }

    /**
     * Detect story using Azure OpenAI.
     *
     * @param string $content The message content
     * @param array $context Optional context
     * @return array|null Detection result
     */
    protected function azureOpenAIDetection(string $content, array $context = []): ?array
    {
        try {
            $endpoint = config('services.azure_openai.endpoint');
            $apiKey = config('services.azure_openai.api_key');
            $deploymentName = config('services.azure_openai.deployment_name');

            if (empty($endpoint) || empty($apiKey) || empty($deploymentName)) {
                return null;
            }

            $prompt = $this->buildStoryDetectionPrompt($content, $context);

            $response = Http::withHeaders([
                'api-key' => $apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(30)->post("{$endpoint}/openai/deployments/{$deploymentName}/chat/completions?api-version=2024-02-15-preview", [
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are an expert at analyzing text messages to determine if they contain stories or narratives. Respond with a JSON object containing: is_story (boolean), confidence (0-1), and reasoning (string).',
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt,
                    ],
                ],
                'temperature' => 0.3,
                'max_tokens' => 200,
            ]);

            if ($response->successful()) {
                $result = $response->json();
                $aiResponse = $result['choices'][0]['message']['content'] ?? null;

                if ($aiResponse) {
                    return $this->parseAIResponse($aiResponse, 'azure_openai');
                }
            }
        } catch (\Exception $e) {
            Log::error('Azure OpenAI story detection failed: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Detect story using Claude API.
     *
     * @param string $content The message content
     * @param array $context Optional context
     * @return array|null Detection result
     */
    protected function claudeDetection(string $content, array $context = []): ?array
    {
        try {
            $apiKey = config('services.claude.api_key');
            $model = config('services.claude.model', 'claude-3-haiku-20240307');

            if (empty($apiKey)) {
                return null;
            }

            $prompt = $this->buildStoryDetectionPrompt($content, $context);

            $response = Http::withHeaders([
                'x-api-key' => $apiKey,
                'anthropic-version' => '2023-06-01',
                'Content-Type' => 'application/json',
            ])->timeout(30)->post('https://api.anthropic.com/v1/messages', [
                'model' => $model,
                'max_tokens' => 200,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => 'You are an expert at analyzing text messages to determine if they contain stories or narratives. Respond with a JSON object containing: is_story (boolean), confidence (0-1), and reasoning (string).' . "\n\n" . $prompt,
                    ],
                ],
            ]);

            if ($response->successful()) {
                $result = $response->json();
                $aiResponse = $result['content'][0]['text'] ?? null;

                if ($aiResponse) {
                    return $this->parseAIResponse($aiResponse, 'claude');
                }
            }
        } catch (\Exception $e) {
            Log::error('Claude story detection failed: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Build the prompt for AI story detection.
     *
     * @param string $content The message content
     * @param array $context Optional context
     * @return string The prompt
     */
    protected function buildStoryDetectionPrompt(string $content, array $context = []): string
    {
        $prompt = "Analyze the following message and determine if it contains a story or narrative:\n\n";
        $prompt .= "Message: {$content}\n\n";

        if (!empty($context['previous_messages'])) {
            $prompt .= "Context (previous messages):\n";
            foreach ($context['previous_messages'] as $msg) {
                $prompt .= "- {$msg}\n";
            }
            $prompt .= "\n";
        }

        $prompt .= "Consider the following criteria:\n";
        $prompt .= "- Does it describe past events or experiences?\n";
        $prompt .= "- Does it have a narrative structure (beginning, middle, end)?\n";
        $prompt .= "- Does it include specific details, characters, or dialogue?\n";
        $prompt .= "- Is it more than just a simple statement or question?\n\n";
        $prompt .= "Respond with a JSON object only, no additional text.";

        return $prompt;
    }

    /**
     * Parse AI response.
     *
     * @param string $response The AI response
     * @param string $method The AI method used
     * @return array Parsed detection result
     */
    protected function parseAIResponse(string $response, string $method): array
    {
        // Try to extract JSON from response
        if (preg_match('/\{[^}]+\}/', $response, $matches)) {
            $json = json_decode($matches[0], true);

            if ($json && isset($json['is_story']) && isset($json['confidence'])) {
                return [
                    'is_story' => (bool) $json['is_story'],
                    'confidence' => (float) $json['confidence'],
                    'reasoning' => $json['reasoning'] ?? "AI-based detection ({$method})",
                    'method' => $method,
                ];
            }
        }

        // Fall back to simple parsing
        $isStory = stripos($response, 'true') !== false || stripos($response, 'is a story') !== false;

        return [
            'is_story' => $isStory,
            'confidence' => $isStory ? 0.7 : 0.3,
            'reasoning' => "AI-based detection ({$method}): " . substr($response, 0, 200),
            'method' => $method,
        ];
    }

    /**
     * Combine pattern and AI results.
     *
     * @param array $patternResult Pattern-based result
     * @param array $aiResult AI-based result
     * @return array Combined result
     */
    protected function combineResults(array $patternResult, array $aiResult): array
    {
        // Weighted average (AI gets more weight)
        $combinedConfidence = ($patternResult['confidence'] * 0.3) + ($aiResult['confidence'] * 0.7);

        $isStory = $combinedConfidence >= 0.5;

        return [
            'is_story' => $isStory,
            'confidence' => round($combinedConfidence, 2),
            'reasoning' => "Combined: Pattern ({$patternResult['confidence']}) + AI {$aiResult['method']} ({$aiResult['confidence']}). " . $aiResult['reasoning'],
            'method' => 'combined',
        ];
    }
}
