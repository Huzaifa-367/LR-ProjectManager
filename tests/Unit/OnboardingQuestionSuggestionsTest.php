<?php

namespace Tests\Unit;

use App\Support\OnboardingRequirementRegistry;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OnboardingQuestionSuggestionsTest extends TestCase
{
    #[Test]
    public function it_returns_profile_specific_suggestions(): void
    {
        $training = OnboardingRequirementRegistry::suggestionsFor('work_items', 'training');
        $software = OnboardingRequirementRegistry::suggestionsFor('work_items', 'software');

        $this->assertNotEmpty($training);
        $this->assertNotEmpty($software);
        $this->assertNotSame($training[0]['value'], $software[0]['value']);
    }

    #[Test]
    public function it_resolves_input_modes_for_question_keys(): void
    {
        $this->assertSame('list', OnboardingRequirementRegistry::inputModeFor('work_items'));
        $this->assertSame('single', OnboardingRequirementRegistry::inputModeFor('objective'));
    }
}
