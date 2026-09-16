# Industry-standard design notes

1. Risk must be explainable and repeatable. Keep a score, dimensions, signal codes, confidence, evidence and calculation timestamp.
2. Use layered controls rather than one verification checkbox.
3. Do not delete suspected duplicates; cluster them and preserve source provenance.
4. Positive signals (e.g. presence across multiple independent sources) must not be treated as fraud signals.
5. Never equate risk with fraud. Risk means review/restriction may be appropriate.
6. Use human review for consequential decisions.
7. Rate-limit and monitor automated source access. Respect robots.txt, terms, licenses and API limits before enabling a connector.
8. Keep personally identifying information out of broad risk logs; store only what is necessary and protect sensitive fields.
9. Add external sanctions/KYC/IP/device intelligence only through licensed/approved services.
10. Calibrate thresholds using reviewed outcomes after real production data exists; fixed thresholds are the initial policy, not the final truth.
