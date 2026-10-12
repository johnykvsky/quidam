<?php

declare(strict_types = 1);

namespace Quidam\LanguagePack\pl_PL;

use Quidam\Core\PhoneNumber as CorePhoneNumber;

/**
 * Polish numbers - 9 digits, no trunk prefix: landlines with real area codes ("22 345 67 89"), mobiles with real
 * prefixes ("512 345 678"). Calling code "48" comes from the locale, so internationalPhoneNumber() and
 * e164PhoneNumber() follow ("+48 512 345 678", "+48512345678").
 */
class PhoneNumber extends CorePhoneNumber
{
    /**
     * Landlines - area code (strefa numeracyjna) and 7 digits
     *
     * @var list<string>
     */
    protected const array FORMATS = [
        '12 %## ## ##', '13 %## ## ##', '14 %## ## ##', '15 %## ## ##', '16 %## ## ##', '17 %## ## ##', '18 %## ## ##',
        '22 %## ## ##', '23 %## ## ##', '24 %## ## ##', '25 %## ## ##', '29 %## ## ##', '32 %## ## ##', '33 %## ## ##',
        '34 %## ## ##', '41 %## ## ##', '42 %## ## ##', '43 %## ## ##', '44 %## ## ##', '46 %## ## ##', '48 %## ## ##',
        '52 %## ## ##', '54 %## ## ##', '55 %## ## ##', '56 %## ## ##', '58 %## ## ##', '59 %## ## ##', '61 %## ## ##',
        '62 %## ## ##', '63 %## ## ##', '65 %## ## ##', '67 %## ## ##', '68 %## ## ##', '71 %## ## ##', '74 %## ## ##',
        '75 %## ## ##', '76 %## ## ##', '77 %## ## ##', '81 %## ## ##', '82 %## ## ##', '83 %## ## ##', '84 %## ## ##',
        '85 %## ## ##', '86 %## ## ##', '87 %## ## ##', '89 %## ## ##', '91 %## ## ##', '94 %## ## ##', '95 %## ## ##',
    ];

    /**
     * Mobiles - real prefixes of mobile networks
     *
     * @var list<string>
     */
    protected const array MOBILE_FORMATS = [
        '45# ### ###', '50# ### ###', '51# ### ###', '53# ### ###', '57# ### ###', '60# ### ###', '66# ### ###',
        '69# ### ###', '72# ### ###', '73# ### ###', '78# ### ###', '79# ### ###', '88# ### ###',
    ];

    /** @var list<string> */
    protected const array TOLL_FREE_FORMATS = ['800 ### ###', '800-###-###'];

    /**
     * Toll free number (infolinia), e.g. "800 123 456" - free for the caller
     */
    public function tollFreePhoneNumber(): string
    {
        return $this->strings->numerify($this->randomizer->randomElement(static::TOLL_FREE_FORMATS));
    }
}
