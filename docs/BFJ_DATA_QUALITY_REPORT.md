# BFJ Data Quality Report

Source issues are reported, never silently fixed. Codes: INVALID_DATE, FORMULA_ERROR, MISSING_SEQUENCE, NUMBER_PATTERN_VARIANCE, MISSING_FIELD, DUPLICATE_REFERENCE, ERROR_MISSING_QTY, AMOUNT_VARIANCE, HOURS_VARIANCE.

- Duplicates: file SHA-256 + row fingerprints (sales: date/DO/customer/vehicle/material/volume/amount; sparepart: date/item/qty/type/equipment; payroll: employee/date/hours/component; deposit: customer/date/ref/amount/material).
- Blank qty: ERROR_MISSING_QTY, never coerced to 0.
- Invalid dates / `#ERROR!`: stored as IMPORT_WARNING with row trace.
- Inconsistent naming: BFJ company variants, PT SMJ aliases, plate spacing/case, material case — normalized with original preserved; POSSIBLE_MATCH requires manual review.
- Number formats: Rp30.000 / Rp135.975.000 / Rp388,500,000 / 4,51 / 13.91 — money parser ≠ qty parser, transformation rule kept per value.
- Missing masters / references: Master Mapping Queue (match/create/ignore-as-legacy-text); invoice ids never guessed.
- Summary/detail mismatch: recap variance reported with root cause, never force-matched.
- Double-count risk: sales↔finance, sales↔deposit, payroll↔finance, sparepart↔maintenance cross-file hints; both sides never posted as separate revenue/expense.

Privacy: real workbooks stay migration input only. Repo holds anonymized fixtures (`tests/Fixtures/bfj/`, Customer A/B, Driver A, BG 8000 XX, SPR-00x SAMPLE).
