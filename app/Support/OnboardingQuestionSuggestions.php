<?php

namespace App\Support;

final class OnboardingQuestionSuggestions
{
    /**
     * @return list<array{label: string, value: string, mode: string}>
     */
    public static function for(string $questionKey, string $profileKey = 'general'): array
    {
        return match ($questionKey) {
            'project_name' => self::projectNameSuggestions($profileKey),
            'objective' => self::objectiveSuggestions($profileKey),
            'work_items' => self::workItemSuggestions($profileKey),
            'timeline' => self::timelineSuggestions(),
            'success_criteria' => self::successCriteriaSuggestions($profileKey),
            'decisions' => self::decisionSuggestions($profileKey),
            'reminders' => self::reminderSuggestions(),
            'dynamic_audience' => self::audienceSuggestions($profileKey),
            'dynamic_delivery' => self::deliverySuggestions($profileKey),
            'dynamic_materials' => self::materialSuggestions(),
            'dynamic_outcomes' => self::outcomeSuggestions($profileKey),
            'dynamic_logistics' => self::logisticsSuggestions($profileKey),
            'dynamic_impact' => self::impactSuggestions(),
            'dynamic_scope_depth' => self::scopeDepthSuggestions(),
            'dynamic_constraints' => self::constraintSuggestions(),
            default => [],
        };
    }

    public static function inputModeFor(string $questionKey): string
    {
        return in_array($questionKey, [
            'work_items',
            'success_criteria',
            'decisions',
            'reminders',
            'dynamic_scope_depth',
            'dynamic_materials',
            'dynamic_impact',
            'dynamic_constraints',
            'dynamic_logistics',
        ], true) ? 'list' : 'single';
    }

    /**
     * @return list<array{label: string, value: string, mode: string}>
     */
    private static function projectNameSuggestions(string $profileKey): array
    {
        return match ($profileKey) {
            'software' => [
                self::replace(__('Customer portal'), __('Customer self-service portal')),
                self::replace(__('Mobile app release'), __('Mobile app v2 release')),
                self::replace(__('API integration'), __('Third-party API integration')),
            ],
            'training' => [
                self::replace(__('Leadership program'), __('Leadership fundamentals program')),
                self::replace(__('Compliance training'), __('Annual compliance training')),
                self::replace(__('Skills bootcamp'), __('Technical skills bootcamp')),
            ],
            'workshop' => [
                self::replace(__('Strategy workshop'), __('Quarterly strategy workshop')),
                self::replace(__('Design sprint'), __('Product design sprint')),
                self::replace(__('Team offsite'), __('Leadership team offsite')),
            ],
            'event' => [
                self::replace(__('Annual conference'), __('Annual user conference')),
                self::replace(__('Product launch'), __('Product launch event')),
                self::replace(__('Town hall'), __('Company town hall')),
            ],
            'operations' => [
                self::replace(__('Process improvement'), __('Core process improvement initiative')),
                self::replace(__('Policy rollout'), __('New policy rollout')),
                self::replace(__('Vendor transition'), __('Vendor transition program')),
            ],
            default => [
                self::replace(__('Strategic initiative'), __('Strategic initiative')),
                self::replace(__('Pilot program'), __('Pilot program')),
                self::replace(__('Improvement project'), __('Improvement project')),
            ],
        };
    }

    /**
     * @return list<array{label: string, value: string, mode: string}>
     */
    private static function objectiveSuggestions(string $profileKey): array
    {
        return match ($profileKey) {
            'software' => [
                self::replace(__('Launch MVP'), __('Deliver an MVP that replaces manual workflows and improves user adoption by the target date.')),
                self::replace(__('Reduce defects'), __('Ship a stable release that reduces critical defects and meets agreed acceptance criteria.')),
                self::replace(__('Integrate systems'), __('Integrate key systems so teams can share data reliably with minimal manual handoffs.')),
            ],
            'training' => [
                self::replace(__('Upskill team'), __('Equip the target audience with practical skills and measurable competency by the end of the program.')),
                self::replace(__('Certify learners'), __('Deliver training that prepares learners to pass certification and apply skills on the job.')),
                self::replace(__('Roll out curriculum'), __('Roll out a standardized curriculum to all required cohorts on schedule.')),
            ],
            'workshop' => [
                self::replace(__('Align stakeholders'), __('Facilitate a focused session that aligns stakeholders on priorities and owned next steps.')),
                self::replace(__('Co-create solution'), __('Run a workshop that produces a validated plan or prototype the team can execute.')),
                self::replace(__('Solve problem'), __('Bring the right participants together to decide on a clear path forward for the stated challenge.')),
            ],
            'event' => [
                self::replace(__('Engage audience'), __('Deliver an event that engages the target audience and achieves the planned program outcomes.')),
                self::replace(__('Launch offering'), __('Execute a launch event that generates awareness and supports the go-to-market plan.')),
                self::replace(__('Community gathering'), __('Host a successful gathering with smooth logistics and positive attendee experience.')),
            ],
            'operations' => [
                self::replace(__('Improve process'), __('Implement an improved process that reduces errors and cycle time for the affected teams.')),
                self::replace(__('Enable compliance'), __('Complete the rollout so the organization meets the required compliance or policy standards.')),
                self::replace(__('Reduce cost'), __('Deliver operational changes that reduce cost or effort without sacrificing quality.')),
            ],
            default => [
                self::replace(__('Deliver outcome'), __('Deliver the planned outcome for the target audience by the agreed timeline.')),
                self::replace(__('Improve performance'), __('Improve performance against the stated baseline with clear, measurable results.')),
                self::replace(__('Complete rollout'), __('Complete rollout with stakeholder sign-off and documented handover.')),
            ],
        };
    }

    /**
     * @return list<array{label: string, value: string, mode: string}>
     */
    private static function workItemSuggestions(string $profileKey): array
    {
        return match ($profileKey) {
            'software' => [
                self::append(__('Requirements & design'), __('Gather requirements and finalize solution design')),
                self::append(__('Core build'), __('Implement core features and integrations')),
                self::append(__('Testing & UAT'), __('Complete testing, UAT, and defect remediation')),
                self::append(__('Release & docs'), __('Deploy to production and publish user documentation')),
            ],
            'training' => [
                self::append(__('Curriculum design'), __('Design curriculum outline and learning objectives')),
                self::append(__('Materials'), __('Develop slides, exercises, and job aids')),
                self::append(__('Pilot session'), __('Run pilot session and incorporate feedback')),
                self::append(__('Rollout'), __('Schedule and deliver sessions to all cohorts')),
            ],
            'workshop' => [
                self::append(__('Pre-work'), __('Prepare pre-read materials and participant briefing')),
                self::append(__('Agenda design'), __('Design agenda and facilitation guide')),
                self::append(__('Facilitation'), __('Facilitate workshop sessions and capture outputs')),
                self::append(__('Follow-up'), __('Publish outcomes and assign follow-up actions')),
            ],
            'event' => [
                self::append(__('Venue & vendors'), __('Confirm venue, vendors, and contracts')),
                self::append(__('Program'), __('Finalize program, speakers, and run-of-show')),
                self::append(__('Registration'), __('Set up registration and attendee communications')),
                self::append(__('Event day'), __('Execute event day logistics and debrief')),
            ],
            'operations' => [
                self::append(__('Current state'), __('Document current process and pain points')),
                self::append(__('Future design'), __('Design future-state process and roles')),
                self::append(__('Pilot'), __('Pilot changes with one team or region')),
                self::append(__('Full rollout'), __('Roll out changes and train affected teams')),
            ],
            default => [
                self::append(__('Discovery'), __('Complete discovery and confirm scope')),
                self::append(__('Planning'), __('Create detailed plan and assign owners')),
                self::append(__('Execution'), __('Execute main deliverables')),
                self::append(__('Review'), __('Review outcomes and close out the project')),
            ],
        };
    }

    /**
     * @return list<array{label: string, value: string, mode: string}>
     */
    private static function timelineSuggestions(): array
    {
        return [
            self::replace(__('4 weeks'), __('4-week delivery with weekly milestones')),
            self::replace(__('6 weeks'), __('6-week phased rollout')),
            self::replace(__('This quarter'), __('Complete by end of current quarter')),
            self::replace(__('Next quarter'), __('Target launch next quarter')),
            self::replace(__('Pilot then scale'), __('Pilot in 4 weeks, full rollout within 3 months')),
            self::replace(__('Fixed date'), __('Must complete by a fixed event or deadline date')),
        ];
    }

    /**
     * @return list<array{label: string, value: string, mode: string}>
     */
    private static function successCriteriaSuggestions(string $profileKey): array
    {
        $common = [
            self::append(__('Stakeholder sign-off'), __('Executive or sponsor sign-off obtained')),
            self::append(__('On time'), __('Delivered on or before the target date')),
            self::append(__('Budget met'), __('Delivered within approved budget')),
        ];

        $specific = match ($profileKey) {
            'software' => [
                self::append(__('UAT passed'), __('UAT passed with no critical open defects')),
                self::append(__('Adoption target'), __('Adoption or usage target met post-launch')),
            ],
            'training' => [
                self::append(__('Completion rate'), __('Target completion rate achieved')),
                self::append(__('Assessment pass'), __('Assessment pass rate meets threshold')),
            ],
            'workshop', 'event' => [
                self::append(__('Participant satisfaction'), __('Participant satisfaction score meets target')),
                self::append(__('Outcomes captured'), __('Agreed outcomes documented and accepted')),
            ],
            default => [
                self::append(__('Quality bar'), __('Quality criteria met without rework')),
            ],
        };

        return array_merge($common, $specific);
    }

    /**
     * @return list<array{label: string, value: string, mode: string}>
     */
    private static function decisionSuggestions(string $profileKey): array
    {
        $common = [
            self::append(__('Budget approval'), __('Budget approval from finance or sponsor')),
            self::append(__('Scope trade-off'), __('Scope trade-off decision if timeline slips')),
            self::append(__('Go / no-go'), __('Go/no-go gate before major rollout')),
        ];

        $specific = match ($profileKey) {
            'software' => [self::append(__('Vendor / build'), __('Build vs buy or vendor selection'))],
            'training' => [self::append(__('Delivery mode'), __('In-house vs external delivery decision'))],
            'event' => [self::append(__('Venue choice'), __('Venue and date confirmation'))],
            default => [],
        };

        return array_merge($common, $specific);
    }

    /**
     * @return list<array{label: string, value: string, mode: string}>
     */
    private static function reminderSuggestions(): array
    {
        return [
            self::append(__('Weekly standup'), __('Weekly project standup')),
            self::append(__('Steering review'), __('Bi-weekly steering committee review')),
            self::append(__('Milestone check'), __('Milestone readiness check one week before due date')),
            self::append(__('Stakeholder update'), __('Monthly stakeholder status update')),
        ];
    }

    /**
     * @return list<array{label: string, value: string, mode: string}>
     */
    private static function audienceSuggestions(string $profileKey): array
    {
        return match ($profileKey) {
            'software' => [
                self::replace(__('End users'), __('Primary end users, system admins, and executive sponsor')),
                self::replace(__('Internal teams'), __('Internal operators, support team, and product owner')),
                self::replace(__('External clients'), __('External clients or partners who depend on the solution')),
            ],
            'training' => [
                self::replace(__('New hires'), __('New hires or early-career staff in the target role')),
                self::replace(__('Managers'), __('People managers and team leads')),
                self::replace(__('All staff'), __('All staff in a specific department or region')),
            ],
            'workshop' => [
                self::replace(__('Leadership'), __('Leadership team and key decision makers')),
                self::replace(__('Cross-functional'), __('Cross-functional contributors from affected teams')),
                self::replace(__('Subject experts'), __('Subject matter experts and facilitators')),
            ],
            'event' => [
                self::replace(__('Customers'), __('Customers, partners, and prospects')),
                self::replace(__('Employees'), __('Employees and leadership attendees')),
                self::replace(__('Industry guests'), __('Industry guests, speakers, and VIP stakeholders')),
            ],
            default => [
                self::replace(__('Primary users'), __('Primary beneficiaries and day-to-day owners')),
                self::replace(__('Sponsors'), __('Executive sponsor and approvers')),
                self::replace(__('Support teams'), __('Teams who support or maintain the outcome')),
            ],
        };
    }

    /**
     * @return list<array{label: string, value: string, mode: string}>
     */
    private static function deliverySuggestions(string $profileKey): array
    {
        return match ($profileKey) {
            'software' => [
                self::replace(__('Agile releases'), __('Agile iterations with staging then production release')),
                self::replace(__('Phased rollout'), __('Phased rollout by user group or region')),
                self::replace(__('Big bang'), __('Single release with full UAT before go-live')),
            ],
            'training' => [
                self::replace(__('In-person'), __('In-person instructor-led sessions')),
                self::replace(__('Virtual live'), __('Live virtual sessions via video conference')),
                self::replace(__('Self-paced'), __('Self-paced e-learning with optional live Q&A')),
                self::replace(__('Hybrid'), __('Hybrid blend of live sessions and self-paced modules')),
            ],
            default => [
                self::replace(__('Standard delivery'), __('Standard delivery approach for this organization')),
            ],
        };
    }

    /**
     * @return list<array{label: string, value: string, mode: string}>
     */
    private static function materialSuggestions(): array
    {
        return [
            self::append(__('Slides'), __('Slide deck and facilitator notes')),
            self::append(__('Exercises'), __('Hands-on exercises and worksheets')),
            self::append(__('Assessment'), __('Knowledge check or final assessment')),
            self::append(__('Job aids'), __('Quick-reference job aids for learners')),
        ];
    }

    /**
     * @return list<array{label: string, value: string, mode: string}>
     */
    private static function outcomeSuggestions(string $profileKey): array
    {
        return match ($profileKey) {
            'workshop' => [
                self::replace(__('Decisions made'), __('Clear decisions documented with owners and dates')),
                self::replace(__('Prioritized backlog'), __('Prioritized backlog or roadmap agreed by participants')),
                self::replace(__('Prototype / plan'), __('Validated prototype, wireframes, or action plan')),
            ],
            default => [
                self::replace(__('Measurable result'), __('Measurable result achieved against the stated goal')),
                self::replace(__('Deliverables accepted'), __('All deliverables accepted by the sponsor')),
                self::replace(__('Behavior change'), __('Target behavior or process change observed after delivery')),
            ],
        };
    }

    /**
     * @return list<array{label: string, value: string, mode: string}>
     */
    private static function logisticsSuggestions(string $profileKey): array
    {
        return match ($profileKey) {
            'workshop' => [
                self::replace(__('Half day remote'), __('Half-day remote session via video conference')),
                self::replace(__('Full day onsite'), __('Full-day onsite workshop with breakout rooms')),
                self::replace(__('Multi-day'), __('Multi-day session with pre-work and follow-up')),
            ],
            'event' => [
                self::append(__('Venue booked'), __('Venue booked and layout confirmed')),
                self::append(__('AV & catering'), __('AV, catering, and registration arranged')),
                self::append(__('Run-of-show'), __('Run-of-show timeline finalized')),
            ],
            default => [
                self::replace(__('Remote session'), __('Remote session with scheduled breaks')),
                self::replace(__('Onsite session'), __('Onsite session with room and materials prepared')),
            ],
        };
    }

    /**
     * @return list<array{label: string, value: string, mode: string}>
     */
    private static function impactSuggestions(): array
    {
        return [
            self::append(__('Operations team'), __('Operations team processes and handoffs')),
            self::append(__('Customer-facing'), __('Customer-facing teams and service levels')),
            self::append(__('Finance / HR'), __('Finance, HR, or compliance workflows')),
            self::append(__('IT systems'), __('IT systems and integration points')),
        ];
    }

    /**
     * @return list<array{label: string, value: string, mode: string}>
     */
    private static function scopeDepthSuggestions(): array
    {
        return [
            self::append(__('Auth & access'), __('User authentication and role-based access')),
            self::append(__('Core workflow'), __('Core workflow users need day one')),
            self::append(__('Reporting'), __('Essential reporting or dashboards')),
            self::append(__('Integrations'), __('Critical integrations with existing systems')),
        ];
    }

    /**
     * @return list<array{label: string, value: string, mode: string}>
     */
    private static function constraintSuggestions(): array
    {
        return [
            self::append(__('Budget cap'), __('Fixed budget cap with no additional spend approved')),
            self::append(__('Regulatory'), __('Regulatory or compliance requirements must be met')),
            self::append(__('Resource limits'), __('Limited availability of key subject matter experts')),
            self::append(__('Hard deadline'), __('Immovable external deadline or dependency')),
            self::append(__('Legacy systems'), __('Must work with existing legacy systems during transition')),
        ];
    }

    /**
     * @return array{label: string, value: string, mode: string}
     */
    private static function replace(string $label, string $value): array
    {
        return ['label' => $label, 'value' => $value, 'mode' => 'replace'];
    }

    /**
     * @return array{label: string, value: string, mode: string}
     */
    private static function append(string $label, string $value): array
    {
        return ['label' => $label, 'value' => $value, 'mode' => 'append'];
    }
}
