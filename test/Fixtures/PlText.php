<?php

declare(strict_types = 1);

namespace Quidam\Test\Fixtures;

use Quidam\Addon\Text;

/**
 * Own corpus - Adam Mickiewicz, "Pan Tadeusz" (1834, public domain), fragment
 */
class PlText extends Text
{
    protected const string CORPUS = <<<'TEXT'
        Litwo! Ojczyzno moja! ty jesteś jak zdrowie. Ile cię trzeba cenić, ten tylko się dowie, kto cię stracił. Dziś
        piękność twą w całej ozdobie widzę i opisuję, bo tęsknię po tobie. Panno Święta, co jasnej bronisz Częstochowy i w
        Ostrej świecisz Bramie! Ty, co gród zamkowy nowogródzki ochraniasz z jego wiernym ludem! Jak mnie dziecko do
        zdrowia powróciłaś cudem. Gdy od płaczącej matki pod Twoją opiekę ofiarowany, martwą podniosłem powiekę i zaraz
        mogłem pieszo do Twych świątyń progu iść za wrócone życie podziękować Bogu, tak nas powrócisz cudem na Ojczyzny
        łono. Tymczasem przenoś moję duszę utęsknioną do tych pagórków leśnych, do tych łąk zielonych, szeroko nad
        błękitnym Niemnem rozciągnionych; do tych pól malowanych zbożem rozmaitem, wyzłacanych pszenicą, posrebrzanych
        żytem; gdzie bursztynowy świerzop, gryka jak śnieg biała, gdzie panieńskim rumieńcem dzięcielina pała, a wszystko
        przepasane, jakby wstęgą, miedzą zieloną, na niej z rzadka ciche grusze siedzą.
        TEXT;
}
