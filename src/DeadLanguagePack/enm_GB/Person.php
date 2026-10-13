<?php

declare(strict_types = 1);

namespace Quidam\DeadLanguagePack\enm_GB;

use Quidam\Core\Person as CorePerson;

/**
 * Middle English names - a given name and a byname, before surnames became hereditary: by trade ("Johan le Smyth"),
 * by place ("Alis atte Welle" - at the well), by father ("Robert Fitz Water") or by looks ("Water Longe"),
 * the same for women and men
 */
class Person extends CorePerson
{
    /** @var list<string> */
    protected const array FIRST_NAME_MALE = [
        'Adam', 'Alayn', 'Elis', 'Geffrey', 'Gilbert', 'Hamond', 'Henri', 'Hugh', 'Johan', 'Laurence', 'Nicholas',
        'Piers', 'Rauf', 'Reynald', 'Richard', 'Robert', 'Roger', 'Simond', 'Stevene', 'Thomas', 'Water', 'William',
    ];

    /** @var list<string> */
    protected const array FIRST_NAME_FEMALE = [
        'Agnes', 'Alianore', 'Alis', 'Amice', 'Avice', 'Beatrice', 'Cecilie', 'Cristiane', 'Edith', 'Emme', 'Isabel',
        'Johane', 'Juliane', 'Kateryne', 'Lettice', 'Mabile', 'Margarete', 'Margerie', 'Maud', 'Parnel', 'Rose',
        'Sibille',
    ];

    /** @var list<string> */
    protected const array FIRST_NAME_NEUTRAL = [];

    /**
     * Bynames - trades, places, fathers and nicknames ("Shakespere" is one, recorded from the 13th century)
     *
     * @var list<string>
     */
    protected const array LAST_NAME = [
        'le Smyth', 'le Taillour', 'le Bakere', 'le Couper', 'le Mullere', 'le Webbe', 'le Carpenter', 'le Fissher',
        'le Spicer', 'le Taverner', 'le Wright', 'le Clerk', 'le Cook', 'le Hunte', 'le Chapman', 'le Mercer',
        'le Fletcher', 'le Bowyere', 'atte Welle', 'atte Wode', 'atte Mille', 'atte Grene', 'atte Brigge', 'atte Halle',
        'atte Hulle', 'atte Lane', 'atte Style', 'de Lyncolne', 'de Ware', 'of Bathe', 'de Dertemouthe', 'de Kent',
        'Fitz Water', 'Fitz Hugh', 'Robynson', 'Wilkynson', 'Hobbesone', 'Longe', 'Whyte', 'Litel', 'Broun',
        'Godsalve', 'Lightfoot', 'Shakespere',
    ];

    /** @var list<string> */
    protected const array TITLE_MALE = ['Sire', 'Maister', 'Frere', 'Daun'];

    /** @var list<string> */
    protected const array TITLE_FEMALE = ['Dame', 'Lady', 'Prioresse'];

    /** @var list<string> */
    protected const array SUFFIX = ['the elder', 'the yonger'];
}
