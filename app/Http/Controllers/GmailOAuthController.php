<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class GmailOAuthController extends Controller
{
    public function callback(
        Organization $organization,
        Request $request,
    ): RedirectResponse {
        abort_unless(
            $request->user() !== null,
            403,
        );

        Inertia::flash('toast', [
            'type' => 'error',
            'message' => __('Gmail OAuth is not configured yet. Use SMTP for now.'),
        ]);

        return redirect()->route('organizations.mail-profiles.index', $organization);
    }
}
