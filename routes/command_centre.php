<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\CommandCentre\AiOnboardingController;
use App\Http\Controllers\CommandCentre\AiSessionController;
use App\Http\Controllers\CommandCentre\AttachmentController;
use App\Http\Controllers\CommandCentre\CommandCentreController;
use App\Http\Controllers\CommandCentre\MemberDailyFocusController;
use App\Http\Controllers\CommandCentre\MemberNoteController;
use App\Http\Controllers\CommandCentre\ProjectController;
use App\Http\Controllers\CommandCentre\ProjectMemberController;
use App\Http\Controllers\CommandCentre\ProjectOnboardingController;
use App\Http\Controllers\CommandCentre\ProjectRoleController;
use App\Http\Controllers\CommandCentre\TaskCommentController;
use App\Http\Controllers\CommandCentre\TaskController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\GmailOAuthController;
use App\Http\Controllers\InvitationAcceptController;
use App\Http\Controllers\MemberMailLinkageController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\NotificationPreferenceController;
use App\Http\Controllers\OrganizationContextController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\OrganizationInvitationController;
use App\Http\Controllers\OrganizationMailProfileController;
use App\Http\Controllers\OrganizationMemberController;
use App\Http\Controllers\OrganizationReportsController;
use App\Http\Controllers\OrganizationRoleController;
use App\Http\Controllers\ProjectContextController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::get('organizations', [OrganizationController::class, 'index'])
        ->name('organizations.index');
    Route::get('organizations/create', [OrganizationController::class, 'create'])
        ->name('organizations.create');
    Route::post('organizations', [OrganizationController::class, 'store'])
        ->name('organizations.store');
    Route::post('organizations/select', [OrganizationContextController::class, 'update'])
        ->name('organizations.select');

    Route::prefix('organizations/{organization}')
        ->middleware(['org.access', 'org.member'])
        ->group(function (): void {
            Route::get('/', [OrganizationController::class, 'show'])
                ->middleware('org.permission:org.organizations.show')
                ->name('organizations.show');
            Route::patch('/', [OrganizationController::class, 'update'])
                ->middleware('org.permission:org.organizations.update')
                ->name('organizations.update');

            Route::get('members', [OrganizationMemberController::class, 'index'])
                ->middleware('org.permission:org.members.index')
                ->name('organizations.members.index');
            Route::post('members', [OrganizationMemberController::class, 'store'])
                ->middleware('org.permission:org.members.store')
                ->name('organizations.members.store');
            Route::get('members/{organizationMember}', [OrganizationMemberController::class, 'show'])
                ->middleware('org.permission:org.members.show')
                ->name('organizations.members.show');
            Route::patch('members/{organizationMember}', [OrganizationMemberController::class, 'update'])
                ->middleware('org.permission:org.members.update')
                ->name('organizations.members.update');
            Route::patch('members/{organizationMember}/disable', [OrganizationMemberController::class, 'disable'])
                ->middleware('org.permission:org.members.disable')
                ->name('organizations.members.disable');

            Route::post('invitations', [OrganizationInvitationController::class, 'store'])
                ->middleware('org.permission:org.invitations.store')
                ->name('organizations.invitations.store');
            Route::delete('invitations/{organizationInvitation}', [OrganizationInvitationController::class, 'destroy'])
                ->middleware('org.permission:org.invitations.destroy')
                ->name('organizations.invitations.destroy');
            Route::post('invitations/{organizationInvitation}/resend', [OrganizationInvitationController::class, 'resend'])
                ->middleware('org.permission:org.invitations.resend')
                ->name('organizations.invitations.resend');

            Route::get('activity-logs', [ActivityLogController::class, 'index'])
                ->middleware('org.permission:org.activity-logs.index')
                ->name('organizations.activity-logs.index');

            Route::get('reports', [OrganizationReportsController::class, 'index'])
                ->middleware('org.permission:org.notification-deliveries.index')
                ->name('organizations.reports.index');

            Route::get('mail-profiles', [OrganizationMailProfileController::class, 'index'])
                ->name('organizations.mail-profiles.index');
            Route::post('mail-profiles', [OrganizationMailProfileController::class, 'store'])
                ->middleware('org.permission:org.mail-profiles.store')
                ->name('organizations.mail-profiles.store');
            Route::patch('mail-profiles/{organizationMailProfile}', [OrganizationMailProfileController::class, 'update'])
                ->middleware('org.permission:org.mail-profiles.update')
                ->name('organizations.mail-profiles.update');
            Route::delete('mail-profiles/{organizationMailProfile}', [OrganizationMailProfileController::class, 'destroy'])
                ->middleware('org.permission:org.mail-profiles.destroy')
                ->name('organizations.mail-profiles.destroy');
            Route::post('mail-profiles/{organizationMailProfile}/test', [OrganizationMailProfileController::class, 'test'])
                ->middleware('org.permission:org.mail-profiles.test')
                ->name('organizations.mail-profiles.test');
            Route::get('mail-profiles/oauth/callback', [GmailOAuthController::class, 'callback'])
                ->middleware('org.permission:org.mail-profiles.oauth.callback')
                ->name('organizations.mail-profiles.oauth.callback');

            Route::get('notifications', [NotificationController::class, 'index'])
                ->middleware('org.permission:org.notifications.index')
                ->name('organizations.notifications.index');
            Route::patch('notifications/mark-read', [NotificationController::class, 'markRead'])
                ->middleware('org.permission:org.notifications.mark-read')
                ->name('organizations.notifications.mark-read');
            Route::get('notification-preferences', [NotificationPreferenceController::class, 'show'])
                ->middleware('org.permission:org.notification-preferences.show')
                ->name('organizations.notification-preferences.show');
            Route::patch('notification-preferences', [NotificationPreferenceController::class, 'update'])
                ->middleware('org.permission:org.notification-preferences.update')
                ->name('organizations.notification-preferences.update');
            Route::post('select-project', [ProjectContextController::class, 'update'])
                ->name('organizations.select-project');
            Route::patch('member-mail-linkage', [MemberMailLinkageController::class, 'update'])
                ->middleware('org.permission:org.member-mail-linkage.update')
                ->name('organizations.member-mail-linkage.update');
            Route::post('member-mail-linkage/test', [MemberMailLinkageController::class, 'test'])
                ->middleware('org.permission:org.member-mail-linkage.test')
                ->name('organizations.member-mail-linkage.test');

            Route::post('exports', [ExportController::class, 'store'])
                ->middleware('org.permission:org.exports.store')
                ->name('organizations.exports.store');
            Route::get('exports/{exportJob}', [ExportController::class, 'show'])
                ->middleware('org.permission:org.exports.show')
                ->name('organizations.exports.show');

            Route::get('roles', [OrganizationRoleController::class, 'index'])
                ->middleware('org.permission:org.roles.index')
                ->name('organizations.roles.index');
            Route::post('roles', [OrganizationRoleController::class, 'store'])
                ->middleware('org.permission:org.roles.store')
                ->name('organizations.roles.store');
            Route::get('roles/{organizationRole}', [OrganizationRoleController::class, 'show'])
                ->middleware('org.permission:org.roles.show')
                ->name('organizations.roles.show');
            Route::patch('roles/{organizationRole}', [OrganizationRoleController::class, 'update'])
                ->middleware('org.permission:org.roles.update')
                ->name('organizations.roles.update');
            Route::delete('roles/{organizationRole}', [OrganizationRoleController::class, 'destroy'])
                ->middleware('org.permission:org.roles.destroy')
                ->name('organizations.roles.destroy');
            Route::put('roles/{organizationRole}/permissions', [OrganizationRoleController::class, 'syncPermissions'])
                ->middleware('org.permission:org.roles.permissions.sync')
                ->name('organizations.roles.permissions.sync');

            Route::get('command-centre', [CommandCentreController::class, 'index'])
                ->middleware('org.permission:org.command-centre.index')
                ->name('organizations.command-centre.index');

            Route::post('focus', [MemberDailyFocusController::class, 'store'])
                ->middleware('org.permission:org.focus.store')
                ->name('organizations.focus.store');
            Route::post('focus/reorder', [MemberDailyFocusController::class, 'reorder'])
                ->middleware('org.permission:org.focus.reorder')
                ->name('organizations.focus.reorder');
            Route::delete('focus/{memberDailyFocus}', [MemberDailyFocusController::class, 'destroy'])
                ->middleware('org.permission:org.focus.destroy')
                ->name('organizations.focus.destroy');

            Route::post('notes', [MemberNoteController::class, 'store'])
                ->middleware('org.permission:org.notes.store')
                ->name('organizations.notes.store');
            Route::patch('notes/{memberNote}', [MemberNoteController::class, 'update'])
                ->middleware('org.permission:org.notes.update')
                ->name('organizations.notes.update');
            Route::delete('notes/{memberNote}', [MemberNoteController::class, 'destroy'])
                ->middleware('org.permission:org.notes.destroy')
                ->name('organizations.notes.destroy');

            Route::get('projects', [ProjectController::class, 'index'])
                ->middleware('org.permission:org.projects.index')
                ->name('organizations.projects.index');
            Route::post('projects', [ProjectController::class, 'store'])
                ->middleware('org.permission:org.projects.store')
                ->name('organizations.projects.store');

            Route::middleware('org.ai')->group(function (): void {
                Route::get('projects/onboarding', [ProjectOnboardingController::class, 'create'])
                    ->middleware('org.permission:org.ai-onboarding.start')
                    ->name('organizations.projects.onboarding');
                Route::post('projects/onboarding/reset', [ProjectOnboardingController::class, 'reset'])
                    ->middleware('org.permission:org.ai-onboarding.start')
                    ->name('organizations.projects.onboarding.reset');
            });

            Route::get('projects/{project}', [ProjectController::class, 'show'])
                ->middleware(['project.access', 'org.permission:org.projects.show'])
                ->name('organizations.projects.show');
            Route::patch('projects/{project}', [ProjectController::class, 'update'])
                ->middleware(['project.access', 'org.permission:org.projects.update'])
                ->name('organizations.projects.update');
            Route::delete('projects/{project}', [ProjectController::class, 'archive'])
                ->middleware(['project.access', 'org.permission:org.projects.archive'])
                ->name('organizations.projects.archive');

            Route::get('tasks', [TaskController::class, 'index'])
                ->middleware('org.permission:org.tasks.index')
                ->name('organizations.tasks.index');
            Route::post('tasks', [TaskController::class, 'store'])
                ->middleware('org.permission:org.tasks.store')
                ->name('organizations.tasks.store');
            Route::get('tasks/{task}', [TaskController::class, 'show'])
                ->middleware('org.permission:org.tasks.show')
                ->name('organizations.tasks.show');
            Route::patch('tasks/{task}', [TaskController::class, 'update'])
                ->middleware('org.permission:org.tasks.update')
                ->name('organizations.tasks.update');
            Route::delete('tasks/{task}', [TaskController::class, 'destroy'])
                ->middleware('org.permission:org.tasks.destroy')
                ->name('organizations.tasks.destroy');
            Route::patch('tasks/{task}/status', [TaskController::class, 'updateStatus'])
                ->middleware('org.permission:org.tasks.status.update')
                ->name('organizations.tasks.status.update');
            Route::put('tasks/{task}/assignees', [TaskController::class, 'syncAssignees'])
                ->middleware('org.permission:org.tasks.assignees.sync')
                ->name('organizations.tasks.assignees.sync');
            Route::patch('tasks/{task}/toggle-done', [TaskController::class, 'toggleDone'])
                ->middleware('org.permission:org.tasks.toggle-done')
                ->name('organizations.tasks.toggle-done');

            Route::post('tasks/{task}/comments', [TaskCommentController::class, 'store'])
                ->middleware('org.permission:org.task-comments.store')
                ->name('organizations.tasks.comments.store');
            Route::patch('tasks/{task}/comments/{taskComment}', [TaskCommentController::class, 'update'])
                ->middleware('org.permission:org.task-comments.update')
                ->name('organizations.tasks.comments.update');
            Route::delete('tasks/{task}/comments/{taskComment}', [TaskCommentController::class, 'destroy'])
                ->middleware('org.permission:org.task-comments.destroy')
                ->name('organizations.tasks.comments.destroy');

            Route::post('attachments', [AttachmentController::class, 'store'])
                ->middleware('org.permission:org.attachments.store')
                ->name('organizations.attachments.store');
            Route::delete('attachments/{attachment}', [AttachmentController::class, 'destroy'])
                ->middleware('org.permission:org.attachments.destroy')
                ->name('organizations.attachments.destroy');

            Route::middleware('org.ai')->group(function (): void {
                Route::get('ai-sessions', [AiSessionController::class, 'index'])
                    ->middleware('org.permission:org.ai-sessions.index')
                    ->name('organizations.ai-sessions.index');
                Route::post('ai-sessions', [AiSessionController::class, 'store'])
                    ->middleware('org.permission:org.ai-sessions.store')
                    ->name('organizations.ai-sessions.store');
                Route::get('ai-sessions/{aiSession}', [AiSessionController::class, 'show'])
                    ->middleware('org.permission:org.ai-sessions.show')
                    ->name('organizations.ai-sessions.show');

                Route::post('ai-onboarding/propose', [AiOnboardingController::class, 'propose'])
                    ->middleware('org.permission:org.ai-onboarding.propose')
                    ->name('organizations.ai-onboarding.propose');
                Route::get('ai-onboarding/{aiOnboardingProposal}', [AiOnboardingController::class, 'show'])
                    ->middleware('org.permission:org.ai-onboarding.show')
                    ->name('organizations.ai-onboarding.show');
                Route::patch('ai-onboarding/{aiOnboardingProposal}', [AiOnboardingController::class, 'update'])
                    ->middleware('org.permission:org.ai-onboarding.update')
                    ->name('organizations.ai-onboarding.update');
                Route::patch('ai-onboarding/{aiOnboardingProposal}/approve', [AiOnboardingController::class, 'approve'])
                    ->middleware('org.permission:org.ai-onboarding.approve')
                    ->name('organizations.ai-onboarding.approve');
                Route::patch('ai-onboarding/{aiOnboardingProposal}/reject', [AiOnboardingController::class, 'reject'])
                    ->middleware('org.permission:org.ai-onboarding.reject')
                    ->name('organizations.ai-onboarding.reject');
                Route::post('ai-onboarding/{aiOnboardingProposal}/apply', [AiOnboardingController::class, 'apply'])
                    ->middleware('org.permission:org.ai-onboarding.apply')
                    ->name('organizations.ai-onboarding.apply');
            });

            Route::prefix('projects/{project}')
                ->middleware('project.access')
                ->group(function (): void {
                    Route::get('members', [ProjectMemberController::class, 'index'])
                        ->middleware('project.permission:project.members.index')
                        ->name('projects.members.index');
                    Route::post('members', [ProjectMemberController::class, 'store'])
                        ->middleware('project.permission:project.members.store')
                        ->name('projects.members.store');
                    Route::patch('members/{projectMember}', [ProjectMemberController::class, 'update'])
                        ->middleware('project.permission:project.members.update')
                        ->name('projects.members.update');
                    Route::delete('members/{projectMember}', [ProjectMemberController::class, 'destroy'])
                        ->middleware('project.permission:project.members.destroy')
                        ->name('projects.members.destroy');

                    Route::get('roles', [ProjectRoleController::class, 'index'])
                        ->middleware('project.permission:project.roles.index')
                        ->name('projects.roles.index');
                    Route::post('roles', [ProjectRoleController::class, 'store'])
                        ->middleware('project.permission:project.roles.store')
                        ->name('projects.roles.store');
                    Route::get('roles/{projectRole}', [ProjectRoleController::class, 'show'])
                        ->middleware('project.permission:project.roles.show')
                        ->name('projects.roles.show');
                    Route::patch('roles/{projectRole}', [ProjectRoleController::class, 'update'])
                        ->middleware('project.permission:project.roles.update')
                        ->name('projects.roles.update');
                    Route::delete('roles/{projectRole}', [ProjectRoleController::class, 'destroy'])
                        ->middleware('project.permission:project.roles.destroy')
                        ->name('projects.roles.destroy');
                    Route::put('roles/{projectRole}/permissions', [ProjectRoleController::class, 'syncPermissions'])
                        ->middleware('project.permission:project.roles.permissions.sync')
                        ->name('projects.roles.permissions.sync');
                });
        });
});

Route::get('invitations/{token}', [InvitationAcceptController::class, 'show'])
    ->name('invitations.show');
Route::post('invitations/{token}/accept', [InvitationAcceptController::class, 'accept'])
    ->middleware(['auth', 'verified'])
    ->name('invitations.accept');
Route::post('invitations/pending/{organizationInvitation}/accept', [InvitationAcceptController::class, 'acceptPending'])
    ->middleware(['auth', 'verified'])
    ->name('invitations.accept-pending');

Route::middleware(['auth', 'verified'])->get('/', function () {
    return redirect()->route('organizations.index');
})->name('home');
