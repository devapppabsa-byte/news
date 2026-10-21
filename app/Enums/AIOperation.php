<?php

namespace App\Enums;

enum AIOperation: string
{
    case Analyze = 'analyze';
    case ClassifyRelevance = 'classify_relevance';
    case Summarize = 'summarize';
    case ExtractStructured = 'extract_structured';
    case DetectDuplicates = 'detect_duplicates';
    case AssessNovedad = 'assess_novedad';
}
