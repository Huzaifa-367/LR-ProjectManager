<?php

namespace App\Support;

use App\Enums\MailProvider;
use App\Models\Organization;
use App\Models\OrganizationMailProfile;

final class OrganizationMailerResolver
{
    public function defaultForOrganization(Organization $organization): ?OrganizationMailProfile
    {
        $default = OrganizationMailProfile::query()
            ->where('organization_id', $organization->id)
            ->where('is_active', true)
            ->where('is_default', true)
            ->first();

        if ($default !== null) {
            return $default;
        }

        return OrganizationMailProfile::query()
            ->where('organization_id', $organization->id)
            ->where('is_active', true)
            ->orderBy('id')
            ->first();
    }

    public function mailerName(OrganizationMailProfile $profile): string
    {
        return "organization_mail_{$profile->id}";
    }

    public function registerMailer(OrganizationMailProfile $profile): string
    {
        $mailerName = $this->mailerName($profile);

        config([
            "mail.mailers.{$mailerName}" => $this->buildMailerConfig($profile),
        ]);

        return $mailerName;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildMailerConfig(OrganizationMailProfile $profile): array
    {
        if ($profile->provider === MailProvider::Smtp) {
            /** @var array<string, mixed> $config */
            $config = $profile->config ?? [];

            return [
                'transport' => 'smtp',
                'host' => $config['host'] ?? '',
                'port' => (int) ($config['port'] ?? 587),
                'encryption' => $config['encryption'] ?? null,
                'username' => $config['username'] ?? null,
                'password' => $config['password'] ?? null,
                'timeout' => null,
                'local_domain' => parse_url((string) config('app.url', 'http://localhost'), PHP_URL_HOST),
            ];
        }

        return (array) config('mail.mailers.smtp', [
            'transport' => 'smtp',
            'host' => config('mail.mailers.smtp.host'),
            'port' => config('mail.mailers.smtp.port'),
            'encryption' => config('mail.mailers.smtp.encryption'),
            'username' => config('mail.mailers.smtp.username'),
            'password' => config('mail.mailers.smtp.password'),
            'timeout' => null,
        ]);
    }
}
