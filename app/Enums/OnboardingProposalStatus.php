<?php

namespace App\Enums;

enum OnboardingProposalStatus: string
{
    case Draft = 'draft';
    case PendingReview = 'pending_review';
    case Approved = 'approved';
    case Applied = 'applied';
    case Rejected = 'rejected';
    case Superseded = 'superseded';
}
