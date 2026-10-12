<?php

declare(strict_types = 1);

namespace Quidam\LanguagePack\pl_PL;

use Quidam\Core\Internet as CoreInternet;

/**
 * Polish email providers and domains - user names and domains are ASCII ("Łucja Żółć" -> "lucja.zolc"), the core
 * transliterates them
 */
class Internet extends CoreInternet
{
    /** @var list<string> */
    protected const array FREE_EMAIL_DOMAIN = [
        'gmail.com', 'wp.pl', 'onet.pl', 'interia.pl', 'o2.pl', 'op.pl', 'gazeta.pl', 'outlook.com',
    ];

    /** @var list<string> */
    protected const array TLD = [
        'pl', 'pl', 'pl', 'pl', 'pl', 'pl', 'com.pl', 'com.pl', 'com', 'eu', 'net.pl', 'org.pl',
    ];
}
