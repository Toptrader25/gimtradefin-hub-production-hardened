# Multilingual Intelligence Layer (Stages 1–6)

This is a cross-cutting capability, not a separate commercial stage.

## Production principles
- Preserve the exact original source text permanently.
- Detect language with confidence and flag uncertain/mixed-language text.
- Extract commercial concepts in the source language before translation where possible.
- Translation is a secondary representation for search, matching and analyst workflows; it never overwrites evidence.
- Keep provider/model/version metadata for every translation.
- Protect company names, product codes, HS codes, Incoterms, quantities, currencies and identifiers.
- Keep a human-review path for low-confidence/high-value translations.

## Initial coverage
English, Simplified/Traditional Chinese, Vietnamese, Indonesian, Malay, Thai, Japanese, Korean, Hindi, Bengali, Arabic, Urdu, Turkish, Persian, Russian, Spanish, Portuguese, French, German and Italian.

## Translation provider
The package is provider-neutral. Set `LEADHUNTER_TRANSLATION_PROVIDER=deepl` and `DEEPL_API_KEY` to enable the included DeepL adapter. With `none`, language detection and source-language concept extraction still work without external translation calls.

DeepL recommends specifying the source language when possible and notes that longer contextual text improves detection reliability. citeturn0search0

## Quality controls
The initial implementation checks numeric consistency between source and translation. Production should additionally check currencies, units, dates, HS codes, company names, product codes and glossary terms. Translation QA should preserve terminology and source meaning rather than relying on literal wording. citeturn0search3turn0search11

## Governance
Do not send restricted/private data to external translation providers without the appropriate permission and data-processing review. Public-source evidence remains immutable and traceable.
