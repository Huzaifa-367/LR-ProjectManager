<?php

namespace Tests\Feature\CommandCentre;

use App\Models\MemberNote;
use App\Models\OrganizationMember;
use App\Models\User;
use App\Support\OrganizationBootstrapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberNotesTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_can_create_personal_note(): void
    {
        $user = User::factory()->create();
        $organization = app(OrganizationBootstrapService::class)->create($user, [
            'name' => 'Notes Org',
        ]);

        $this->actingAs($user)
            ->post(route('organizations.notes.store', $organization), [
                'body' => 'Review Q3 pipeline with Nawal.',
            ])
            ->assertRedirect();

        $member = $organization->members()->where('user_id', $user->id)->firstOrFail();

        $this->assertDatabaseHas('member_notes', [
            'organization_member_id' => $member->id,
            'body' => 'Review Q3 pipeline with Nawal.',
        ]);
    }

    public function test_member_cannot_update_another_members_note(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();

        $organization = app(OrganizationBootstrapService::class)->create($owner, [
            'name' => 'Private Notes Org',
        ]);

        $otherMember = OrganizationMember::query()->create([
            'organization_id' => $organization->id,
            'user_id' => $other->id,
            'organization_role_id' => $organization->roles()->where('slug', 'member')->firstOrFail()->id,
            'display_name' => $other->name,
            'email' => $other->email,
            'status' => 'active',
            'joined_at' => now(),
        ]);

        $note = MemberNote::query()->create([
            'organization_member_id' => $otherMember->id,
            'body' => 'Private note',
            'sort_order' => 1,
        ]);

        $this->actingAs($owner)
            ->patch(route('organizations.notes.update', [$organization, $note]), [
                'body' => 'Hacked',
            ])
            ->assertForbidden();
    }
}
