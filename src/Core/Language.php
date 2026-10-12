<?php

declare(strict_types = 1);

namespace Quidam\Core;

use Quidam\Definitions\Extension\LanguageExtensionInterface;
use Quidam\Definitions\Randomizer\RandomizerInterface;
use Quidam\Exception\InvalidArgumentException;

class Language implements LanguageExtensionInterface
{
    /**
     * ISO 639-1 codes of living languages => English name
     *
     * @var array<string, string>
     *
     * @see https://en.wikipedia.org/wiki/List_of_ISO_639_language_codes
     */
    protected const array LANGUAGES = [
        'aa' => 'Afar',
        'ab' => 'Abkhazian',
        'af' => 'Afrikaans',
        'ak' => 'Akan',
        'am' => 'Amharic',
        'an' => 'Aragonese',
        'ar' => 'Arabic',
        'as' => 'Assamese',
        'av' => 'Avaric',
        'ay' => 'Aymara',
        'az' => 'Azerbaijani',
        'ba' => 'Bashkir',
        'be' => 'Belarusian',
        'bg' => 'Bulgarian',
        'bi' => 'Bislama',
        'bm' => 'Bambara',
        'bn' => 'Bengali',
        'bo' => 'Tibetan',
        'br' => 'Breton',
        'bs' => 'Bosnian',
        'ca' => 'Catalan',
        'ce' => 'Chechen',
        'ch' => 'Chamorro',
        'co' => 'Corsican',
        'cr' => 'Cree',
        'cs' => 'Czech',
        'cv' => 'Chuvash',
        'cy' => 'Welsh',
        'da' => 'Danish',
        'de' => 'German',
        'dv' => 'Divehi',
        'dz' => 'Dzongkha',
        'ee' => 'Ewe',
        'el' => 'Greek',
        'en' => 'English',
        'es' => 'Spanish',
        'et' => 'Estonian',
        'eu' => 'Basque',
        'fa' => 'Persian',
        'ff' => 'Fulah',
        'fi' => 'Finnish',
        'fj' => 'Fijian',
        'fo' => 'Faroese',
        'fr' => 'French',
        'fy' => 'Western Frisian',
        'ga' => 'Irish',
        'gd' => 'Scottish Gaelic',
        'gl' => 'Galician',
        'gn' => 'Guarani',
        'gu' => 'Gujarati',
        'gv' => 'Manx',
        'ha' => 'Hausa',
        'he' => 'Hebrew',
        'hi' => 'Hindi',
        'ho' => 'Hiri Motu',
        'hr' => 'Croatian',
        'ht' => 'Haitian',
        'hu' => 'Hungarian',
        'hy' => 'Armenian',
        'hz' => 'Herero',
        'id' => 'Indonesian',
        'ig' => 'Igbo',
        'ii' => 'Sichuan Yi',
        'ik' => 'Inupiaq',
        'is' => 'Icelandic',
        'it' => 'Italian',
        'iu' => 'Inuktitut',
        'ja' => 'Japanese',
        'jv' => 'Javanese',
        'ka' => 'Georgian',
        'kg' => 'Kongo',
        'ki' => 'Kikuyu',
        'kj' => 'Kuanyama',
        'kk' => 'Kazakh',
        'kl' => 'Kalaallisut',
        'km' => 'Khmer',
        'kn' => 'Kannada',
        'ko' => 'Korean',
        'kr' => 'Kanuri',
        'ks' => 'Kashmiri',
        'ku' => 'Kurdish',
        'kv' => 'Komi',
        'kw' => 'Cornish',
        'ky' => 'Kirghiz',
        'lb' => 'Luxembourgish',
        'lg' => 'Ganda',
        'li' => 'Limburgan',
        'ln' => 'Lingala',
        'lo' => 'Lao',
        'lt' => 'Lithuanian',
        'lu' => 'Luba-Katanga',
        'lv' => 'Latvian',
        'mg' => 'Malagasy',
        'mh' => 'Marshallese',
        'mi' => 'Maori',
        'mk' => 'Macedonian',
        'ml' => 'Malayalam',
        'mn' => 'Mongolian',
        'mr' => 'Marathi',
        'ms' => 'Malay',
        'mt' => 'Maltese',
        'my' => 'Burmese',
        'na' => 'Nauru',
        'nb' => 'Norwegian Bokmål',
        'nd' => 'North Ndebele',
        'ne' => 'Nepali',
        'ng' => 'Ndonga',
        'nl' => 'Dutch',
        'nn' => 'Norwegian Nynorsk',
        'no' => 'Norwegian',
        'nr' => 'South Ndebele',
        'nv' => 'Navajo',
        'ny' => 'Chichewa',
        'oc' => 'Occitan',
        'oj' => 'Ojibwa',
        'om' => 'Oromo',
        'or' => 'Oriya',
        'os' => 'Ossetian',
        'pa' => 'Panjabi',
        'pl' => 'Polish',
        'ps' => 'Pushto',
        'pt' => 'Portuguese',
        'qu' => 'Quechua',
        'rm' => 'Romansh',
        'rn' => 'Rundi',
        'ro' => 'Romanian',
        'ru' => 'Russian',
        'rw' => 'Kinyarwanda',
        'sc' => 'Sardinian',
        'sd' => 'Sindhi',
        'se' => 'Northern Sami',
        'sg' => 'Sango',
        'sh' => 'Serbo-Croatian',
        'si' => 'Sinhala',
        'sk' => 'Slovak',
        'sl' => 'Slovenian',
        'sm' => 'Samoan',
        'sn' => 'Shona',
        'so' => 'Somali',
        'sq' => 'Albanian',
        'sr' => 'Serbian',
        'ss' => 'Swati',
        'st' => 'Southern Sotho',
        'su' => 'Sundanese',
        'sv' => 'Swedish',
        'sw' => 'Swahili',
        'ta' => 'Tamil',
        'te' => 'Telugu',
        'tg' => 'Tajik',
        'th' => 'Thai',
        'ti' => 'Tigrinya',
        'tk' => 'Turkmen',
        'tl' => 'Tagalog',
        'tn' => 'Tswana',
        'to' => 'Tongan',
        'tr' => 'Turkish',
        'ts' => 'Tsonga',
        'tt' => 'Tatar',
        'tw' => 'Twi',
        'ty' => 'Tahitian',
        'ug' => 'Uighur',
        'uk' => 'Ukrainian',
        'ur' => 'Urdu',
        'uz' => 'Uzbek',
        've' => 'Venda',
        'vi' => 'Vietnamese',
        'wa' => 'Walloon',
        'wo' => 'Wolof',
        'xh' => 'Xhosa',
        'yi' => 'Yiddish',
        'yo' => 'Yoruba',
        'za' => 'Zhuang',
        'zh' => 'Chinese',
        'zu' => 'Zulu',
    ];

    /**
     * Locales (language_COUNTRY) supported by glibc
     *
     * @var list<string>
     */
    protected const array LOCALES = [
        'aa_DJ', 'aa_ER', 'aa_ET', 'af_ZA', 'ak_GH', 'am_ET', 'an_ES', 'ar_AE', 'ar_BH', 'ar_DZ', 'ar_EG', 'ar_IN',
        'ar_IQ', 'ar_JO', 'ar_KW', 'ar_LB', 'ar_LY', 'ar_MA', 'ar_OM', 'ar_QA', 'ar_SA', 'ar_SD', 'ar_SS', 'ar_SY',
        'ar_TN', 'ar_YE', 'as_IN', 'az_AZ', 'az_IR', 'be_BY', 'bg_BG', 'bi_VU', 'bn_BD', 'bn_IN', 'bo_CN', 'bo_IN',
        'br_FR', 'bs_BA', 'ca_AD', 'ca_ES', 'ca_FR', 'ca_IT', 'ce_RU', 'cs_CZ', 'cv_RU', 'cy_GB', 'da_DK', 'de_AT',
        'de_BE', 'de_CH', 'de_DE', 'de_IT', 'de_LI', 'de_LU', 'dv_MV', 'dz_BT', 'el_CY', 'el_GR', 'en_AG', 'en_AU',
        'en_BW', 'en_CA', 'en_DK', 'en_GB', 'en_HK', 'en_IE', 'en_IL', 'en_IN', 'en_NG', 'en_NZ', 'en_PH', 'en_SC',
        'en_SE', 'en_SG', 'en_US', 'en_ZA', 'en_ZM', 'en_ZW', 'es_AR', 'es_BO', 'es_CL', 'es_CO', 'es_CR', 'es_CU',
        'es_DO', 'es_EC', 'es_ES', 'es_GT', 'es_HN', 'es_MX', 'es_NI', 'es_PA', 'es_PE', 'es_PR', 'es_PY', 'es_SV',
        'es_US', 'es_UY', 'es_VE', 'et_EE', 'eu_ES', 'fa_IR', 'ff_SN', 'fi_FI', 'fo_FO', 'fr_BE', 'fr_CA', 'fr_CH',
        'fr_FR', 'fr_LU', 'fy_DE', 'fy_NL', 'ga_IE', 'gd_GB', 'gl_ES', 'gu_IN', 'gv_GB', 'ha_NG', 'he_IL', 'hi_IN',
        'hr_HR', 'ht_HT', 'hu_HU', 'hy_AM', 'id_ID', 'ig_NG', 'ik_CA', 'is_IS', 'it_CH', 'it_IT', 'iu_CA', 'ja_JP',
        'ka_GE', 'kk_KZ', 'kl_GL', 'km_KH', 'kn_IN', 'ko_KR', 'ks_IN', 'ku_TR', 'kv_RU', 'kw_GB', 'ky_KG', 'lb_LU',
        'lg_UG', 'li_BE', 'li_NL', 'ln_CD', 'lo_LA', 'lt_LT', 'lv_LV', 'mg_MG', 'mi_NZ', 'mk_MK', 'ml_IN', 'mn_MN',
        'mr_IN', 'ms_MY', 'mt_MT', 'my_MM', 'nb_NO', 'ne_NP', 'nl_AW', 'nl_BE', 'nl_NL', 'nn_NO', 'nr_ZA', 'oc_FR',
        'om_ET', 'om_KE', 'or_IN', 'os_RU', 'pa_IN', 'pa_PK', 'pl_PL', 'ps_AF', 'pt_BR', 'pt_PT', 'ro_RO', 'ru_RU',
        'ru_UA', 'rw_RW', 'sc_IT', 'sd_IN', 'se_NO', 'si_LK', 'sk_SK', 'sl_SI', 'sm_WS', 'so_DJ', 'so_ET', 'so_KE',
        'so_SO', 'sq_AL', 'sq_MK', 'sr_ME', 'sr_RS', 'ss_ZA', 'st_ZA', 'su_ID', 'sv_FI', 'sv_SE', 'sw_KE', 'sw_TZ',
        'ta_IN', 'ta_LK', 'te_IN', 'tg_TJ', 'th_TH', 'ti_ER', 'ti_ET', 'tk_TM', 'tl_PH', 'tn_ZA', 'to_TO', 'tr_CY',
        'tr_TR', 'ts_ZA', 'tt_RU', 'ug_CN', 'uk_UA', 'ur_IN', 'ur_PK', 'uz_UZ', 've_ZA', 'vi_VN', 'wa_BE', 'wo_SN',
        'xh_ZA', 'yi_US', 'yo_NG', 'zh_CN', 'zh_HK', 'zh_SG', 'zh_TW', 'zu_ZA',
    ];

    public function __construct(
        protected readonly RandomizerInterface $randomizer,
    ) {
    }

    /**
     * English name of a language, e.g. "Polish"
     */
    public function language(): string
    {
        return $this->languageData()['name'];
    }

    /**
     * ISO 639-1 language code, e.g. "pl"
     */
    public function languageCode(): string
    {
        return $this->languageData()['code'];
    }

    /**
     * Matching code and name of one language, e.g. ['code' => 'pl', 'name' => 'Polish']
     *
     * @return array{code: string, name: string}
     */
    public function languageData(): array
    {
        $code = $this->randomizer->randomKey(static::LANGUAGES);

        return ['code' => $code, 'name' => static::LANGUAGES[$code]];
    }

    /**
     * Locale with language and country code, e.g. "pl_PL", or "pl-PL" (BCP 47 style) with "-" separator
     *
     * @param string $separator "_" or "-"
     */
    public function locale(string $separator = '_'): string
    {
        if ($separator !== '_' && $separator !== '-') {
            throw new InvalidArgumentException('locale() separator must be "_" or "-"');
        }

        return str_replace('_', $separator, $this->randomizer->randomElement(static::LOCALES));
    }
}
