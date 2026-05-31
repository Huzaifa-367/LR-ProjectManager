<?php

namespace App\Http\Controllers\CommandCentre;

use App\Enums\AiSessionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\CommandCentre\StoreAiSessionRequest;
use App\Models\AiSession;
use App\Models\Organization;
use App\Support\CommandCentreResourcePresenter;
use App\Support\EffectivePermissionService;
use App\Support\OrganizationMemberResolver;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AiSessionController extends Controller
{
    public function index(
        Organization $organization,
        OrganizationMemberResolver $memberResolver,
        EffectivePermissionService $permissions,
    ): Response {
        $member = $memberResolver->requireForOrganization(request()->user(), $organization);

        abort_unless($permissions->memberCan($member, 'org.ai-sessions.index'), 403);

        $sessions = AiSession::query()
            ->where('organization_id', $organization->id)
            ->where('organization_member_id', $member->id)
            ->orderByDesc('last_message_at')
            ->orderByDesc('id')
            ->limit(20)
            ->get()
            ->map(fn (AiSession $session): array => CommandCentreResourcePresenter::aiSession($session))
            ->values()
            ->all();

        return Inertia::render('organizations/ai/sessions/index', [
            'organization' => [
                'id' => $organization->id,
                'name' => $organization->name,
                'slug' => $organization->slug,
            ],
            'sessions' => $sessions,
            'permissions' => CommandCentreResourcePresenter::permissions($permissions, $member),
        ]);
    }

    public function store(
        StoreAiSessionRequest $request,
        Organization $organization,
        OrganizationMemberResolver $memberResolver,
        EffectivePermissionService $permissions,
    ): RedirectResponse {
        $member = $memberResolver->requireForOrganization($request->user(), $organization);

        abort_unless($permissions->memberCan($member, 'org.ai-sessions.store'), 403);

        $validated = $request->validated();

        $session = AiSession::query()->create([
            'organization_id' => $organization->id,
            'organization_member_id' => $member->id,
            'user_id' => $request->user()->id,
            'context' => $validated['context'],
            'title' => $validated['title'] ?? null,
            'status' => AiSessionStatus::Active,
        ]);

        return to_route('organizations.ai-sessions.show', [$organization, $session]);
    }

    public function show(
        Organization $organization,
        AiSession $aiSession,
        OrganizationMemberResolver $memberResolver,
        EffectivePermissionService $permissions,
    ): Response {
        $member = $memberResolver->requireForOrganization(request()->user(), $organization);

        abort_unless($aiSession->organization_id === $organization->id, 404);
        abort_unless($aiSession->organization_member_id === $member->id, 403);
        abort_unless($permissions->memberCan($member, 'org.ai-sessions.show'), 403);

        $aiSession->load(['messages' => fn ($query) => $query->orderBy('id')]);

        return Inertia::render('organizations/ai/sessions/show', [
            'organization' => [
                'id' => $organization->id,
                'name' => $organization->name,
                'slug' => $organization->slug,
            ],
            'session' => CommandCentreResourcePresenter::aiSession($aiSession),
            'messages' => $aiSession->messages->map(fn ($message): array => [
                'id' => $message->id,
                'role' => $message->role->value,
                'content' => $message->content,
                'onboarding_proposal_id' => $message->onboarding_proposal_id,
                'created_at' => $message->created_at?->toIso8601String(),
            ])->values()->all(),
            'permissions' => CommandCentreResourcePresenter::permissions($permissions, $member),
        ]);
    }
}
