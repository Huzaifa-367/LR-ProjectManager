<?php

namespace Tests\Unit;

use App\Ai\Agents\ProjectOnboardingPlanAgent;
use Illuminate\JsonSchema\JsonSchemaTypeFactory;
use Illuminate\Support\Carbon;
use Laravel\Ai\ObjectSchema;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProjectOnboardingPlanAgentTest extends TestCase
{
    #[Test]
    public function it_marks_every_object_property_as_required_for_strict_output(): void
    {
        $schema = (new ProjectOnboardingPlanAgent('training'))->schema(new JsonSchemaTypeFactory);
        $serialized = (new ObjectSchema($schema))->toSchema();

        $this->assertObjectPropertiesMatchRequired($serialized);

        $taskItems = $serialized['properties']['tasks']['items'] ?? null;
        $this->assertIsArray($taskItems);
        $this->assertObjectPropertiesMatchRequired($taskItems);
        $this->assertContains('deadline_date', $taskItems['required']);
    }

    #[Test]
    public function it_includes_the_reference_date_in_instructions(): void
    {
        $referenceDate = Carbon::parse('2026-05-31 09:00:00', config('app.timezone'));

        $instructions = (string) (new ProjectOnboardingPlanAgent('training', $referenceDate))->instructions();

        $this->assertStringContainsString('Reference date (today): 2026-05-31', $instructions);
        $this->assertStringContainsString($referenceDate->timezoneName, $instructions);
    }

    /**
     * @param  array<string, mixed>  $objectSchema
     */
    private function assertObjectPropertiesMatchRequired(array $objectSchema): void
    {
        $propertyKeys = array_keys($objectSchema['properties'] ?? []);
        $requiredKeys = $objectSchema['required'] ?? [];

        sort($propertyKeys);
        sort($requiredKeys);

        $this->assertSame(
            $propertyKeys,
            $requiredKeys,
            'Strict JSON schema requires every property key to appear in required.',
        );
    }
}
