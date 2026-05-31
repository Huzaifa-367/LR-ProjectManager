<?php

namespace App\Support;

final class OnboardingProjectProfileDetector
{
    /**
     * @param  array<string, mixed>  $structure
     * @return array{key: string, label: string, summary: string}
     */
    public function detect(string $brief, array $structure): array
    {
        $haystack = strtolower($brief.' '.implode(' ', $structure['sections']['general'] ?? []));

        $profiles = [
            'software' => [
                'label' => __('Software / product delivery'),
                'summary' => __('Build, release, or integrate a technical solution.'),
                'patterns' => '/\b(software|application|app|platform|api|integration|deploy|release|sprint|uat|qa|backend|frontend|database|feature|bug|code|devops|migration)\b/i',
            ],
            'training' => [
                'label' => __('Training / learning program'),
                'summary' => __('Design and deliver structured learning outcomes.'),
                'patterns' => '/\b(training|course|curriculum|learners?|participants?|certification|module|lesson|cohort|lms|workbook|instructor)\b/i',
            ],
            'workshop' => [
                'label' => __('Workshop / facilitated session'),
                'summary' => __('Plan a focused working session with clear outputs.'),
                'patterns' => '/\b(workshop|facilitat|brainstorm|design\s+thinking|breakout|agenda|session\s+plan|offsite)\b/i',
            ],
            'event' => [
                'label' => __('Event / program launch'),
                'summary' => __('Coordinate people, logistics, and run-of-show.'),
                'patterns' => '/\b(event|conference|summit|launch\s+event|venue|speaker|registration|expo|gala)\b/i',
            ],
            'operations' => [
                'label' => __('Operations / business initiative'),
                'summary' => __('Improve processes, policies, or organizational outcomes.'),
                'patterns' => '/\b(process|policy|operating\s+model|rollout|change\s+management|vendor|procurement|compliance|audit)\b/i',
            ],
        ];

        foreach ($profiles as $key => $profile) {
            if (preg_match($profile['patterns'], $haystack) === 1) {
                return [
                    'key' => $key,
                    'label' => $profile['label'],
                    'summary' => $profile['summary'],
                ];
            }
        }

        return [
            'key' => 'general',
            'label' => __('General project'),
            'summary' => __('Plan work from the details you provide — any domain or format.'),
        ];
    }
}
