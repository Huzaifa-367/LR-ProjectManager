<?php

namespace App\Support;

final class OnboardingRequirementRegistry
{
    /**
     * @return list<array{
     *     key: string,
     *     label: string,
     *     required: bool,
     *     prompt: string,
     *     hint: string
     * }>
     */
    public static function definitions(): array
    {
        return [
            self::definition('project_name', true),
            self::definition('objective', true),
            self::definition('work_items', true),
            self::definition('timeline', true),
            self::definition('success_criteria', false),
            self::definition('decisions', false),
            self::definition('reminders', false),
        ];
    }

    public static function initialPastePlaceholder(): string
    {
        return <<<'TXT'
Paste everything you have — any project type is supported (software, training, workshop, event, operations, etc.).

Project: …
Objective: …
Scope / deliverables:
- …
- …
Timeline: …
Success criteria: …
Decisions needed: …
TXT;
    }

    public static function initialPasteGuide(): string
    {
        return __('Paste notes, emails, proposals, or bullet lists. The assistant detects the project type and only asks for gaps — it does not invent details.');
    }

    /**
     * @return list<array{label: string, detail: string}>
     */
    public static function wizardSteps(): array
    {
        return [
            ['label' => 'Details', 'detail' => 'Paste what you know about the project'],
            ['label' => 'Questions', 'detail' => 'Answer tailored follow-ups for missing context'],
            ['label' => 'Review', 'detail' => 'Edit the generated plan'],
            ['label' => 'Apply', 'detail' => 'Create the project and work items'],
        ];
    }

    public static function labelFor(string $key): string
    {
        if (str_starts_with($key, 'dynamic_')) {
            return self::dynamicLabelFor($key);
        }

        foreach (self::definitions() as $definition) {
            if ($definition['key'] === $key) {
                return $definition['label'];
            }
        }

        return ucfirst(str_replace('_', ' ', $key));
    }

    /**
     * @return list<array{label: string, value: string, mode: string}>
     */
    public static function suggestionsFor(string $questionKey, string $profileKey = 'general'): array
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
     * @return array{key: string, label: string, required: bool, prompt: string, hint: string}|null
     */
    public static function definitionFor(string $key, ?string $profileKey = null): ?array
    {
        foreach (self::definitions() as $definition) {
            if ($definition['key'] === $key) {
                return self::tailorDefinition($definition, $profileKey ?? 'general');
            }
        }

        return null;
    }

    /**
     * @return array{key: string, label: string, required: bool, prompt: string, hint: string}
     */
    private static function definition(string $key, bool $required): array
    {
        return match ($key) {
            'project_name' => [
                'key' => 'project_name',
                'label' => __('Project name'),
                'required' => $required,
                'prompt' => __('What should this project be called?'),
                'hint' => __('A short title for dashboards — e.g. “Customer portal v2”, “Leadership workshop”, “Q3 compliance training”.'),
            ],
            'objective' => [
                'key' => 'objective',
                'label' => __('Objective'),
                'required' => $required,
                'prompt' => __('What outcome should this project achieve?'),
                'hint' => __('The main result, who it serves, and by when — in one or two sentences.'),
            ],
            'work_items' => [
                'key' => 'work_items',
                'label' => __('Scope & deliverables'),
                'required' => $required,
                'prompt' => __('What are the main work items or deliverables?'),
                'hint' => __('List at least two concrete bullets. Each becomes a task — features, sessions, modules, milestones, or artifacts.'),
            ],
            'timeline' => [
                'key' => 'timeline',
                'label' => __('Timeline'),
                'required' => $required,
                'prompt' => __('What is the target timeline or key milestones?'),
                'hint' => __('Dates, phases, or relative timing — e.g. “pilot in 6 weeks”, “Q3 launch”, “two-day workshop in May”.'),
            ],
            'success_criteria' => [
                'key' => 'success_criteria',
                'label' => __('Success criteria'),
                'required' => $required,
                'prompt' => __('How will you know this project succeeded?'),
                'hint' => __('Metrics, acceptance tests, completion rates, feedback scores, or sign-off conditions.'),
            ],
            'decisions' => [
                'key' => 'decisions',
                'label' => __('Key decisions'),
                'required' => $required,
                'prompt' => __('Which approvals or decisions are still needed?'),
                'hint' => __('Budget, vendor choice, scope trade-offs, go/no-go gates, or policy approvals.'),
            ],
            'reminders' => [
                'key' => 'reminders',
                'label' => __('Check-ins'),
                'required' => $required,
                'prompt' => __('Are there recurring check-ins or reminders to plan?'),
                'hint' => __('Standups, steering reviews, rehearsal dates, or milestone reminders.'),
            ],
            default => throw new \InvalidArgumentException("Unknown requirement key [{$key}]"),
        };
    }

    /**
     * @param  array{key: string, label: string, required: bool, prompt: string, hint: string}  $definition
     * @return array{key: string, label: string, required: bool, prompt: string, hint: string}
     */
    private static function tailorDefinition(array $definition, string $profileKey): array
    {
        if ($definition['key'] !== 'work_items') {
            return $definition;
        }

        $tailoredHint = match ($profileKey) {
            'software' => __('List features, integrations, environments, testing, or release steps — at least two bullets.'),
            'training' => __('List modules, materials, assessments, pilot sessions, or rollout steps — at least two bullets.'),
            'workshop' => __('List agenda blocks, prep work, facilitation tasks, or outputs — at least two bullets.'),
            'event' => __('List venue, program, vendors, communications, or run-of-show tasks — at least two bullets.'),
            'operations' => __('List process changes, communications, training, or rollout tasks — at least two bullets.'),
            default => $definition['hint'],
        };

        return [...$definition, 'hint' => $tailoredHint];
    }

    private static function dynamicLabelFor(string $key): string
    {
        return match ($key) {
            'dynamic_audience' => __('Audience & stakeholders'),
            'dynamic_delivery' => __('Delivery'),
            'dynamic_materials' => __('Materials'),
            'dynamic_outcomes' => __('Outcomes'),
            'dynamic_logistics' => __('Logistics'),
            'dynamic_impact' => __('Impact'),
            'dynamic_scope_depth' => __('Scope'),
            'dynamic_constraints' => __('Constraints & dependencies'),
            default => ucfirst(str_replace('_', ' ', preg_replace('/^dynamic_/', '', $key) ?? $key)),
        };
    }

    /**
     * @return list<array{label: string, value: string, mode: string}>
     */
    private static function projectNameSuggestions(string $profileKey): array
    {
        return match ($profileKey) {
            'software' => [
                self::replaceSuggestion(__('Customer portal'), __('Customer self-service portal')),
                self::replaceSuggestion(__('Mobile app release'), __('Mobile app v2 release')),
                self::replaceSuggestion(__('API integration'), __('Third-party API integration')),
            ],
            'training' => [
                self::replaceSuggestion(__('Leadership program'), __('Leadership fundamentals program')),
                self::replaceSuggestion(__('Compliance training'), __('Annual compliance training')),
                self::replaceSuggestion(__('Skills bootcamp'), __('Technical skills bootcamp')),
            ],
            'workshop' => [
                self::replaceSuggestion(__('Strategy workshop'), __('Quarterly strategy workshop')),
                self::replaceSuggestion(__('Design sprint'), __('Product design sprint')),
                self::replaceSuggestion(__('Team offsite'), __('Leadership team offsite')),
            ],
            'event' => [
                self::replaceSuggestion(__('Annual conference'), __('Annual user conference')),
                self::replaceSuggestion(__('Product launch'), __('Product launch event')),
                self::replaceSuggestion(__('Town hall'), __('Company town hall')),
            ],
            'operations' => [
                self::replaceSuggestion(__('Process improvement'), __('Core process improvement initiative')),
                self::replaceSuggestion(__('Policy rollout'), __('New policy rollout')),
                self::replaceSuggestion(__('Vendor transition'), __('Vendor transition program')),
            ],
            default => [
                self::replaceSuggestion(__('Strategic initiative'), __('Strategic initiative')),
                self::replaceSuggestion(__('Pilot program'), __('Pilot program')),
                self::replaceSuggestion(__('Improvement project'), __('Improvement project')),
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
                self::replaceSuggestion(__('Launch MVP'), __('Deliver an MVP that replaces manual workflows and improves user adoption by the target date.')),
                self::replaceSuggestion(__('Reduce defects'), __('Ship a stable release that reduces critical defects and meets agreed acceptance criteria.')),
                self::replaceSuggestion(__('Integrate systems'), __('Integrate key systems so teams can share data reliably with minimal manual handoffs.')),
            ],
            'training' => [
                self::replaceSuggestion(__('Upskill team'), __('Equip the target audience with practical skills and measurable competency by the end of the program.')),
                self::replaceSuggestion(__('Certify learners'), __('Deliver training that prepares learners to pass certification and apply skills on the job.')),
                self::replaceSuggestion(__('Roll out curriculum'), __('Roll out a standardized curriculum to all required cohorts on schedule.')),
            ],
            'workshop' => [
                self::replaceSuggestion(__('Align stakeholders'), __('Facilitate a focused session that aligns stakeholders on priorities and owned next steps.')),
                self::replaceSuggestion(__('Co-create solution'), __('Run a workshop that produces a validated plan or prototype the team can execute.')),
                self::replaceSuggestion(__('Solve problem'), __('Bring the right participants together to decide on a clear path forward for the stated challenge.')),
            ],
            'event' => [
                self::replaceSuggestion(__('Engage audience'), __('Deliver an event that engages the target audience and achieves the planned program outcomes.')),
                self::replaceSuggestion(__('Launch offering'), __('Execute a launch event that generates awareness and supports the go-to-market plan.')),
                self::replaceSuggestion(__('Community gathering'), __('Host a successful gathering with smooth logistics and positive attendee experience.')),
            ],
            'operations' => [
                self::replaceSuggestion(__('Improve process'), __('Implement an improved process that reduces errors and cycle time for the affected teams.')),
                self::replaceSuggestion(__('Enable compliance'), __('Complete the rollout so the organization meets the required compliance or policy standards.')),
                self::replaceSuggestion(__('Reduce cost'), __('Deliver operational changes that reduce cost or effort without sacrificing quality.')),
            ],
            default => [
                self::replaceSuggestion(__('Deliver outcome'), __('Deliver the planned outcome for the target audience by the agreed timeline.')),
                self::replaceSuggestion(__('Improve performance'), __('Improve performance against the stated baseline with clear, measurable results.')),
                self::replaceSuggestion(__('Complete rollout'), __('Complete rollout with stakeholder sign-off and documented handover.')),
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
                self::appendSuggestion(__('Requirements & design'), __('Gather requirements and finalize solution design')),
                self::appendSuggestion(__('Core build'), __('Implement core features and integrations')),
                self::appendSuggestion(__('Testing & UAT'), __('Complete testing, UAT, and defect remediation')),
                self::appendSuggestion(__('Release & docs'), __('Deploy to production and publish user documentation')),
            ],
            'training' => [
                self::appendSuggestion(__('Curriculum design'), __('Design curriculum outline and learning objectives')),
                self::appendSuggestion(__('Materials'), __('Develop slides, exercises, and job aids')),
                self::appendSuggestion(__('Pilot session'), __('Run pilot session and incorporate feedback')),
                self::appendSuggestion(__('Rollout'), __('Schedule and deliver sessions to all cohorts')),
            ],
            'workshop' => [
                self::appendSuggestion(__('Pre-work'), __('Prepare pre-read materials and participant briefing')),
                self::appendSuggestion(__('Agenda design'), __('Design agenda and facilitation guide')),
                self::appendSuggestion(__('Facilitation'), __('Facilitate workshop sessions and capture outputs')),
                self::appendSuggestion(__('Follow-up'), __('Publish outcomes and assign follow-up actions')),
            ],
            'event' => [
                self::appendSuggestion(__('Venue & vendors'), __('Confirm venue, vendors, and contracts')),
                self::appendSuggestion(__('Program'), __('Finalize program, speakers, and run-of-show')),
                self::appendSuggestion(__('Registration'), __('Set up registration and attendee communications')),
                self::appendSuggestion(__('Event day'), __('Execute event day logistics and debrief')),
            ],
            'operations' => [
                self::appendSuggestion(__('Current state'), __('Document current process and pain points')),
                self::appendSuggestion(__('Future design'), __('Design future-state process and roles')),
                self::appendSuggestion(__('Pilot'), __('Pilot changes with one team or region')),
                self::appendSuggestion(__('Full rollout'), __('Roll out changes and train affected teams')),
            ],
            default => [
                self::appendSuggestion(__('Discovery'), __('Complete discovery and confirm scope')),
                self::appendSuggestion(__('Planning'), __('Create detailed plan and assign owners')),
                self::appendSuggestion(__('Execution'), __('Execute main deliverables')),
                self::appendSuggestion(__('Review'), __('Review outcomes and close out the project')),
            ],
        };
    }

    /**
     * @return list<array{label: string, value: string, mode: string}>
     */
    private static function timelineSuggestions(): array
    {
        return [
            self::replaceSuggestion(__('4 weeks'), __('4-week delivery with weekly milestones')),
            self::replaceSuggestion(__('6 weeks'), __('6-week phased rollout')),
            self::replaceSuggestion(__('This quarter'), __('Complete by end of current quarter')),
            self::replaceSuggestion(__('Next quarter'), __('Target launch next quarter')),
            self::replaceSuggestion(__('Pilot then scale'), __('Pilot in 4 weeks, full rollout within 3 months')),
            self::replaceSuggestion(__('Fixed date'), __('Must complete by a fixed event or deadline date')),
        ];
    }

    /**
     * @return list<array{label: string, value: string, mode: string}>
     */
    private static function successCriteriaSuggestions(string $profileKey): array
    {
        $common = [
            self::appendSuggestion(__('Stakeholder sign-off'), __('Executive or sponsor sign-off obtained')),
            self::appendSuggestion(__('On time'), __('Delivered on or before the target date')),
            self::appendSuggestion(__('Budget met'), __('Delivered within approved budget')),
        ];

        $specific = match ($profileKey) {
            'software' => [
                self::appendSuggestion(__('UAT passed'), __('UAT passed with no critical open defects')),
                self::appendSuggestion(__('Adoption target'), __('Adoption or usage target met post-launch')),
            ],
            'training' => [
                self::appendSuggestion(__('Completion rate'), __('Target completion rate achieved')),
                self::appendSuggestion(__('Assessment pass'), __('Assessment pass rate meets threshold')),
            ],
            'workshop', 'event' => [
                self::appendSuggestion(__('Participant satisfaction'), __('Participant satisfaction score meets target')),
                self::appendSuggestion(__('Outcomes captured'), __('Agreed outcomes documented and accepted')),
            ],
            default => [
                self::appendSuggestion(__('Quality bar'), __('Quality criteria met without rework')),
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
            self::appendSuggestion(__('Budget approval'), __('Budget approval from finance or sponsor')),
            self::appendSuggestion(__('Scope trade-off'), __('Scope trade-off decision if timeline slips')),
            self::appendSuggestion(__('Go / no-go'), __('Go/no-go gate before major rollout')),
        ];

        $specific = match ($profileKey) {
            'software' => [self::appendSuggestion(__('Vendor / build'), __('Build vs buy or vendor selection'))],
            'training' => [self::appendSuggestion(__('Delivery mode'), __('In-house vs external delivery decision'))],
            'event' => [self::appendSuggestion(__('Venue choice'), __('Venue and date confirmation'))],
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
            self::appendSuggestion(__('Weekly standup'), __('Weekly project standup')),
            self::appendSuggestion(__('Steering review'), __('Bi-weekly steering committee review')),
            self::appendSuggestion(__('Milestone check'), __('Milestone readiness check one week before due date')),
            self::appendSuggestion(__('Stakeholder update'), __('Monthly stakeholder status update')),
        ];
    }

    /**
     * @return list<array{label: string, value: string, mode: string}>
     */
    private static function audienceSuggestions(string $profileKey): array
    {
        return match ($profileKey) {
            'software' => [
                self::replaceSuggestion(__('End users'), __('Primary end users, system admins, and executive sponsor')),
                self::replaceSuggestion(__('Internal teams'), __('Internal operators, support team, and product owner')),
                self::replaceSuggestion(__('External clients'), __('External clients or partners who depend on the solution')),
            ],
            'training' => [
                self::replaceSuggestion(__('New hires'), __('New hires or early-career staff in the target role')),
                self::replaceSuggestion(__('Managers'), __('People managers and team leads')),
                self::replaceSuggestion(__('All staff'), __('All staff in a specific department or region')),
            ],
            'workshop' => [
                self::replaceSuggestion(__('Leadership'), __('Leadership team and key decision makers')),
                self::replaceSuggestion(__('Cross-functional'), __('Cross-functional contributors from affected teams')),
                self::replaceSuggestion(__('Subject experts'), __('Subject matter experts and facilitators')),
            ],
            'event' => [
                self::replaceSuggestion(__('Customers'), __('Customers, partners, and prospects')),
                self::replaceSuggestion(__('Employees'), __('Employees and leadership attendees')),
                self::replaceSuggestion(__('Industry guests'), __('Industry guests, speakers, and VIP stakeholders')),
            ],
            default => [
                self::replaceSuggestion(__('Primary users'), __('Primary beneficiaries and day-to-day owners')),
                self::replaceSuggestion(__('Sponsors'), __('Executive sponsor and approvers')),
                self::replaceSuggestion(__('Support teams'), __('Teams who support or maintain the outcome')),
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
                self::replaceSuggestion(__('Agile releases'), __('Agile iterations with staging then production release')),
                self::replaceSuggestion(__('Phased rollout'), __('Phased rollout by user group or region')),
                self::replaceSuggestion(__('Big bang'), __('Single release with full UAT before go-live')),
            ],
            'training' => [
                self::replaceSuggestion(__('In-person'), __('In-person instructor-led sessions')),
                self::replaceSuggestion(__('Virtual live'), __('Live virtual sessions via video conference')),
                self::replaceSuggestion(__('Self-paced'), __('Self-paced e-learning with optional live Q&A')),
                self::replaceSuggestion(__('Hybrid'), __('Hybrid blend of live sessions and self-paced modules')),
            ],
            default => [
                self::replaceSuggestion(__('Standard delivery'), __('Standard delivery approach for this organization')),
            ],
        };
    }

    /**
     * @return list<array{label: string, value: string, mode: string}>
     */
    private static function materialSuggestions(): array
    {
        return [
            self::appendSuggestion(__('Slides'), __('Slide deck and facilitator notes')),
            self::appendSuggestion(__('Exercises'), __('Hands-on exercises and worksheets')),
            self::appendSuggestion(__('Assessment'), __('Knowledge check or final assessment')),
            self::appendSuggestion(__('Job aids'), __('Quick-reference job aids for learners')),
        ];
    }

    /**
     * @return list<array{label: string, value: string, mode: string}>
     */
    private static function outcomeSuggestions(string $profileKey): array
    {
        return match ($profileKey) {
            'workshop' => [
                self::replaceSuggestion(__('Decisions made'), __('Clear decisions documented with owners and dates')),
                self::replaceSuggestion(__('Prioritized backlog'), __('Prioritized backlog or roadmap agreed by participants')),
                self::replaceSuggestion(__('Prototype / plan'), __('Validated prototype, wireframes, or action plan')),
            ],
            default => [
                self::replaceSuggestion(__('Measurable result'), __('Measurable result achieved against the stated goal')),
                self::replaceSuggestion(__('Deliverables accepted'), __('All deliverables accepted by the sponsor')),
                self::replaceSuggestion(__('Behavior change'), __('Target behavior or process change observed after delivery')),
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
                self::replaceSuggestion(__('Half day remote'), __('Half-day remote session via video conference')),
                self::replaceSuggestion(__('Full day onsite'), __('Full-day onsite workshop with breakout rooms')),
                self::replaceSuggestion(__('Multi-day'), __('Multi-day session with pre-work and follow-up')),
            ],
            'event' => [
                self::appendSuggestion(__('Venue booked'), __('Venue booked and layout confirmed')),
                self::appendSuggestion(__('AV & catering'), __('AV, catering, and registration arranged')),
                self::appendSuggestion(__('Run-of-show'), __('Run-of-show timeline finalized')),
            ],
            default => [
                self::replaceSuggestion(__('Remote session'), __('Remote session with scheduled breaks')),
                self::replaceSuggestion(__('Onsite session'), __('Onsite session with room and materials prepared')),
            ],
        };
    }

    /**
     * @return list<array{label: string, value: string, mode: string}>
     */
    private static function impactSuggestions(): array
    {
        return [
            self::appendSuggestion(__('Operations team'), __('Operations team processes and handoffs')),
            self::appendSuggestion(__('Customer-facing'), __('Customer-facing teams and service levels')),
            self::appendSuggestion(__('Finance / HR'), __('Finance, HR, or compliance workflows')),
            self::appendSuggestion(__('IT systems'), __('IT systems and integration points')),
        ];
    }

    /**
     * @return list<array{label: string, value: string, mode: string}>
     */
    private static function scopeDepthSuggestions(): array
    {
        return [
            self::appendSuggestion(__('Auth & access'), __('User authentication and role-based access')),
            self::appendSuggestion(__('Core workflow'), __('Core workflow users need day one')),
            self::appendSuggestion(__('Reporting'), __('Essential reporting or dashboards')),
            self::appendSuggestion(__('Integrations'), __('Critical integrations with existing systems')),
        ];
    }

    /**
     * @return list<array{label: string, value: string, mode: string}>
     */
    private static function constraintSuggestions(): array
    {
        return [
            self::appendSuggestion(__('Budget cap'), __('Fixed budget cap with no additional spend approved')),
            self::appendSuggestion(__('Regulatory'), __('Regulatory or compliance requirements must be met')),
            self::appendSuggestion(__('Resource limits'), __('Limited availability of key subject matter experts')),
            self::appendSuggestion(__('Hard deadline'), __('Immovable external deadline or dependency')),
            self::appendSuggestion(__('Legacy systems'), __('Must work with existing legacy systems during transition')),
        ];
    }

    /**
     * @return array{label: string, value: string, mode: string}
     */
    private static function replaceSuggestion(string $label, string $value): array
    {
        return ['label' => $label, 'value' => $value, 'mode' => 'replace'];
    }

    /**
     * @return array{label: string, value: string, mode: string}
     */
    private static function appendSuggestion(string $label, string $value): array
    {
        return ['label' => $label, 'value' => $value, 'mode' => 'append'];
    }
}
