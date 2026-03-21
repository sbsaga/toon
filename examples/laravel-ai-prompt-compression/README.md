# AI Prompt Compression Example

This example shows the intended Laravel workflow for sending structured data into an LLM prompt without embedding full JSON.

## Scenario

You want to summarize account state, recent orders, and alerts in a single prompt.

```php
use Sbsaga\Toon\Facades\Toon;

$context = [
    'account' => $account->only(['id', 'name', 'tier']),
    'orders' => $orders->map->only(['id', 'status', 'total'])->all(),
    'alerts' => $alerts->map->only(['severity', 'message'])->all(),
];

$prompt = <<<PROMPT
Review this customer context and identify risks:

%s
PROMPT;

$response = $llm->chat(sprintf($prompt, Toon::encode($context)));
```

## Why This Works

- repeated row keys can collapse into a single TOON table header
- the output is still readable during debugging
- `Toon::diff()` can measure whether the payload is worth sending in TOON form
