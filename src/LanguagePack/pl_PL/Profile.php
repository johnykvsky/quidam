<?php

declare(strict_types = 1);

namespace Quidam\LanguagePack\pl_PL;

use DateTimeImmutable;
use Psr\Clock\ClockInterface;
use Quidam\Core\Profile as CoreProfile;
use Quidam\Definitions\Enum\Gender;
use Quidam\Definitions\Extension\AddressExtensionInterface;
use Quidam\Definitions\Extension\DateTimeExtensionInterface;
use Quidam\Definitions\Extension\InternetExtensionInterface;
use Quidam\Definitions\Extension\PersonExtensionInterface;
use Quidam\Definitions\Extension\PhoneNumberExtensionInterface;
use Quidam\Definitions\Locale\LocaleInterface;

/**
 * Polish citizen - person() of the core (name, email, age, mobile phone and address of one real place) with national
 * ids matching the person
 */
class Profile extends CoreProfile
{
    public function __construct(
        PersonExtensionInterface $person,
        InternetExtensionInterface $internet,
        DateTimeExtensionInterface $dateTime,
        PhoneNumberExtensionInterface $phoneNumber,
        AddressExtensionInterface $address,
        ClockInterface $clock,
        protected readonly NationalIdInterface $nationalId,
        protected readonly LocaleInterface $locale,
    ) {
        parent::__construct($person, $internet, $dateTime, $phoneNumber, $address, $clock);
    }

    /**
     * person() with PESEL matching birth date and gender, ID card and passport numbers, and the language of the
     * locale (LocaleInterface::languageCode() - "pl"), i.e. for a user's preferred language
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
     *     pesel: string,
     *     idCardNumber: string,
     *     passportNumber: string,
     *     language: string,
     * }
     */
    public function citizen(?Gender $gender = null, int $minAge = 18, int $maxAge = 80): array
    {
        $person = $this->person($gender, $minAge, $maxAge);

        return $person + [
            'pesel' => $this->nationalId->pesel($person['dateOfBirth'], $person['gender']),
            'idCardNumber' => $this->nationalId->idCardNumber(),
            'passportNumber' => $this->nationalId->passportNumber(),
            'language' => $this->locale->languageCode(),
        ];
    }
}
