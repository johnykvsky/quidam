<?php

declare(strict_types = 1);

namespace Quidam\Core;

use DateTimeImmutable;
use Psr\Clock\ClockInterface;
use Quidam\Definitions\Enum\Gender;
use Quidam\Definitions\Extension\AddressExtensionInterface;
use Quidam\Definitions\Extension\DateTimeExtensionInterface;
use Quidam\Definitions\Extension\InternetExtensionInterface;
use Quidam\Definitions\Extension\PersonExtensionInterface;
use Quidam\Definitions\Extension\PhoneNumberExtensionInterface;
use Quidam\Definitions\Extension\ProfileExtensionInterface;

/**
 * Coherent records - values which belong together (name matches gender, email matches name, age matches birth date),
 * unlike separate calls to person(), internet() and dateTime().
 */
class Profile implements ProfileExtensionInterface
{
    public function __construct(
        protected readonly PersonExtensionInterface $person,
        protected readonly InternetExtensionInterface $internet,
        protected readonly DateTimeExtensionInterface $dateTime,
        protected readonly PhoneNumberExtensionInterface $phoneNumber,
        protected readonly AddressExtensionInterface $address,
        protected readonly ClockInterface $clock,
    ) {
    }

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
    public function person(?Gender $gender = null, int $minAge = 18, int $maxAge = 80): array
    {
        $gender ??= $this->person->gender();
        $firstName = $this->person->firstName($gender);
        $lastName = $this->person->lastName($gender);
        $dateOfBirth = $this->dateTime->dateOfBirth($minAge, $maxAge);
        $title = $this->person->title($gender);
        $userName = $this->internet->userName($firstName, $lastName);

        return [
            'gender' => $gender,
            'title' => $title,
            'firstName' => $firstName,
            'lastName' => $lastName,
            'name' => $this->person->fullName($firstName, $lastName),
            'userName' => $userName,
            'email' => $userName . '@' . $this->internet->safeEmailDomain(),
            'dateOfBirth' => $dateOfBirth,
            'age' => $dateOfBirth->diff($this->clock->now()->setTimezone($dateOfBirth->getTimezone()))->y,
            'phone' => $this->phoneNumber->mobilePhoneNumber(),
            'address' => $this->address->addressData(),
        ];
    }
}
