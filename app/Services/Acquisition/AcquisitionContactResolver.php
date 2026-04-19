<?php

namespace App\Services\Acquisition;

use App\Models\AcquisitionCompany;
use App\Models\AcquisitionContact;
use App\Models\AcquisitionPerson;
use App\Models\Lead;
use App\Models\PopupLead;
use Illuminate\Database\QueryException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AcquisitionContactResolver
{
    public function resolveFromLead(Lead $lead): AcquisitionContact
    {
        return $this->resolveCanonicalIntake([
            'name' => $lead->first_name,
            'email' => $lead->email,
            'phone' => Arr::get($lead->payload ?? [], 'phone'),
            'company_name' => null,
            'website_url' => null,
            'city' => null,
            'state' => null,
            'contact_type' => 'inbound',
            'source_type' => 'lead_submission',
            'source_label' => $lead->type ?: 'lead_submission',
        ]);
    }

    public function resolveFromPopupLead(PopupLead $popupLead): AcquisitionContact
    {
        return $this->resolveCanonicalIntake([
            'name' => $popupLead->name,
            'email' => $popupLead->email,
            'phone' => $popupLead->phone,
            'company_name' => null,
            'website_url' => null,
            'city' => null,
            'state' => null,
            'contact_type' => 'inbound',
            'source_type' => 'popup_submission',
            'source_label' => $popupLead->lead_type ?: 'popup_submission',
        ]);
    }

    /**
     * @param array{
     *     name?: string|null,
     *     email?: string|null,
     *     phone?: string|null,
     *     company_name?: string|null,
     *     website_url?: string|null,
     *     city?: string|null,
     *     state?: string|null,
     *     contact_type?: string|null,
     *     source_type?: string|null,
     *     source_label?: string|null
     * } $attributes
     */
    public function resolve(array $attributes): AcquisitionContact
    {
        $normalized = $this->normalize($attributes);

        return $this->resolveNormalized($normalized, true);
    }

    /** @param array<string, mixed> $attributes */
    private function normalize(array $attributes): array
    {
        $websiteUrl = $this->normalizeUrl($attributes['website_url'] ?? null);

        return [
            'name' => $this->normalizeText($attributes['name'] ?? null),
            'email' => $this->normalizeEmail($attributes['email'] ?? null),
            'phone' => $this->normalizePhone($attributes['phone'] ?? null),
            'company_name' => $this->normalizeText($attributes['company_name'] ?? null),
            'website_url' => $websiteUrl,
            'domain' => $this->extractDomain($websiteUrl),
            'city' => $this->normalizeText($attributes['city'] ?? null),
            'state' => $this->normalizeText($attributes['state'] ?? null),
            'contact_type' => $this->normalizeContactType($attributes['contact_type'] ?? null),
            'source_type' => $this->normalizeText($attributes['source_type'] ?? null) ?: 'manual_entry',
            'source_label' => $this->normalizeText($attributes['source_label'] ?? null),
        ];
    }

    /** @param array<string, mixed> $attributes */
    private function resolveCanonicalIntake(array $attributes): AcquisitionContact
    {
        $normalized = $this->normalize($attributes);

        return $this->resolveNormalized($normalized, false);
    }

    /** @param array<string, mixed> $normalized */
    private function resolveNormalized(array $normalized, bool $allowCompanyFallback): AcquisitionContact
    {
        try {
            return DB::transaction(function () use ($normalized, $allowCompanyFallback): AcquisitionContact {
                return $this->resolveWithinTransaction($normalized, $allowCompanyFallback);
            });
        } catch (QueryException $exception) {
            if (! $this->wasIdentityConstraintViolation($exception)) {
                throw $exception;
            }
        }

        $contact = $this->findExistingContact($normalized, $allowCompanyFallback);

        if ($contact) {
            return $contact;
        }

        throw new \RuntimeException('Failed to resolve acquisition contact after identity key conflict.');
    }

    /** @param array<string, mixed> $normalized */
    private function resolveWithinTransaction(array $normalized, bool $allowCompanyFallback): AcquisitionContact
    {
        $contact = $this->findExistingContact($normalized, $allowCompanyFallback);
        $company = $this->resolveCompany($normalized, $contact, $allowCompanyFallback);
        $person = $this->resolvePerson($normalized, $company, $contact);

        if ($contact) {
            return tap($contact, function (AcquisitionContact $existing) use ($normalized, $company, $person): void {
                $existing->fill(array_filter([
                    'acquisition_company_id' => $company?->id ?? $existing->acquisition_company_id,
                    'acquisition_person_id' => $person?->id ?? $existing->acquisition_person_id,
                    'contact_type' => $this->mergeContactType($existing->contact_type, $normalized['contact_type']),
                    'source_type' => $existing->source_type ?: $normalized['source_type'],
                    'source_label' => $existing->source_label ?: $normalized['source_label'],
                    'primary_email' => $existing->primary_email ?: $normalized['email'],
                    'primary_phone' => $existing->primary_phone ?: $normalized['phone'],
                    'display_name' => $existing->display_name ?: $normalized['name'],
                    'company_name_snapshot' => $existing->company_name_snapshot ?: $normalized['company_name'],
                    'website_url_snapshot' => $existing->website_url_snapshot ?: $normalized['website_url'],
                    'city_snapshot' => $existing->city_snapshot ?: $normalized['city'],
                    'state_snapshot' => $existing->state_snapshot ?: $normalized['state'],
                    'normalized_email_key' => $existing->normalized_email_key ?: $this->contactEmailKey($existing, $normalized),
                    'normalized_phone_key' => $existing->normalized_phone_key ?: $this->contactPhoneKey($existing, $normalized),
                    'last_activity_at' => now(),
                ], fn ($value) => $value !== null));

                $existing->save();
            });
        }

        return AcquisitionContact::query()->create(array_filter([
            'acquisition_company_id' => $company?->id,
            'acquisition_person_id' => $person?->id,
            'contact_type' => $normalized['contact_type'],
            'state' => 'new',
            'source_type' => $normalized['source_type'],
            'source_label' => $normalized['source_label'],
            'normalized_email_key' => $normalized['email'],
            'normalized_phone_key' => $normalized['phone'],
            'primary_email' => $normalized['email'],
            'primary_phone' => $normalized['phone'],
            'display_name' => $normalized['name'],
            'company_name_snapshot' => $normalized['company_name'],
            'website_url_snapshot' => $normalized['website_url'],
            'city_snapshot' => $normalized['city'],
            'state_snapshot' => $normalized['state'],
            'last_activity_at' => now(),
        ], fn ($value) => $value !== null));
    }

    /** @param array<string, mixed> $normalized */
    private function findExistingContact(array $normalized, bool $allowCompanyFallback): ?AcquisitionContact
    {
        if (!empty($normalized['email'])) {
            $contact = AcquisitionContact::query()
                ->where('normalized_email_key', $normalized['email'])
                ->orderBy('id')
                ->first();

            if ($contact) {
                return $contact;
            }

            $contact = AcquisitionContact::query()
                ->where('primary_email', $normalized['email'])
                ->orderBy('id')
                ->first();

            if ($contact) {
                return $contact;
            }
        }

        if (!empty($normalized['phone'])) {
            $contact = AcquisitionContact::query()
                ->where('normalized_phone_key', $normalized['phone'])
                ->orderBy('id')
                ->first();

            if ($contact) {
                return $contact;
            }

            $contact = AcquisitionContact::query()
                ->where('primary_phone', $normalized['phone'])
                ->orderBy('id')
                ->first();

            if ($contact) {
                return $contact;
            }
        }

        if ($allowCompanyFallback && !empty($normalized['company_name']) && !empty($normalized['domain'])) {
            return AcquisitionContact::query()
                ->where('company_name_snapshot', $normalized['company_name'])
                ->where('website_url_snapshot', 'like', '%' . $normalized['domain'] . '%')
                ->orderBy('id')
                ->first();
        }

        return null;
    }

    /** @param array<string, mixed> $normalized */
    private function resolveCompany(array $normalized, ?AcquisitionContact $contact, bool $allowCompanyFallback): ?AcquisitionCompany
    {
        if (! $allowCompanyFallback) {
            return $contact?->company;
        }

        if ($contact?->acquisition_company_id) {
            $company = $contact->company;

            if ($company) {
                $company->fill(array_filter([
                    'website_url' => $company->website_url ?: $normalized['website_url'],
                    'domain' => $company->domain ?: $normalized['domain'],
                    'city' => $company->city ?: $normalized['city'],
                    'state' => $company->state ?: $normalized['state'],
                ], fn ($value) => $value !== null));
                $company->save();
            }

            return $company;
        }

        if (empty($normalized['company_name']) && empty($normalized['domain'])) {
            return null;
        }

        $query = AcquisitionCompany::query();

        if (!empty($normalized['domain'])) {
            $company = (clone $query)->where('domain', $normalized['domain'])->orderBy('id')->first();
            if ($company) {
                $company->fill(array_filter([
                    'name' => $company->name ?: $normalized['company_name'],
                    'website_url' => $company->website_url ?: $normalized['website_url'],
                    'city' => $company->city ?: $normalized['city'],
                    'state' => $company->state ?: $normalized['state'],
                ], fn ($value) => $value !== null));
                $company->save();

                return $company;
            }
        }

        if (!empty($normalized['company_name'])) {
            $company = (clone $query)->where('name', $normalized['company_name'])->orderBy('id')->first();
            if ($company) {
                $company->fill(array_filter([
                    'website_url' => $company->website_url ?: $normalized['website_url'],
                    'domain' => $company->domain ?: $normalized['domain'],
                    'city' => $company->city ?: $normalized['city'],
                    'state' => $company->state ?: $normalized['state'],
                ], fn ($value) => $value !== null));
                $company->save();

                return $company;
            }
        }

        return AcquisitionCompany::query()->create(array_filter([
            'name' => $normalized['company_name'] ?: $normalized['domain'],
            'website_url' => $normalized['website_url'],
            'domain' => $normalized['domain'],
            'city' => $normalized['city'],
            'state' => $normalized['state'],
            'status' => 'active',
        ], fn ($value) => $value !== null));
    }

    /** @param array<string, mixed> $normalized */
    private function resolvePerson(array $normalized, ?AcquisitionCompany $company, ?AcquisitionContact $contact): ?AcquisitionPerson
    {
        if ($contact?->acquisition_person_id) {
            $person = $contact->person;

            if ($person) {
                $person->fill(array_filter([
                    'acquisition_company_id' => $person->acquisition_company_id ?: $company?->id,
                    'full_name' => $person->full_name ?: $normalized['name'],
                    'email' => $person->email ?: $normalized['email'],
                    'phone' => $person->phone ?: $normalized['phone'],
                ], fn ($value) => $value !== null));
                $person->save();
            }

            return $person;
        }

        if (empty($normalized['name']) && empty($normalized['email']) && empty($normalized['phone'])) {
            return null;
        }

        $query = AcquisitionPerson::query();

        if (!empty($normalized['email'])) {
            $person = (clone $query)->where('email', $normalized['email'])->orderBy('id')->first();
            if ($person) {
                $person->fill(array_filter([
                    'acquisition_company_id' => $person->acquisition_company_id ?: $company?->id,
                    'full_name' => $person->full_name ?: $normalized['name'],
                    'phone' => $person->phone ?: $normalized['phone'],
                ], fn ($value) => $value !== null));
                $person->save();

                return $person;
            }
        }

        if (!empty($normalized['phone'])) {
            $person = (clone $query)->where('phone', $normalized['phone'])->orderBy('id')->first();
            if ($person) {
                $person->fill(array_filter([
                    'acquisition_company_id' => $person->acquisition_company_id ?: $company?->id,
                    'full_name' => $person->full_name ?: $normalized['name'],
                    'email' => $person->email ?: $normalized['email'],
                ], fn ($value) => $value !== null));
                $person->save();

                return $person;
            }
        }

        return AcquisitionPerson::query()->create(array_filter([
            'acquisition_company_id' => $company?->id,
            'full_name' => $normalized['name'],
            'email' => $normalized['email'],
            'phone' => $normalized['phone'],
            'is_primary_contact' => true,
        ], fn ($value) => $value !== null));
    }

    private function normalizeEmail(?string $value): ?string
    {
        $value = $this->normalizeText($value);

        return $value ? Str::lower($value) : null;
    }

    private function normalizePhone(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        if ($value === '') {
            return null;
        }

        $value = preg_replace('/(?:ext\.?|extension|x)\s*\d+.*$/i', '', $value) ?? $value;
        $digits = preg_replace('/\D+/', '', $value);

        if ($digits === '') {
            return null;
        }

        if (strlen($digits) === 11 && Str::startsWith($digits, '1')) {
            $digits = substr($digits, 1);
        }

        return strlen($digits) >= 7 ? $digits : null;
    }

    private function normalizeUrl(?string $value): ?string
    {
        $value = $this->normalizeText($value);

        if (!$value) {
            return null;
        }

        if (!Str::startsWith($value, ['http://', 'https://'])) {
            $value = 'https://' . $value;
        }

        return filter_var($value, FILTER_VALIDATE_URL) ? $value : null;
    }

    private function extractDomain(?string $url): ?string
    {
        if (!$url) {
            return null;
        }

        $host = parse_url($url, PHP_URL_HOST);

        if (!is_string($host) || $host === '') {
            return null;
        }

        return Str::lower(preg_replace('/^www\./', '', $host));
    }

    private function normalizeText(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim(strip_tags($value));

        return $value === '' ? null : Str::squish($value);
    }

    private function normalizeContactType(?string $value): string
    {
        $value = $this->normalizeText($value);

        return in_array($value, ['inbound', 'outbound', 'hybrid'], true) ? $value : 'inbound';
    }

    private function mergeContactType(?string $existing, ?string $incoming): string
    {
        $existing = $this->normalizeContactType($existing);
        $incoming = $this->normalizeContactType($incoming);

        if ($existing === $incoming) {
            return $existing;
        }

        if (in_array('hybrid', [$existing, $incoming], true)) {
            return 'hybrid';
        }

        return 'hybrid';
    }

    /** @param array<string, mixed> $normalized */
    private function contactEmailKey(AcquisitionContact $contact, array $normalized): ?string
    {
        return $this->normalizeEmail($contact->primary_email) ?: $normalized['email'];
    }

    /** @param array<string, mixed> $normalized */
    private function contactPhoneKey(AcquisitionContact $contact, array $normalized): ?string
    {
        return $this->normalizePhone($contact->primary_phone) ?: $normalized['phone'];
    }

    private function wasIdentityConstraintViolation(QueryException $exception): bool
    {
        $sqlState = $exception->errorInfo[0] ?? null;
        $driverCode = $exception->errorInfo[1] ?? null;
        $message = Str::lower($exception->getMessage());

        if (in_array($sqlState, ['23000', '23505'], true)) {
            return str_contains($message, 'normalized_email_key')
                || str_contains($message, 'normalized_phone_key');
        }

        return $driverCode === 19
            && (str_contains($message, 'normalized_email_key') || str_contains($message, 'normalized_phone_key'));
    }
}
