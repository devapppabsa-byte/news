<?php

namespace App\Enums;

enum AICapability: string
{
    case StructuredOutput = 'structured_output';
    case JsonMode = 'json_mode';
    case FunctionCalling = 'function_calling';
    case WebSearch = 'web_search';
}
