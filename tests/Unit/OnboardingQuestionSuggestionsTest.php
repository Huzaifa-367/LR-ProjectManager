<?php

namespace Tests\Unit;

use App\Support\OnboardingQuestionSuggestions;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OnboardingQuestionSuggestionsTest extends TestCase
{
    #[Test]
    public function it_provides_profile_specific_work_item_suggestions(): void
    {
        $training = OnboardingQuestionSuggestions::for('work_items', 'training');
        $software = OnboardingQuestionSuggestions::for('work_items', 'software');

        $this->assertNotEmpty($training);
        $this->assertNotEmpty($software);
        $this->assertNotSame($training[0]['label'], $software[0]['label']);
    }

    #[Test]
    public function it_marks_list_fields_correctly(): void
    {
        $this->assertSame('list', OnboardingQuestionSuggestions::inputModeFor('work_items'));
        $this->assertSame('single', OnboardingQuestionSuggestions::inputModeFor('objective'));
    }
}
