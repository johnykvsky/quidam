<?php

declare(strict_types = 1);

namespace Quidam\DeadLanguagePack;

use Quidam\DeadLanguagePack\akk_IQ\AkkIqProviderPack;
use Quidam\DeadLanguagePack\ang_GB\AngGbProviderPack;
use Quidam\DeadLanguagePack\en_XS\EnXsProviderPack;
use Quidam\DeadLanguagePack\enm_GB\EnmGbProviderPack;
use Quidam\DeadLanguagePack\fro_FR\FroFrProviderPack;
use Quidam\DeadLanguagePack\gmh_DE\GmhDeProviderPack;
use Quidam\DeadLanguagePack\got_IT\GotItProviderPack;
use Quidam\DeadLanguagePack\grc_GR\GrcGrProviderPack;
use Quidam\DeadLanguagePack\la_VA\LaVaProviderPack;
use Quidam\DeadLanguagePack\non_IS\NonIsProviderPack;
use Quidam\ProviderPack\ProviderPackInterface;

/**
 * The historical packs Quidam::ancient() picks from - not Klingon, which was never alive (load it, or any other pack,
 * with withProvider())
 */
final class AncientLanguages
{
    /**
     * Pack code => provider pack, oldest first
     *
     * @var array<string, class-string<ProviderPackInterface>>
     */
    public const array PACKS = [
        'akk_IQ' => AkkIqProviderPack::class,
        'grc_GR' => GrcGrProviderPack::class,
        'la_VA' => LaVaProviderPack::class,
        'got_IT' => GotItProviderPack::class,
        'ang_GB' => AngGbProviderPack::class,
        'non_IS' => NonIsProviderPack::class,
        'fro_FR' => FroFrProviderPack::class,
        'gmh_DE' => GmhDeProviderPack::class,
        'enm_GB' => EnmGbProviderPack::class,
        'en_XS' => EnXsProviderPack::class,
    ];
}
