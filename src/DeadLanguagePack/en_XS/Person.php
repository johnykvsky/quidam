<?php

declare(strict_types = 1);

namespace Quidam\DeadLanguagePack\en_XS;

use Quidam\Core\Person as CorePerson;

/**
 * Elizabethan names - Shakespeare's family and characters, surnames of his fellow players and of his clowns ("Nick
 * Bottom", "Beatrice Hathaway"); titles of the age and insults from the plays
 */
class Person extends CorePerson
{
    /** @var list<string> */
    protected const array FIRST_NAME_MALE = [
        'William', 'John', 'Thomas', 'Richard', 'Robert', 'Henry', 'Edmund', 'Francis', 'Christopher', 'Walter',
        'Philip', 'Anthony', 'Gilbert', 'Hamnet', 'Romeo', 'Benvolio', 'Mercutio', 'Orsino', 'Sebastian', 'Antonio',
        'Bassanio', 'Lorenzo', 'Benedick', 'Claudio', 'Horatio', 'Laertes', 'Lysander', 'Demetrius', 'Orlando',
        'Malvolio', 'Toby', 'Andrew', 'Nick', 'Peter',
    ];

    /** @var list<string> */
    protected const array FIRST_NAME_FEMALE = [
        'Anne', 'Susanna', 'Judith', 'Joan', 'Mary', 'Elizabeth', 'Margaret', 'Agnes', 'Juliet', 'Rosalind', 'Viola',
        'Olivia', 'Portia', 'Beatrice', 'Hero', 'Ophelia', 'Desdemona', 'Helena', 'Hermia', 'Miranda', 'Imogen',
        'Celia', 'Perdita', 'Cordelia', 'Jessica', 'Nerissa', 'Isabella', 'Katherine', 'Maria',
    ];

    /** @var list<string> */
    protected const array FIRST_NAME_NEUTRAL = [];

    /**
     * Shakespeare's family, fellow players and playwrights, his clowns and households
     *
     * @var list<string>
     */
    protected const array LAST_NAME = [
        'Hathaway', 'Arden', 'Quiney', 'Burbage', 'Kemp', 'Armin', 'Heminges', 'Condell', 'Alleyn', 'Henslowe',
        'Marlowe', 'Jonson', 'Kyd', 'Greene', 'Dekker', 'Fletcher', 'Field', 'Bottom', 'Quince', 'Snout', 'Flute',
        'Snug', 'Starveling', 'Dogberry', 'Verges', 'Belch', 'Aguecheek', 'Shallow', 'Slender', 'Pistol', 'Bardolph',
        'Quickly', 'Overdone', 'Elbow', 'Froth', 'Page', 'Ford', 'Capulet', 'Montague',
    ];

    /** @var list<string> */
    protected const array TITLE_MALE = ['Master', 'Sir', 'Goodman', 'Signior', 'Lord'];

    /** @var list<string> */
    protected const array TITLE_FEMALE = ['Mistress', 'Goodwife', 'Lady', 'Madam'];

    /** @var list<string> */
    protected const array SUFFIX = ['the elder', 'the younger', 'Gent.', 'Esq.'];

    /**
     * Insults, as the plays have them
     *
     * @var list<string>
     */
    protected const array INSULTS = [
        'Thou cream-faced loon!', 'Away, you starvelling, you elf-skin, you dried neat\'s-tongue!',
        'Thou lump of foul deformity!', 'I do desire we may be better strangers.',
        'More of your conversation would infect my brain.', 'Thy tongue outvenoms all the worms of Nile.',
        'You Banbury cheese!', 'Peace, ye fat guts!', 'Out of my sight! Thou dost infect mine eyes.',
        'Methink\'st thou art a general offence, and every man should beat thee.',
        'Thou art a boil, a plague-sore, an embossed carbuncle.', 'Thou art as loathsome as a toad.',
    ];

    /**
     * An insult from the plays, e.g. "Thou cream-faced loon!" (Macbeth)
     */
    public function insult(): string
    {
        return $this->randomizer->randomElement(static::INSULTS);
    }
}
