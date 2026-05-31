<?php

namespace App\Enums;

enum AiSessionContext: string
{
    case ProjectOnboarding = 'project_onboarding';
    case ProjectAssist = 'project_assist';
    case OrgOnboarding = 'org_onboarding';
}
