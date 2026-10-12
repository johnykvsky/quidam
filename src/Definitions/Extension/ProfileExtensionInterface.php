<?php

declare(strict_types = 1);

namespace Quidam\Definitions\Extension;

use DateTimeImmutable;
use Quidam\Definitions\Enum\Gender;

interface ProfileExtensionInterface extends ExtensionInterface
{
    /**
     * One person: name and title match gender, name follows the language's order (Person::fullName()), email is the
     * user name in a reserved domain, age matches birth date (relative to clock "now"); mobile phone and address of
     * the locale (addressData() - street, city, region, postcode, coordinates and the address line belong together)
     *
     * @return array{
     *     gender: Gender,
     *     title: string,
     *     firstName: string,
     *     lastName: string,
     *     name: string,
     *     userName: string,
     *     email: string,
     *     dateOfBirth: DateTimeImmutable,
     *     age: int,
     *     phone: string,
     *     address: array{street: string, city: string, state: string, stateCode: string, county: string, postcode: string, latitude: float, longitude: float, address: string},
     * }
     */
    public function person(?Gender $gender = null, int $minAge = 18, int $maxAge = 80): array;
}
