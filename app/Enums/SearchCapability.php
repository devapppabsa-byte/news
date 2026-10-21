<?php

namespace App\Enums;

enum SearchCapability: string
{
    case DateFilter = 'date_filter';
    case DomainFilter = 'domain_filter';
    case LanguageFilter = 'language_filter';
    case KeywordFilter = 'keyword_filter';
}
