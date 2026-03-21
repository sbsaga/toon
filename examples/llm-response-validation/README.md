# LLM Response Validation Example

This example shows how to ask a model for TOON output and validate it safely before using it.

~~~php
use Sbsaga\Toon\Facades\Toon;

$prompt = <<<PROMPT
Return the answer in TOON format only.

Expected shape:
```toon
summary:
risks:
  items[2]{severity,message}:
```
PROMPT;

$modelOutput = $llm->chat($prompt);
$validation = Toon::validate($modelOutput, strict: true);

if (!$validation['valid']) {
    throw new RuntimeException('Model returned invalid TOON: ' . $validation['error']);
}

$data = Toon::decode($modelOutput);
~~~

## Why It Helps

- makes LLM integrations safer
- gives you a clean retry or fallback path
- keeps structured responses compact and readable
